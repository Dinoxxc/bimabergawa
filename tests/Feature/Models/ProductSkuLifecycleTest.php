<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\BulkSkuNormalizationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create([
            'name' => 'Schneider Electric',
            'code' => 'SCH',
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Otomasi & Kendali Industri',
            'code' => 'AUT',
        ]);
    }

    public function test_product_creation_automatically_generates_internal_sku(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Inverter Altivar ATV320 5.5kW',
            'slug' => 'schneider-electric-atv320u55n4b',
            'model_number' => 'ATV320U55N4B',
            'cost_price' => 4500000.00,
        ]);

        $this->assertNotNull($product->sku);
        $this->assertSame('PBB-AUT-SCH-0001', $product->sku);
    }

    public function test_explicit_sku_is_honored_and_not_overwritten(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Special Custom Breaker',
            'slug' => 'special-custom-breaker',
            'model_number' => 'SPECIAL-001',
            'sku' => 'PBB-CUSTOM-9999',
            'cost_price' => 1000000.00,
        ]);

        $this->assertSame('PBB-CUSTOM-9999', $product->sku);
    }

    public function test_sku_is_immutable_on_product_update(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Contactor TeSys 25A',
            'slug' => 'contactor-tesys-25a',
            'model_number' => 'LC1D25M7',
            'cost_price' => 350000.00,
        ]);

        $originalSku = $product->sku;
        $this->assertSame('PBB-AUT-SCH-0001', $originalSku);

        // Ubah nama produk dan brand
        $newBrand = Brand::factory()->create(['name' => 'ABB', 'code' => 'ABB']);
        $product->update([
            'name' => 'Contactor Renamed',
            'brand_id' => $newBrand->id,
        ]);

        $product->refresh();
        $this->assertSame($originalSku, $product->sku);
    }

    public function test_three_way_matching_by_model_number_sku_and_name(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Inverter Altivar ATV320 5.5kW 380V',
            'slug' => 'atv320u55n4b',
            'model_number' => 'ATV320U55N4B',
            'cost_price' => 4500000.00,
        ]);

        // 1. Ditemukan via Part Number Pabrikan (model_number)
        $matchedByModel = BulkSkuNormalizationService::matchSku('ATV320U55N4B');
        $this->assertNotNull($matchedByModel);
        $this->assertSame($product->id, $matchedByModel->id);

        // 2. Ditemukan via Internal SKU PBB (sku)
        $matchedBySku = BulkSkuNormalizationService::matchSku('PBB-AUT-SCH-0001');
        $this->assertNotNull($matchedBySku);
        $this->assertSame($product->id, $matchedBySku->id);

        // 2b. Ditemukan via Normalized SKU (variasi underscore / lowercase)
        $matchedByNormalizedSku = BulkSkuNormalizationService::matchSku('pbb_aut_sch_0001');
        $this->assertNotNull($matchedByNormalizedSku);
        $this->assertSame($product->id, $matchedByNormalizedSku->id);

        // 3. Ditemukan via Nama Produk (name)
        $matchedByName = BulkSkuNormalizationService::matchSku('Inverter Altivar ATV320 5.5kW 380V');
        $this->assertNotNull($matchedByName);
        $this->assertSame($product->id, $matchedByName->id);
    }

    public function test_database_enforces_unique_sku(): void
    {
        Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Product Alpha',
            'slug' => 'product-alpha',
            'model_number' => 'ALPHA-01',
            'sku' => 'PBB-UNIQUE-0001',
            'cost_price' => 100000.00,
        ]);

        $this->expectException(QueryException::class);

        // Mencoba membuat produk lain dengan SKU yang sama
        Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Product Beta',
            'slug' => 'product-beta',
            'model_number' => 'BETA-02',
            'sku' => 'PBB-UNIQUE-0001',
            'cost_price' => 200000.00,
        ]);
    }
}
