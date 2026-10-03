<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
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

    /**
     * Rule 6.8: Verifikasi short alias morph map untuk ProductSupplier
     */
    public function test_product_supplier_has_short_morph_alias(): void
    {
        $this->assertEquals('product_supplier', (new ProductSupplier)->getMorphClass());
    }

    /**
     * TC-CAT-SUP-005: Verifikasi 1 produk dapat disuplai oleh beberapa supplier dengan variasi harga modal (Multi-Sourcing Pivot)
     */
    public function test_product_can_have_multiple_suppliers_with_pivot_data(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Schneider ATV320 2.2kW Inverter',
            'slug' => 'schneider-atv320-22kw-'.time(),
            'model_number' => 'ATV320U22N4B',
            'cost_price' => 4500000.00,
            'currency' => 'IDR',
        ]);

        $supplierPrimary = Supplier::factory()->create([
            'name' => 'PT. Schneider Prima Distributor Indonesia',
            'code' => 'SUP-TEST-SCH-'.time(),
            'brand_id' => $brand->id,
        ]);

        $supplierSecondary = Supplier::factory()->create([
            'name' => 'PT. Mega Daya Otomasi ABB',
            'code' => 'SUP-TEST-MDO-'.time(),
        ]);

        // Hubungkan Supplier Utama
        $product->suppliers()->attach($supplierPrimary->id, [
            'supplier_sku' => 'SCH-ATV320',
            'purchase_price' => 4500000.00,
            'currency' => 'IDR',
            'lead_time_days' => 0,
            'min_order_qty' => 1,
            'is_primary' => true,
            'notes' => 'Gudang Cikarang ready stock',
        ]);

        // Hubungkan Supplier Cadangan / Sekunder dengan selisih harga modal
        $product->suppliers()->attach($supplierSecondary->id, [
            'supplier_sku' => 'MDO-ATV320',
            'purchase_price' => 4750000.00,
            'currency' => 'IDR',
            'lead_time_days' => 2,
            'min_order_qty' => 1,
            'is_primary' => false,
            'notes' => 'Sub-distributor Surabaya butuh 2 hari kirim',
        ]);

        $product->refresh();

        // Verifikasi Many-to-Many dari sisi Product
        $this->assertCount(2, $product->suppliers);

        $primaryPivot = $product->suppliers->where('id', $supplierPrimary->id)->first()->pivot;
        $this->assertEquals(4500000.00, $primaryPivot->purchase_price);
        $this->assertEquals(0, $primaryPivot->lead_time_days);
        $this->assertTrue($primaryPivot->is_primary);

        $secondaryPivot = $product->suppliers->where('id', $supplierSecondary->id)->first()->pivot;
        $this->assertEquals(4750000.00, $secondaryPivot->purchase_price);
        $this->assertEquals(2, $secondaryPivot->lead_time_days);
        $this->assertFalse($secondaryPivot->is_primary);

        // Verifikasi Many-to-Many dari sisi Supplier
        $this->assertTrue($supplierSecondary->suppliedProducts->contains($product));
    }

    /**
     * TC-CAT-SUP-006: Verifikasi pencegahan duplikasi supplier yang sama pada 1 produk (Unique Constraint)
     */
    public function test_cannot_attach_duplicate_supplier_to_same_product(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Omron Timer Relay H3CR',
            'slug' => 'omron-timer-relay-h3cr-'.time(),
            'model_number' => 'H3CR-A8',
            'cost_price' => 350000.00,
            'currency' => 'IDR',
        ]);

        $supplier = Supplier::factory()->create();

        $product->suppliers()->attach($supplier->id, [
            'purchase_price' => 350000.00,
            'is_primary' => true,
        ]);

        $this->expectException(QueryException::class);

        // Coba attach ulang supplier yang sama tanpa detaching harus memicu QueryException karena unique index uk_prod_sup
        $product->suppliers()->attach($supplier->id, [
            'purchase_price' => 360000.00,
            'is_primary' => false,
        ]);
    }
}
