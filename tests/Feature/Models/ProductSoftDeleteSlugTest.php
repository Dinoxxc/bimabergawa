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
}
