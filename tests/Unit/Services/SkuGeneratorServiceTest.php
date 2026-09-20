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

    public function test_sequence_correctly_transitions_beyond_9999_items(): void
    {
        // Simulasi produk dengan sequence 9999 dan 10000 sudah ada
        Product::factory()->create([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'sku' => 'PBB-ELE-SCH-9999',
            'model_number' => 'SIM-9999',
        ]);

        Product::factory()->create([
            'brand_id' => $this->schneider->id,
            'category_id' => $this->electrical->id,
            'sku' => 'PBB-ELE-SCH-10000',
            'model_number' => 'SIM-10000',
        ]);

        // Pembangkitan SKU berikutnya harus menghasilkan 10001 secara numerik
        $nextSku = SkuGeneratorService::generateFor($this->electrical, $this->schneider);
        $this->assertSame('PBB-ELE-SCH-10001', $nextSku);
    }

    public function test_subcategory_inherits_parent_category_division_code_when_code_is_empty(): void
    {
        // Subcategory under electrical with empty code
        $mcbSubcategory = Category::factory()->create([
            'name' => 'Miniature Circuit Breaker',
            'parent_id' => $this->electrical->id,
            'code' => null,
        ]);

        $sku = SkuGeneratorService::generateFor($mcbSubcategory, $this->schneider);

        // Harus mewarisi kode 'ELE' dari parent category, bukan singkatan nama subkategori 'MIN'
        $this->assertSame('PBB-ELE-SCH-0001', $sku);
    }

    public function test_code_sanitization_ignores_symbols_and_falls_back_gracefully(): void
    {
        $symbolBrand = Brand::factory()->create([
            'name' => 'General Electric',
            'code' => '---###---',
        ]);

        $symbolCategory = Category::factory()->create([
            'name' => 'Instrumentation',
            'code' => '***@@@',
        ]);

        $sku = SkuGeneratorService::generateFor($symbolCategory, $symbolBrand);

        // Kategori "Instrumentation" -> "INS", Brand "General Electric" -> "GENE"
        $this->assertSame('PBB-INS-GENE-0001', $sku);
    }
}
