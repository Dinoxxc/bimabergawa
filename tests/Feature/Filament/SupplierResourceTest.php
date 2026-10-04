<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\SupplierResource\Pages\CreateSupplier;
use App\Filament\Resources\SupplierResource\Pages\EditSupplier;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesUser;

    private User $financeUser;

    private User $logisticsUser;

    private User $mitraUser;

    private Brand $brand;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['email' => 'admin@bimabergawa.com', 'is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->salesUser = User::factory()->create(['email' => 'sales@bimabergawa.com', 'is_active' => true]);
        $this->salesUser->assignRole('sales_pic');

        $this->financeUser = User::factory()->create(['email' => 'finance@bimabergawa.com', 'is_active' => true]);
        $this->financeUser->assignRole('finance');

        $this->logisticsUser = User::factory()->create(['email' => 'logistics@bimabergawa.com', 'is_active' => true]);
        $this->logisticsUser->assignRole('logistics');

        $this->mitraUser = User::factory()->create(['email' => 'mitra@bimabergawa.com', 'is_active' => true]);
        $this->mitraUser->assignRole('freelance_sales');

        $this->brand = Brand::factory()->create(['name' => 'Schneider Electric', 'code' => 'SCH']);
        $this->category = Category::factory()->create(['name' => 'Electrical Distribution', 'code' => 'ED']);
    }

    /**
     * TC-CAT-SUP-001: Pembuatan Rekanan Vendor Baru Lengkap dengan Brand Prinsipal & Ketentuan Bayar.
     */
    public function test_can_create_new_supplier_with_brand_and_payment_terms(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreateSupplier::class)
            ->fillForm([
                'name' => 'PT. Schneider Prima Distributor Indonesia',
                'code' => 'SUP-SCH',
                'brand_id' => $this->brand->id,
                'contact_person' => 'Hendra Wijaya',
                'phone' => '08119876543',
                'email' => 'procurement@schneider-prima.co.id',
                'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
                'payment_terms' => 'Net 30',
                'discount_structure' => 'Pricelist - 35% + Project 5%',
                'notes' => 'Authorized tier-1 Schneider Electric distributor with ready stock buffer.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT. Schneider Prima Distributor Indonesia',
            'code' => 'SUP-SCH',
            'brand_id' => $this->brand->id,
            'contact_person' => 'Hendra Wijaya',
            'phone' => '08119876543',
            'payment_terms' => 'Net 30',
            'is_active' => 1,
        ]);
    }

    /**
     * TC-CAT-SUP-002: Asosiasi Produk Master ke Primary Supplier.
     */
    public function test_can_associate_product_to_primary_supplier(): void
    {
        $supplier = Supplier::factory()->create([
            'name' => 'PT. Schneider Prima Dist',
            'code' => 'SUP-SCH-PRI',
            'brand_id' => $this->brand->id,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sku' => 'SCH-ACTI9-C60N-1P-16A',
            'name' => 'Acti9 C60N MCB 1-Pole 16A',
            'primary_supplier_id' => $supplier->id,
        ]);

        $this->assertNotNull($product->primarySupplier);
        $this->assertSame($supplier->id, $product->primarySupplier->id);
        $this->assertEquals('PT. Schneider Prima Dist', $product->primarySupplier->name);
        $this->assertEquals(1, $supplier->products()->count());
    }

    /**
     * TC-CAT-SUP-003: Validasi Keunikan Kode Vendor (Unique Vendor Code).
     */
    public function test_unique_vendor_code_validation_prevents_duplicate(): void
    {
        Supplier::factory()->create([
            'code' => 'SUP-SCH',
            'name' => 'Existing Schneider Vendor',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateSupplier::class)
            ->fillForm([
                'name' => 'New Duplicate Vendor',
                'code' => 'SUP-SCH',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);
    }

    /**
     * TC-CAT-SUP-004: Proteksi Soft Deletes Rekanan Vendor & Rantai Transaksi Historis.
     */
    public function test_soft_deletes_preserves_supplier_for_historical_audits(): void
    {
        $supplier = Supplier::factory()->create([
            'name' => 'PT. ABB Distributor Indonesia',
            'code' => 'SUP-ABB',
        ]);

        $product = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'primary_supplier_id' => $supplier->id,
        ]);

        // Soft delete the supplier
        $supplier->delete();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        // Historical query using withTrashed returns the deleted supplier
        $queriedProduct = Product::with('primarySupplier')->find($product->id);
        $this->assertNotNull($queriedProduct->primarySupplier);
        $this->assertEquals('PT. ABB Distributor Indonesia', $queriedProduct->primarySupplier->name);
    }

    /**
     * TC-CAT-SUP-005: Asosiasi Multi-Sourcing Vendor Pivot Matrix (product_suppliers).
     */
    public function test_multi_sourcing_vendor_pivot_matrix(): void
    {
        $product = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sku' => 'SCH-MCB-16A',
        ]);

        $supplierA = Supplier::factory()->create(['name' => 'Supplier A Resmi', 'code' => 'SUP-A']);
        $supplierB = Supplier::factory()->create(['name' => 'Supplier B Alternatif', 'code' => 'SUP-B']);
        $supplierC = Supplier::factory()->create(['name' => 'Supplier C Ready Stock', 'code' => 'SUP-C']);

        // Attach 3 vendors with different purchase prices, lead times, and primary status
        $product->suppliers()->attach($supplierA->id, [
            'supplier_sku' => 'SKU-SUP-A-01',
            'purchase_price' => 75000.00,
            'currency' => 'IDR',
            'lead_time_days' => 0,
            'min_order_qty' => 10,
            'is_primary' => true,
        ]);

        $product->suppliers()->attach($supplierB->id, [
            'supplier_sku' => 'SKU-SUP-B-99',
            'purchase_price' => 78000.00,
            'currency' => 'IDR',
            'lead_time_days' => 2,
            'min_order_qty' => 1,
            'is_primary' => false,
        ]);

        $product->suppliers()->attach($supplierC->id, [
            'supplier_sku' => 'SKU-SUP-C-55',
            'purchase_price' => 82000.00,
            'currency' => 'IDR',
            'lead_time_days' => 0,
            'min_order_qty' => 5,
            'is_primary' => false,
        ]);

        $this->assertEquals(3, $product->suppliers()->count());

        $primaryVendor = $product->suppliers()->wherePivot('is_primary', true)->first();
        $this->assertNotNull($primaryVendor);
        $this->assertSame($supplierA->id, $primaryVendor->id);
        $this->assertEquals('75000.00', $primaryVendor->pivot->purchase_price);
        $this->assertEquals(0, $primaryVendor->pivot->lead_time_days);
        $this->assertTrue((bool) $primaryVendor->pivot->is_primary);

        $altVendor = $product->suppliers()->where('suppliers.id', $supplierB->id)->first();
        $this->assertNotNull($altVendor);
        $this->assertEquals('78000.00', $altVendor->pivot->purchase_price);
        $this->assertEquals(2, $altVendor->pivot->lead_time_days);
        $this->assertFalse((bool) $altVendor->pivot->is_primary);
    }

    /**
     * TC-CAT-SUP-006: Pencegahan Duplikasi Vendor yang Sama pada 1 Part Number (Unique Constraint).
     */
    public function test_prevent_duplicate_vendor_on_same_product_pivot(): void
    {
        $product = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);

        $supplier = Supplier::factory()->create(['name' => 'Supplier X', 'code' => 'SUP-X']);

        // First attachment succeeds
        $product->suppliers()->attach($supplier->id, [
            'purchase_price' => 50000.00,
            'is_primary' => true,
        ]);

        // Second attachment to same product and same supplier must trigger QueryException (uk_prod_sup)
        $this->expectException(QueryException::class);
        $product->suppliers()->attach($supplier->id, [
            'purchase_price' => 52000.00,
            'is_primary' => false,
        ]);
    }

    /**
     * RBAC Access: Super Admin, Sales PIC, and Finance can access suppliers list.
     */
    public function test_authorized_internal_roles_can_access_suppliers(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'PT. Mega Electric Supply', 'code' => 'SUP-MES']);

        // Super Admin
        $this->actingAs($this->superAdmin)
            ->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertSuccessful()
            ->assertSee('PT. Mega Electric Supply');

        // Sales PIC
        $this->actingAs($this->salesUser)
            ->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertSuccessful()
            ->assertSee('PT. Mega Electric Supply');

        // Finance
        $this->actingAs($this->financeUser)
            ->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertSuccessful()
            ->assertSee('PT. Mega Electric Supply');
    }

    /**
     * RBAC Access: Logistics and Freelance Mitra are strictly blocked.
     */
    public function test_unauthorized_roles_are_blocked_from_suppliers(): void
    {
        // Logistics role
        $this->actingAs($this->logisticsUser)
            ->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertForbidden();

        // Mitra Agent role
        $this->actingAs($this->mitraUser)
            ->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertForbidden();
    }

    /**
     * Unauthenticated guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('http://app.bimabergawa.test/admin/suppliers')
            ->assertRedirect('http://app.bimabergawa.test/login');
    }

    /**
     * Test editing supplier updates record successfully.
     */
    public function test_can_edit_supplier(): void
    {
        $supplier = Supplier::factory()->create([
            'name' => 'Original Vendor Name',
            'code' => 'SUP-ORIG',
            'payment_terms' => 'Net 14',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Vendor Name',
                'payment_terms' => 'Net 45',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Vendor Name',
            'payment_terms' => 'Net 45',
        ]);
    }
}
