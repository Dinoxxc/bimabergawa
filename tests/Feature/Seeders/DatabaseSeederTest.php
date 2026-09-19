<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionPayout;
use App\Models\CommissionTier;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DocumentSequence;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\InquiryTelemetry;
use App\Models\InternalComment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationPaymentTerm;
use App\Models\SalesOrder;
use App\Models\SystemSetting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\BrandAndCategorySeeder;
use Database\Seeders\CommissionTierSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InitialSuperAdminSeeder;
use Database\Seeders\ProductAndSkuSampleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-INF-MIG-002: Inisialisasi 5 Peran Standar & Hak Akses Spatie RBAC.
     */
    public function test_roles_and_permissions_seeder_creates_five_official_roles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $expectedRoles = ['super_admin', 'sales_pic', 'finance', 'logistics', 'freelance_sales'];

        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->where('guard_name', 'web')->exists(),
                "Role {$roleName} must exist in roles table."
            );
        }

        // Verify granular permissions exist
        $this->assertTrue(Permission::where('name', 'view_inquiries')->exists());
        $this->assertTrue(Permission::where('name', 'view_hpp_margin')->exists());
        $this->assertTrue(Permission::where('name', 'approve_quotation_margin')->exists());
        $this->assertTrue(Permission::where('name', 'verify_invoice_payment')->exists());
        $this->assertTrue(Permission::where('name', 'access_mitra_portal')->exists());

        // Verify Super Admin has all permissions
        $superAdmin = Role::findByName('super_admin', 'web');
        $this->assertGreaterThan(10, $superAdmin->permissions()->count());
    }

    /**
     * TC-INF-MIG-003: Inisialisasi Akun Super Admin Awal & Staf Internal.
     */
    public function test_initial_super_admin_seeder_creates_master_accounts(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CommissionTierSeeder::class);
        $this->seed(InitialSuperAdminSeeder::class);

        $admin = User::where('email', 'admin@bimabergawa.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole('super_admin'));

        // Verify Sales PIC
        $sales = User::where('email', 'sales@bimabergawa.com')->first();
        $this->assertNotNull($sales);
        $this->assertTrue($sales->hasRole('sales_pic'));

        // Verify Finance
        $finance = User::where('email', 'finance@bimabergawa.com')->first();
        $this->assertNotNull($finance);
        $this->assertTrue($finance->hasRole('finance'));

        // Verify Logistics
        $logistics = User::where('email', 'logistics@bimabergawa.com')->first();
        $this->assertNotNull($logistics);
        $this->assertTrue($logistics->hasRole('logistics'));

        // Verify Freelance Agent & Profile
        $agent = User::where('email', 'agen@bimabergawa.com')->first();
        $this->assertNotNull($agent);
        $this->assertTrue($agent->hasRole('freelance_sales'));
        $this->assertNotNull($agent->freelance_sales_id);

        $profile = FreelanceSales::where('user_id', $agent->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('approved', $profile->status);
    }

    /**
     * TC-INF-MIG-004 & TC-INF-MIG-005: Seeding 4 Brand Prinsipal Resmi & Kategori Hierarkis.
     */
    public function test_brand_and_category_seeder_creates_official_principals_and_taxonomy(): void
    {
        $this->seed(BrandAndCategorySeeder::class);

        // Verify 4 Official Principals
        $expectedBrands = ['Schneider Electric', 'Philips Lighting / Signify', 'ABB', 'Legrand'];
        foreach ($expectedBrands as $brandName) {
            $brand = Brand::where('name', $brandName)->first();
            $this->assertNotNull($brand, "Brand {$brandName} must exist.");
            $this->assertTrue($brand->is_active);
            $this->assertNotEmpty($brand->slug);
        }

        // Verify 4 Parent Categories
        $expectedParents = [
            'Distribusi Daya Listrik',
            'Otomasi & Kendali Industri',
            'Pencahayaan Industri Komersial',
            'Infrastruktur Pengkabelan & Panel',
        ];

        foreach ($expectedParents as $parentName) {
            $parent = Category::where('name', $parentName)->whereNull('parent_id')->first();
            $this->assertNotNull($parent, "Parent category {$parentName} must exist.");
            $this->assertGreaterThan(0, $parent->children()->count(), "Parent {$parentName} must have subcategories.");
        }
    }

    /**
     * TC-INF-MIG-017: Inisialisasi Pengaturan Komersial Dinamis (SystemSettingSeeder).
     */
    public function test_system_setting_seeder_initializes_commercial_and_identity_configs(): void
    {
        $this->seed(SystemSettingSeeder::class);

        // Tax & Margin
        $vatRate = SystemSetting::where('key', 'commercial_tax.tax_vat_rate')->value('value');
        $this->assertEquals('11.00', $vatRate);

        $criticalMargin = SystemSetting::where('key', 'commercial_tax.critical_margin_threshold')->value('value');
        $this->assertEquals('8.00', $criticalMargin);

        $warningMargin = SystemSetting::where('key', 'commercial_tax.warning_margin_threshold')->value('value');
        $this->assertEquals('15.00', $warningMargin);

        // Office Identity
        $companyName = SystemSetting::where('key', 'general.company_name')->value('value');
        $this->assertEquals('PT. Panca Bima Bergawa', $companyName);

        $companyAddress = SystemSetting::where('key', 'general.company_address')->value('value');
        $this->assertStringContainsString('Rasuna Said', $companyAddress);
    }

    /**
     * TC-INF-MIG-018: Inisialisasi Sample Katalog Produk Teknik Multi-Brand.
     */
    public function test_product_and_sku_sample_seeder_creates_twelve_technical_products(): void
    {
        $this->seed(BrandAndCategorySeeder::class);
        $this->seed(ProductAndSkuSampleSeeder::class);

        $this->assertEquals(12, Product::count(), 'Exactly 12 sample products must be seeded.');

        $expectedSkus = [
            'LC1D25M7', 'ATV320U55N4B', 'NSX100F-TM80D',
            'BY698P-LED200', 'BVP151-LED100', 'DN027B-LED12',
            'S203-C16', 'OT63F3', 'MS116-16',
            'P31-100X50', 'HYPRA-055533', 'MARINA-035504',
        ];

        foreach ($expectedSkus as $sku) {
            $product = Product::where('model_number', $sku)->first();
            $this->assertNotNull($product, "Product model_number {$sku} must exist.");
            $this->assertGreaterThan(0, $product->cost_price, "Product {$sku} must have a base HPP cost price.");
            $this->assertGreaterThan(0, $product->specifications()->count(), "Product {$sku} must have dynamic specifications.");
            $this->assertNotNull($product->primaryImage, "Product {$sku} must have a primary image.");
        }
    }

    /**
     * TC-INF-MIG-006 & TC-INF-MIG-007: Master Pipeline Seeder & Idempotensi.
     */
    public function test_database_seeder_runs_end_to_end_and_is_idempotent(): void
    {
        // First Run
        $this->seed(DatabaseSeeder::class);

        $rolesCount = Role::count();
        $brandsCount = Brand::count();
        $productsCount = Product::count();
        $settingsCount = SystemSetting::count();
        $tiersCount = CommissionTier::count();

        $this->assertEquals(5, $rolesCount);
        $this->assertEquals(4, $brandsCount);
        $this->assertEquals(12, $productsCount);
        $this->assertEquals(3, $tiersCount);
        $this->assertGreaterThan(10, $settingsCount);

        // Second Run (Idempotency Test)
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals($rolesCount, Role::count(), 'Role count must remain identical on second run.');
        $this->assertEquals($brandsCount, Brand::count(), 'Brand count must remain identical on second run.');
        $this->assertEquals($productsCount, Product::count(), 'Product count must remain identical on second run.');
        $this->assertEquals($settingsCount, SystemSetting::count(), 'SystemSetting count must remain identical on second run.');
        $this->assertEquals($tiersCount, CommissionTier::count(), 'CommissionTier count must remain identical on second run.');
    }

    /**
     * Verifikasi Seluruh 28 Model Factories Dapat Membuat Model Valid.
     */
    public function test_model_factories_can_instantiate_and_persist_domain_entities(): void
    {
        $brand = Brand::factory()->create();
        $this->assertDatabaseHas('brands', ['id' => $brand->id]);

        $category = Category::factory()->create();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $product = Product::factory()->create(['brand_id' => $brand->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $spec = ProductSpecification::factory()->create(['product_id' => $product->id]);
        $this->assertDatabaseHas('product_specifications', ['id' => $spec->id]);

        $img = ProductImage::factory()->create(['product_id' => $product->id]);
        $this->assertDatabaseHas('product_images', ['id' => $img->id]);

        $tier = CommissionTier::factory()->create();
        $this->assertDatabaseHas('commission_tiers', ['id' => $tier->id]);

        $agent = FreelanceSales::factory()->create();
        $this->assertDatabaseHas('freelance_sales', ['id' => $agent->id]);

        $inquiry = Inquiry::factory()->create();
        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id]);

        $inquiryItem = InquiryItem::factory()->create(['inquiry_id' => $inquiry->id]);
        $this->assertDatabaseHas('inquiry_items', ['id' => $inquiryItem->id]);

        $telemetry = InquiryTelemetry::factory()->create(['inquiry_id' => $inquiry->id]);
        $this->assertDatabaseHas('inquiry_telemetries', ['id' => $telemetry->id]);

        $quotation = Quotation::factory()->create(['inquiry_id' => $inquiry->id]);
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);

        $quoItem = QuotationItem::factory()->create(['quotation_id' => $quotation->id]);
        $this->assertDatabaseHas('quotation_items', ['id' => $quoItem->id]);

        $quoTerm = QuotationPaymentTerm::factory()->create(['quotation_id' => $quotation->id]);
        $this->assertDatabaseHas('quotation_payment_terms', ['id' => $quoTerm->id]);

        $so = SalesOrder::factory()->create(['quotation_id' => $quotation->id]);
        $this->assertDatabaseHas('sales_orders', ['id' => $so->id]);

        $do = DeliveryOrder::factory()->create(['sales_order_id' => $so->id]);
        $this->assertDatabaseHas('delivery_orders', ['id' => $do->id]);

        $doItem = DeliveryOrderItem::factory()->create(['delivery_order_id' => $do->id]);
        $this->assertDatabaseHas('delivery_order_items', ['id' => $doItem->id]);

        $invoice = Invoice::factory()->create(['sales_order_id' => $so->id, 'quotation_id' => $quotation->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);

        $invItem = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        $this->assertDatabaseHas('invoice_items', ['id' => $invItem->id]);

        $payout = CommissionPayout::factory()->create(['freelance_sales_id' => $agent->id, 'sales_order_id' => $so->id]);
        $this->assertDatabaseHas('commission_payouts', ['id' => $payout->id]);

        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $comment = InternalComment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => 'inquiry',
            'commentable_id' => $inquiry->id,
        ]);
        $this->assertDatabaseHas('internal_comments', ['id' => $comment->id]);

        $activity = ActivityLog::factory()->create([
            'subject_type' => 'quotation',
            'subject_id' => $quotation->id,
            'causer_type' => 'user',
            'causer_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['id' => $activity->id]);

        $setting = SystemSetting::factory()->create();
        $this->assertDatabaseHas('system_settings', ['id' => $setting->id]);

        $notif = Notification::factory()->create();
        $this->assertDatabaseHas('notifications', ['id' => $notif->id]);

        $seq = DocumentSequence::factory()->create();
        $this->assertDatabaseHas('document_sequences', ['id' => $seq->id]);

        $article = Article::factory()->create();
        $this->assertDatabaseHas('articles', ['id' => $article->id]);

        $uom = UnitOfMeasure::factory()->create();
        $this->assertDatabaseHas('units_of_measure', ['id' => $uom->id]);
    }

    /**
     * TC-INF-MIG-013: Validasi Enkripsi PII At-Rest & Password Hashing.
     */
    public function test_seeded_credentials_and_pii_encryption_at_rest(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CommissionTierSeeder::class);
        $this->seed(InitialSuperAdminSeeder::class);

        // Verify password hashing on seeded user
        $admin = User::where('email', 'admin@bimabergawa.com')->firstOrFail();
        $this->assertNotEquals('PBB-RasunaSaid-2026!', $admin->password);
        $this->assertTrue(Hash::check('PBB-RasunaSaid-2026!', $admin->password));

        $sales = User::where('email', 'sales@bimabergawa.com')->firstOrFail();
        $this->assertNotEquals('PBB-Staff-2026!', $sales->password);
        $this->assertTrue(Hash::check('PBB-Staff-2026!', $sales->password));

        // Verify PII encryption at rest on freelance_sales
        $agent = FreelanceSales::firstOrFail();
        $rawDbRecord = DB::table('freelance_sales')->where('id', $agent->id)->first();

        // Raw database column values MUST NOT contain plaintext
        $this->assertNotEquals('3171012345670001', $rawDbRecord->nik);
        $this->assertNotEquals('09.123.456.7-012.000', $rawDbRecord->npwp_number);
        $this->assertNotEquals('1234567890', $rawDbRecord->bank_account_number);

        // Raw database values must be encrypted strings (Laravel encrypter format)
        $this->assertStringStartsWith('eyJpdiI6', (string) $rawDbRecord->nik);
        $this->assertStringStartsWith('eyJpdiI6', (string) $rawDbRecord->npwp_number);
        $this->assertStringStartsWith('eyJpdiI6', (string) $rawDbRecord->bank_account_number);

        // Eloquent Model access MUST automatically decrypt to valid plaintext
        $this->assertEquals('3171012345670001', $agent->nik);
        $this->assertEquals('09.123.456.7-012.000', $agent->npwp_number);
        $this->assertEquals('1234567890', $agent->bank_account_number);
    }

    /**
     * TC-INF-MIG-014: Validasi Integritas Unique Index Database.
     */
    public function test_unique_indexes_enforced_by_database(): void
    {
        $brand = Brand::factory()->create(['slug' => 'unique-brand-slug']);
        $this->expectException(QueryException::class);
        Brand::factory()->create(['slug' => 'unique-brand-slug']);
    }

    /**
     * TC-INF-MIG-015: Validasi Standalone Self-Healing Seeders.
     */
    public function test_self_healing_standalone_seeder_execution(): void
    {
        // Run ProductAndSkuSampleSeeder directly on empty database
        // It must automatically invoke BrandAndCategorySeeder
        $this->seed(ProductAndSkuSampleSeeder::class);

        $this->assertEquals(4, Brand::count(), 'Brands must be automatically seeded by self-healing check.');
        $this->assertEquals(12, Product::count(), 'Products must be seeded successfully.');
    }

    /**
     * TC-INF-MIG-019: Inisialisasi Master Satuan Dinamis (Unit of Measure).
     */
    public function test_unit_of_measure_seeder_initializes_fifteen_industrial_units(): void
    {
        $this->seed(UnitOfMeasureSeeder::class);

        $this->assertEquals(15, UnitOfMeasure::count());

        $expectedCodes = [
            'pcs', 'unit', 'set', 'meter', 'batang', 'roll', 'box',
            'pack', 'pail', 'drum', 'liter', 'kg', 'pasang', 'can', 'lot',
        ];

        foreach ($expectedCodes as $code) {
            $uom = UnitOfMeasure::where('code', $code)->first();
            $this->assertNotNull($uom, "UOM code '{$code}' must exist.");
            $this->assertTrue($uom->is_active);
            $this->assertNotEmpty($uom->name);
        }
    }

    /**
     * TC-INF-MIG-020: Inisialisasi Kategori Multi-Divisi Industrial General Supplier.
     */
    public function test_brand_and_category_seeder_initializes_multi_division_categories(): void
    {
        $this->seed(BrandAndCategorySeeder::class);

        // Verify parent categories across multiple industrial divisions exist
        $this->assertTrue(Category::where('name', 'Distribusi Daya Listrik')->whereNull('parent_id')->exists());
        $this->assertTrue(Category::where('name', 'Mekanikal & Pemipaan Industri')->whereNull('parent_id')->exists());
        $this->assertTrue(Category::where('name', 'Keselamatan Kerja & K3 (HSE)')->whereNull('parent_id')->exists());
        $this->assertTrue(Category::where('name', 'Perkakas Teknik & MRO Pabrik')->whereNull('parent_id')->exists());

        // Verify specific technical sub-categories exist
        $electricalCat = Category::where('name', 'Miniature Circuit Breaker (MCB)')->first();
        $mechanicalCat = Category::where('name', 'Industrial Valves (Ball, Butterfly, Gate, Globe)')->first();
        $safetyCat = Category::where('name', 'Sepatu Safety & Boot K3')->first();
        $toolsCat = Category::where('name', 'Hand Tools & Kunci Mekanik')->first();

        $this->assertNotNull($electricalCat, 'Electrical category must exist.');
        $this->assertNotNull($mechanicalCat, 'Mechanical & Piping category must exist.');
        $this->assertNotNull($safetyCat, 'Safety & HSE category must exist.');
        $this->assertNotNull($toolsCat, 'Tools & MRO category must exist.');
    }

    /**
     * TC-INF-MIG-021: Validasi Integritas Atribut B2B Industrial & Kustomisasi Spesifikasi per Item.
     */
    public function test_b2b_industrial_attributes_and_specifications_integrity(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Verify product B2B industrial fields
        $product = Product::firstOrFail();
        $this->assertNotEmpty($product->uom);
        $this->assertGreaterThanOrEqual(1, $product->moq);
        $this->assertNotEmpty($product->warranty_period);

        // Verify dynamic specification columns on ProductSpecification
        $spec = ProductSpecification::where('product_id', $product->id)->firstOrFail();
        $this->assertNotEmpty($spec->spec_group);
        $this->assertIsBool($spec->is_highlight);

        // Verify per-item custom specifications and industrial fields on InquiryItem & QuotationItem
        $inquiry = Inquiry::factory()->create(['project_name' => 'Proyek Ekspansi Fasilitas Fabrikasi Cikarang']);
        $this->assertEquals('Proyek Ekspansi Fasilitas Fabrikasi Cikarang', $inquiry->project_name);

        $customSpecs = [
            ['label' => 'Tekanan Operasional', 'value' => '16 Bar (PN16)'],
            ['label' => 'Koneksi Flange', 'value' => 'ANSI 150# RF'],
        ];

        $inquiryItem = InquiryItem::factory()->create([
            'inquiry_id' => $inquiry->id,
            'uom' => 'unit',
            'custom_specifications' => $customSpecs,
        ]);
        $this->assertEquals('unit', $inquiryItem->uom);
        $this->assertIsArray($inquiryItem->custom_specifications);
        $this->assertCount(2, $inquiryItem->custom_specifications);
        $this->assertEquals('Tekanan Operasional', $inquiryItem->custom_specifications[0]['label']);

        // QuotationItem
        $quotation = Quotation::factory()->create([
            'inquiry_id' => $inquiry->id,
            'project_name' => 'Proyek Ekspansi Fasilitas Fabrikasi Cikarang',
        ]);
        $this->assertEquals('Proyek Ekspansi Fasilitas Fabrikasi Cikarang', $quotation->project_name);

        $quoItem = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'uom' => 'unit',
            'warranty_period' => '24 Bulan Garansi Resmi Pabrik',
            'custom_specifications' => $customSpecs,
        ]);
        $this->assertEquals('unit', $quoItem->uom);
        $this->assertEquals('24 Bulan Garansi Resmi Pabrik', $quoItem->warranty_period);
        $this->assertIsArray($quoItem->custom_specifications);

        // SalesOrder
        $so = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'project_name' => 'Proyek Ekspansi Fasilitas Fabrikasi Cikarang',
        ]);
        $this->assertEquals('Proyek Ekspansi Fasilitas Fabrikasi Cikarang', $so->project_name);

        // DeliveryOrder
        $do = DeliveryOrder::factory()->create([
            'sales_order_id' => $so->id,
            'driver_name' => 'Bambang Supriyanto',
            'vehicle_plate_number' => 'B 9876 PBB',
        ]);
        $this->assertEquals('Bambang Supriyanto', $do->driver_name);
        $this->assertEquals('B 9876 PBB', $do->vehicle_plate_number);

        $doItem = DeliveryOrderItem::factory()->create([
            'delivery_order_id' => $do->id,
            'uom' => 'unit',
        ]);
        $this->assertEquals('unit', $doItem->uom);

        // Invoice
        $inv = Invoice::factory()->create([
            'sales_order_id' => $so->id,
            'quotation_id' => $quotation->id,
            'company_npwp' => '01.234.567.8-012.000',
            'faktur_pajak_number' => '010.001-26.00000001',
        ]);
        $this->assertEquals('01.234.567.8-012.000', $inv->company_npwp);
        $this->assertEquals('010.001-26.00000001', $inv->faktur_pajak_number);

        $invItem = InvoiceItem::factory()->create([
            'invoice_id' => $inv->id,
            'uom' => 'unit',
        ]);
        $this->assertEquals('unit', $invItem->uom);
    }
}
