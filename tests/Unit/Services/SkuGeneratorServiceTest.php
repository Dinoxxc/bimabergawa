<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\SkuGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private Brand $schneider;

    private Brand $ebara;

    private Category $electrical;

    private Category $mechanical;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schneider = Brand::factory()->create([
            'name' => 'Schneider Electric',
            'code' => 'SCH',
        ]);

        $this->ebara = Brand::factory()->create([
            'name' => 'Ebara',
            'code' => 'EBR',
        ]);

        $this->electrical = Category::factory()->create([
            'name' => 'Distribusi Daya Listrik',
            'code' => 'ELE',
        ]);

        $this->mechanical = Category::factory()->create([
            'name' => 'Mekanikal & Pompa Industri',
            'code' => 'MEC',
        ]);
    }

    public function test_generate_builds_standard_corporate_format(): void
    {
        $product = Product::factory()->make([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'model_number' => 'TEST-MOD-01',
        ]);

        $sku = SkuGeneratorService::generate($product);

        $this->assertSame('PBB-ELE-SCH-0001', $sku);
    }

    public function test_generate_increments_sequence_for_same_division_and_brand(): void
    {
        // Item 1
        $p1 = Product::factory()->create([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'model_number' => 'SCH-MCB-1',
        ]);
        $this->assertSame('PBB-ELE-SCH-0001', $p1->sku);

        // Item 2
        $p2 = Product::factory()->create([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'model_number' => 'SCH-MCB-2',
        ]);
        $this->assertSame('PBB-ELE-SCH-0002', $p2->sku);
    }

    public function test_sequence_is_scoped_by_division_and_brand_combination(): void
    {
        // Schneider Electrical: 0001
        $eleSch = Product::factory()->create([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'model_number' => 'ELE-SCH-01',
        ]);
        $this->assertSame('PBB-ELE-SCH-0001', $eleSch->sku);

        // Ebara Mechanical: starts at 0001
        $mecEbr = Product::factory()->create([
            'brand_id' => $this->ebara->id,
            'category_id' => $this->mechanical->id,
            'model_number' => 'MEC-EBR-01',
        ]);
        $this->assertSame('PBB-MEC-EBR-0001', $mecEbr->sku);
    }

    public function test_auto_derives_codes_when_brand_or_category_code_is_empty(): void
    {
        $customBrand = Brand::factory()->create([
            'name' => 'Danfoss Power',
            'code' => null,
        ]);

        $customCategory = Category::factory()->create([
            'name' => 'Solar Energy',
            'code' => null,
        ]);

        $sku = SkuGeneratorService::generateFor($customCategory, $customBrand);

        // Category "Solar Energy" -> "SOL", Brand "Danfoss Power" -> "DANF"
        $this->assertStringStartsWith('PBB-SOL-DANF-', $sku);
        $this->assertMatchesRegularExpression('/^PBB-SOL-DANF-\d{4}$/', $sku);
    }

    public function test_fallback_to_gen_when_brand_or_category_is_null(): void
    {
        $sku = SkuGeneratorService::generateFor(null, null);

        $this->assertSame('PBB-GEN-GEN-0001', $sku);
    }
}
