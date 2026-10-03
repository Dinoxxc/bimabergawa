<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierModelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * TC-CAT-SUP-001: Verifikasi pembuatan Supplier dan factory
     */
    public function test_supplier_can_be_created_with_factory(): void
    {
        $supplier = Supplier::factory()->create([
            'name' => 'PT. Schneider Prima Distributor Indonesia',
            'code' => 'SUP-TEST-001',
            'payment_terms' => 'Net 30',
            'is_active' => true,
        ]);

        $this->assertNotNull($supplier->id);
        $this->assertEquals('PT. Schneider Prima Distributor Indonesia', $supplier->name);
        $this->assertEquals('SUP-TEST-001', $supplier->code);
        $this->assertEquals('Net 30', $supplier->payment_terms);
        $this->assertTrue($supplier->is_active);
    }

    /**
     * Rule 6.8: Verifikasi short alias morph map
     */
    public function test_supplier_has_short_morph_alias(): void
    {
        $this->assertEquals('supplier', (new Supplier)->getMorphClass());
    }

    /**
     * Verifikasi relasi Supplier ke Brand
     */
    public function test_supplier_belongs_to_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Schneider Electric Test']);
        $supplier = Supplier::factory()->create(['brand_id' => $brand->id]);

        $this->assertInstanceOf(Brand::class, $supplier->brand);
        $this->assertEquals($brand->id, $supplier->brand->id);
    }

    /**
     * TC-CAT-SUP-002 & TC-CAT-SUP-004: Relasi Product ke Primary Supplier dan proteksi Soft Deletes
     */
    public function test_product_belongs_to_primary_supplier_with_soft_deletes(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create(['brand_id' => $brand->id]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'primary_supplier_id' => $supplier->id,
            'name' => 'MCB Acti9 iC60N 1P 16A C-Curve',
            'slug' => 'mcb-acti9-ic60n-1p-16a-'.time(),
            'model_number' => 'A9F74116',
            'cost_price' => 75000.00,
            'currency' => 'IDR',
        ]);

        $this->assertInstanceOf(Supplier::class, $product->primarySupplier);
        $this->assertEquals($supplier->id, $product->primarySupplier->id);
        $this->assertTrue($supplier->products->contains($product));

        // Soft delete supplier
        $supplier->delete();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        // Pastikan relasi Product::primarySupplier tetap me-load objek aslinya via withTrashed (Full-Chain withTrashed)
        $product->refresh();
        $this->assertNotNull($product->primarySupplier);
        $this->assertEquals($supplier->id, $product->primarySupplier->id);
    }

    /**
     * Verifikasi scopeActive Supplier
     */
    public function test_supplier_scope_active(): void
    {
        $activeSupplier = Supplier::factory()->create(['is_active' => true]);
        $inactiveSupplier = Supplier::factory()->create(['is_active' => false]);

        $activeIds = Supplier::active()->pluck('id');

        $this->assertTrue($activeIds->contains($activeSupplier->id));
        $this->assertFalse($activeIds->contains($inactiveSupplier->id));
    }
}
