<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\BrandAndCategorySeeder;
use Database\Seeders\CommissionTierSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InitialSuperAdminSeeder;
use Database\Seeders\ProductAndSkuSampleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     * Verifikasi Model Factories Dapat Membuat Model Valid.
     */
    public function test_model_factories_can_instantiate_and_persist_domain_entities(): void
    {
        $brand = Brand::factory()->create();
        $this->assertDatabaseHas('brands', ['id' => $brand->id]);

        $category = Category::factory()->create();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $product = Product::factory()->create(['brand_id' => $brand->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $tier = CommissionTier::factory()->create();
        $this->assertDatabaseHas('commission_tiers', ['id' => $tier->id]);

        $agent = FreelanceSales::factory()->create();
        $this->assertDatabaseHas('freelance_sales', ['id' => $agent->id]);

        $inquiry = Inquiry::factory()->create();
        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id]);

        $quotation = Quotation::factory()->create(['inquiry_id' => $inquiry->id]);
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);

        $so = SalesOrder::factory()->create(['quotation_id' => $quotation->id]);
        $this->assertDatabaseHas('sales_orders', ['id' => $so->id]);

        $article = Article::factory()->create();
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }
}
