# 01. Pipeline Database Seeder, Peran Spatie RBAC, & Pengaturan Awal
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Database Administrator | Draf Awal | Inisialisasi seeder peran pengguna dasar |
| `1.1.0` | 03 Sep 2026 | Security Specialist | Ditinjau | Penambahan izin granular Spatie & seeder tier komisi |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Seeder profil perusahaan Rasuna Said & akun master |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri pipeline seeder & zero emoji |

---

## 1. Urutan Eksekusi Pipeline Seeder (`DatabaseSeeder.php`)

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            InitialSuperAdminSeeder::class,
            CommissionTierSeeder::class,
            BrandMasterSeeder::class,
            CategoryTreeSeeder::class,
            SiteSettingsSeeder::class,
            ArticleCategorySeeder::class,
        ]);
    }
}
```

---

## 2. Inisialisasi Peran & Hak Akses Spatie RBAC (`RolesAndPermissionsSeeder.php`)

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache izin Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisi Hak Akses (Permissions)
        $permissions = [
            'view_inquiries', 'manage_inquiries', 'assign_inquiries',
            'create_quotations', 'view_quotations', 'view_hpp_margin', 'approve_quotations',
            'create_sales_orders', 'view_sales_orders',
            'create_delivery_orders', 'view_delivery_orders',
            'manage_products', 'import_products',
            'view_financial_reports', 'process_payouts',
            'manage_partners', 'access_mitra_portal',
            'manage_system_settings', 'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Definisi Peran (Roles) & Asosiasi Izin
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $roleSuperAdmin->syncPermissions(Permission::all());

        $roleSales = Role::firstOrCreate(['name' => 'sales_representative', 'guard_name' => 'web']);
        $roleSales->syncPermissions([
            'view_inquiries', 'manage_inquiries',
            'create_quotations', 'view_quotations', 'view_hpp_margin',
            'create_sales_orders', 'view_sales_orders',
            'view_delivery_orders', 'manage_products',
        ]);

        $roleFinance = Role::firstOrCreate(['name' => 'finance_officer', 'guard_name' => 'web']);
        $roleFinance->syncPermissions([
            'view_inquiries', 'view_quotations',
            'view_sales_orders', 'view_delivery_orders',
            'view_financial_reports', 'process_payouts',
        ]);

        $roleLogistics = Role::firstOrCreate(['name' => 'logistics_officer', 'guard_name' => 'web']);
        $roleLogistics->syncPermissions([
            'view_sales_orders', 'create_delivery_orders', 'view_delivery_orders',
        ]);

        $roleMitra = Role::firstOrCreate(['name' => 'freelance_agent', 'guard_name' => 'web']);
        $roleMitra->syncPermissions([
            'access_mitra_portal',
        ]);
    }
}
```

---

## 3. Akun Master Super Admin (`InitialSuperAdminSeeder.php`)

```php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bimabergawa.com'],
            [
                'name'              => 'Direksi PT. Panca Bima Bergawa',
                'phone_wa'          => '081234567890',
                'password'          => Hash::make(config('app.initial_admin_password', 'PBB-Prod-Secure-2026!')),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}
```

---

## 4. Inisialisasi Jenjang Komisi Freelance (`CommissionTierSeeder.php`)

```php
namespace Database\Seeders;

use App\Models\CommissionTier;
use Illuminate\Database\Seeder;

class CommissionTierSeeder extends Seeder
{
    public function run(): void
    {
        CommissionTier::firstOrCreate(
            ['tier_level' => 1],
            [
                'tier_name'             => 'Silver Agent',
                'min_monthly_turnover'  => 0,
                'max_monthly_turnover'  => 99999999,
                'commission_percentage' => 2.00,
                'is_active'             => true,
            ]
        );

        CommissionTier::firstOrCreate(
            ['tier_level' => 2],
            [
                'tier_name'             => 'Gold Agent',
                'min_monthly_turnover'  => 100000000,
                'max_monthly_turnover'  => 499999999,
                'commission_percentage' => 3.00,
                'is_active'             => true,
            ]
        );

        CommissionTier::firstOrCreate(
            ['tier_level' => 3],
            [
                'tier_name'             => 'Platinum Agent',
                'min_monthly_turnover'  => 500000000,
                'max_monthly_turnover'  => null,
                'commission_percentage' => 4.00,
                'is_active'             => true,
            ]
        );
    }
}
```

---

## 5. Metadata Profil Perusahaan (`SiteSettingsSeeder.php`)

```php
namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name'      => 'PT. Panca Bima Bergawa',
            'company_tagline'   => 'Distributor Mesin & Otomasi Industri Multi-Brand Terpercaya',
            'company_address'   => 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920',
            'contact_email'     => 'sales@bimabergawa.com',
            'contact_phone'     => '+62 21-XXXX-XXXX',
            'contact_whatsapp'  => '081234567890',
            'tax_vat_rate'      => 11.0,
            'quote_validity_days' => 14,
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
```
