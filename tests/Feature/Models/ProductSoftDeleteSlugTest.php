<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductSoftDeleteSlugTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Rule 6.10: Resolusi Benturan Unique Slug vs Soft Deletes
     */
    public function test_soft_deleting_product_appends_timestamp_to_slug_and_frees_original_slug(): void
    {
        $brand = Brand::create([
            'name' => 'Schneider Electric Test',
            'slug' => 'schneider-test-'.time(),
        ]);

        $category = Category::create([
            'name' => 'MCB Test',
            'slug' => 'mcb-test-'.time(),
        ]);

        $targetSlug = 'schneider-actit9-ic60n-16a-'.time();

        // 1. Create first product
        $product1 = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Schneider Acti9 iC60N 16A 1P',
            'slug' => $targetSlug,
            'model_number' => 'A9F74116',
            'cost_price' => 75000.00,
            'availability_status' => 'ready_stock',
            'is_published' => true,
        ]);

        $this->assertEquals($targetSlug, $product1->slug);

        // 2. Soft delete product1
        $product1->delete();

        // 3. Verify product1 slug has been modified to release unique constraint
        $deletedProduct = Product::withTrashed()->find($product1->id);
        $this->assertNotNull($deletedProduct->deleted_at);
        $this->assertStringStartsWith($targetSlug.'-deleted-', $deletedProduct->slug);

        // 4. Create second product with the exact same original slug (must succeed without MySQL 1062 duplicate error)
        $product2 = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Schneider Acti9 iC60N 16A 1P Replacement',
            'slug' => $targetSlug,
            'model_number' => 'A9F74116-NEW',
            'cost_price' => 78000.00,
            'availability_status' => 'ready_stock',
            'is_published' => true,
        ]);

        $this->assertNotNull($product2->id);
        $this->assertEquals($targetSlug, $product2->slug);
    }

    /**
     * TC-ERP-INV-015: Pemulihan Produk (Product Restoring) dan Normalisasi Slug Bebas Benturan
     */
    public function test_restoring_product_normalizes_slug_and_handles_collision_gracefully(): void
    {
        $brand = Brand::create([
            'name' => 'ABB Test',
            'slug' => 'abb-test-'.time(),
        ]);

        $category = Category::create([
            'name' => 'Switch Test',
            'slug' => 'switch-test-'.time(),
        ]);

        $originalSlug = 'abb-isolator-switch-40a-'.time();

        // 1. Create and delete product
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'ABB Isolator Switch 40A',
            'slug' => $originalSlug,
            'model_number' => 'OT40F3',
            'cost_price' => 120000.00,
            'availability_status' => 'ready_stock',
            'is_published' => true,
        ]);
        $product->delete();

        // 2. Another product takes the original slug
        $newProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'ABB Isolator Switch 40A V2',
            'slug' => $originalSlug,
            'model_number' => 'OT40F3-V2',
            'cost_price' => 125000.00,
            'availability_status' => 'ready_stock',
            'is_published' => true,
        ]);

        // 3. Restore deleted product: ProductObserver detects collision and generates unique suffix
        $product->restore();
        $this->assertNull($product->deleted_at);
        $this->assertNotEquals($originalSlug, $product->slug);
        $this->assertStringStartsWith($originalSlug.'-', $product->slug);
    }

    /**
     * TC-ERP-INV-018: Mitigasi Tabrakan Deterministik Slug Restoring via Candidate While Loop
     */
    public function test_restoring_product_handles_multiple_sequential_slug_collisions(): void
    {
        $brand = Brand::create([
            'name' => 'Schneider Multi Collide',
            'slug' => 'schneider-multi-'.time(),
        ]);

        $category = Category::create([
            'name' => 'Contactor Multi',
            'slug' => 'contactor-multi-'.time(),
        ]);

        $baseSlug = 'schneider-actassi-'.time();

        // 1. Create Product A with baseSlug, then delete it
        $productA = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Product A Original',
            'slug' => $baseSlug,
            'model_number' => 'ACT-01',
            'cost_price' => 50000.00,
        ]);
        $productA->delete();

        // 2. Create Product B with baseSlug (takes original)
        $productB = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Product B Claims Original',
            'slug' => $baseSlug,
            'model_number' => 'ACT-02',
            'cost_price' => 55000.00,
        ]);

        // 3. Create Product C with baseSlug-2 (takes second suffix)
        $productC = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Product C Claims Suffix 2',
            'slug' => "{$baseSlug}-2",
            'model_number' => 'ACT-03',
            'cost_price' => 60000.00,
        ]);

        // 4. Restore Product A: candidate while loop must skip -2 and claim -3
        $productA->restore();
        $this->assertNull($productA->deleted_at);
        $this->assertEquals("{$baseSlug}-3", $productA->slug);
    }
}
