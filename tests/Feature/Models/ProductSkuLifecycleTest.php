<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\BulkSkuNormalizationService;
use App\Services\SkuParserService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'code' => '001',
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Otomasi & Kendali Industri',
            'code' => 'AU',
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
            'availability_status' => 'ready_stock',
        ]);

        $this->assertNotNull($product->sku);
        $this->assertSame(12, strlen($product->sku));
        $this->assertSame('SAU001000001', $product->sku);
    }

    public function test_explicit_sku_is_honored_and_not_overwritten(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Special Custom Breaker',
            'slug' => 'special-custom-breaker',
            'model_number' => 'SPECIAL-001',
            'sku' => 'CUSTOM000999',
            'cost_price' => 1000000.00,
        ]);

        $this->assertSame('CUSTOM000999', $product->sku);
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
            'availability_status' => 'ready_stock',
        ]);

        $originalSku = $product->sku;
        $this->assertSame('SAU001000001', $originalSku);

        // Ubah nama produk dan brand
        $newBrand = Brand::factory()->create(['name' => 'ABB', 'code' => '002']);
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
            'availability_status' => 'ready_stock',
        ]);

        // 1. Ditemukan via Part Number Pabrikan (model_number)
        $matchedByModel = BulkSkuNormalizationService::matchSku('ATV320U55N4B');
        $this->assertNotNull($matchedByModel);
        $this->assertSame($product->id, $matchedByModel->id);

        // 2. Ditemukan via Internal SKU PBB 12 Karakter (sku)
        $matchedBySku = BulkSkuNormalizationService::matchSku('SAU001000001');
        $this->assertNotNull($matchedBySku);
        $this->assertSame($product->id, $matchedBySku->id);

        // 2b. Ditemukan via Normalized SKU (variasi lowercase)
        $matchedByNormalizedSku = BulkSkuNormalizationService::matchSku('sau001000001');
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
            'sku' => 'UNIQ00000001',
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
            'sku' => 'UNIQ00000001',
            'cost_price' => 200000.00,
        ]);
    }

    public function test_manual_sku_is_normalized_on_creation(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Manual Sku Product',
            'slug' => 'manual-sku-product',
            'model_number' => 'MANUAL-01',
            'sku' => '  sau_001_000099  ',
            'cost_price' => 500000.00,
        ]);

        // Harus dinormalisasi menjadi huruf kapital dan tanda hubung
        $this->assertSame('SAU-001-000099', $product->sku);
    }

    public function test_sku_is_protected_against_accidental_wiping_on_update(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Protected Product',
            'slug' => 'protected-product',
            'model_number' => 'PROT-01',
            'cost_price' => 250000.00,
            'availability_status' => 'ready_stock',
        ]);

        $originalSku = $product->sku;
        $this->assertSame('SAU001000001', $originalSku);

        // Simulasi update dengan SKU null atau kosong (misal form tidak menyertakan SKU)
        $product->update([
            'name' => 'Protected Product Renamed',
            'sku' => null,
        ]);

        $product->refresh();
        $this->assertSame($originalSku, $product->sku);

        $product->update([
            'sku' => '',
        ]);

        $product->refresh();
        $this->assertSame($originalSku, $product->sku);
    }

    public function test_legacy_product_without_sku_is_assigned_sku_on_update(): void
    {
        // Simulasi produk legacy dengan SKU null di database
        $productId = DB::table('products')->insertGetId([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Legacy Product',
            'slug' => 'legacy-product',
            'model_number' => 'LEGACY-01',
            'sku' => null,
            'cost_price' => 100000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::find($productId);
        $this->assertNull($product->sku);

        // Trigger update
        $product->update(['name' => 'Legacy Product Updated']);
        $product->refresh();

        $this->assertNotNull($product->sku);
        $this->assertSame('SAU001000001', $product->sku);
    }

    public function test_stripped_alphanumeric_matching_finds_internal_sku(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Stripped Match Test',
            'slug' => 'stripped-match-test',
            'model_number' => 'STRIP-01',
            'cost_price' => 100000.00,
            'availability_status' => 'ready_stock',
        ]);

        // Query dengan teks alfanumerik bertanda hubung atau spasi (SAU-001-000001)
        $matched = BulkSkuNormalizationService::matchSku('SAU-001-000001');

        $this->assertNotNull($matched);
        $this->assertSame($product->id, $matched->id);
    }

    public function test_sku_parser_service_marks_exact_match_for_internal_sku(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Exact Sku Test',
            'slug' => 'exact-sku-test',
            'model_number' => 'EXACT-01',
            'cost_price' => 100000.00,
            'availability_status' => 'ready_stock',
        ]);

        $result = SkuParserService::parseAndMatch('SAU001000001');

        $this->assertSame(1, $result['matched_count']);
        $this->assertSame('exact', $result['matched'][0]['match_type']);
        $this->assertSame($product->id, $result['matched'][0]['product_id']);
    }

    public function test_product_without_slug_automatically_generates_unique_slug_from_name(): void
    {
        $product1 = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Miniature Circuit Breaker 16A',
            'model_number' => 'MCB-16A-TEST1',
            'cost_price' => 50000.00,
        ]);

        $this->assertSame('miniature-circuit-breaker-16a', $product1->slug);

        // Second product with identical name must get collision-free unique slug
        $product2 = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Miniature Circuit Breaker 16A',
            'model_number' => 'MCB-16A-TEST2',
            'cost_price' => 52000.00,
        ]);

        $this->assertSame('miniature-circuit-breaker-16a-2', $product2->slug);
    }

    public function test_product_with_cleared_slug_regenerates_slug_on_update(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Original Product Name',
            'slug' => 'original-slug',
            'model_number' => 'ORIG-001',
            'cost_price' => 50000.00,
        ]);

        $this->assertSame('original-slug', $product->slug);

        // Update product by clearing the slug
        $product->update([
            'slug' => '',
            'name' => 'Updated Product Name',
        ]);

        $this->assertSame('updated-product-name', $product->fresh()->slug);
    }
}
