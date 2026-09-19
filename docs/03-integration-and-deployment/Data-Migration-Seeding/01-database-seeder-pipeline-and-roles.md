# 01. Pipeline Database Seeder, Peran Spatie RBAC, & Pengaturan Awal
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-01` |
| **Versi Dokumen** | 1.6.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Database Administrator | Draf Awal | Inisialisasi seeder peran pengguna dasar |
| `1.1.0` | 03 Sep 2026 | Security Specialist | Ditinjau | Penambahan izin granular Spatie & seeder tier komisi |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Seeder profil perusahaan Rasuna Said & akun master |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri pipeline seeder & zero emoji |
| `1.4.0` | 19 Sep 2026 | Principal Integration Engineer | Disetujui | Penyelarasan 5 peran resmi Spatie RBAC (super_admin, sales_pic, finance, logistics, freelance_sales), SystemSettingSeeder, & ProductAndSkuSampleSeeder |
| `1.5.0` | 19 Sep 2026 | Lead Database Architect | Disetujui | Penyelarasan urutan pipeline master, sinkronisasi skema commission_tiers, users (phone), akun staf uji coba, dan mitigasi seeder mandiri |
| `1.6.0` | 19 Sep 2026 | Lead Database Architect | Disetujui | Penambahan UnitOfMeasureSeeder (15 Master Satuan Industri Indonesia) dan integrasi pipeline DatabaseSeeder |

---

## 1. Urutan Eksekusi Pipeline Seeder (`DatabaseSeeder.php`)

Pipeline master seeder dieksekusi secara terurut untuk menjamin ketiadaan pelanggaran ketergantungan kunci asing (*foreign key dependency*):

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CommissionTierSeeder::class,
            InitialSuperAdminSeeder::class,
            UnitOfMeasureSeeder::class,
            BrandAndCategorySeeder::class,
            SystemSettingSeeder::class,
            ArticleCategorySeeder::class,
            ProductAndSkuSampleSeeder::class,
        ]);
    }
}
```

---

## 2. Definisi Peran & Hak Akses Spatie RBAC (`RoleAndPermissionSeeder.php`)

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache izin Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisi Hak Akses Granular (Permissions)
        $permissions = [
            // Inquiries & CRM
            'view_inquiries', 'manage_inquiries', 'assign_inquiries',
            // Quotations & Pricing
            'create_quotations', 'view_quotations', 'view_hpp_margin', 'approve_quotations', 'approve_quotation_margin',
            // Sales Orders
            'create_sales_orders', 'view_sales_orders', 'approve_high_value_so',
            // Delivery Orders & Logistics
            'create_delivery_orders', 'view_delivery_orders', 'dispatch_delivery_orders',
            // Invoices & Billing
            'manage_invoices', 'view_invoices', 'verify_invoice_payment',
            // Products & Brands
            'manage_products', 'import_products', 'view_cost_price',
            // Financials & Commissions
            'view_financial_reports', 'process_payouts',
            // Partners & Portal
            'manage_partners', 'access_mitra_portal',
            // System & Audit
            'manage_system_settings', 'manage_commercial_tax', 'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Definisi 5 Peran Resmi Spatie RBAC (Sesuai AGENTS.md Tabel 4)
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $roleSuperAdmin->syncPermissions(Permission::all());

        $roleSales = Role::firstOrCreate(['name' => 'sales_pic', 'guard_name' => 'web']);
        $roleSales->syncPermissions([
            'view_inquiries', 'manage_inquiries', 'assign_inquiries',
            'create_quotations', 'view_quotations', 'view_hpp_margin',
            'create_sales_orders', 'view_sales_orders',
            'view_delivery_orders', 'view_invoices',
            'manage_products', 'import_products', 'view_cost_price',
        ]);

        $roleFinance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $roleFinance->syncPermissions([
            'view_inquiries', 'view_quotations', 'view_hpp_margin',
            'view_sales_orders', 'view_delivery_orders',
            'manage_invoices', 'view_invoices', 'verify_invoice_payment',
            'view_financial_reports', 'process_payouts', 'manage_commercial_tax',
        ]);

        $roleLogistics = Role::firstOrCreate(['name' => 'logistics', 'guard_name' => 'web']);
        $roleLogistics->syncPermissions([
            'view_sales_orders', 'create_delivery_orders', 'view_delivery_orders', 'dispatch_delivery_orders',
        ]);

        $roleMitra = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);
        $roleMitra->syncPermissions([
            'access_mitra_portal',
        ]);
    }
}
```

---

## 3. Akun Master Super Admin & Staf Internal (`InitialSuperAdminSeeder.php`)

Menginisialisasi akun Super Admin resmi Direksi PBB beserta akun uji coba staf internal dan mitra agen dengan profil terenkripsi at-rest UU PDP No. 27/2022:

```php
namespace Database\Seeders;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InitialSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Proteksi self-healing jika dipanggil mandiri
        if (Role::count() === 0) {
            $this->call(RoleAndPermissionSeeder::class);
        }
        if (CommissionTier::count() === 0) {
            $this->call(CommissionTierSeeder::class);
        }

        // 1. Super Admin Bootstrap User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@bimabergawa.com'],
            [
                'name' => 'Direksi PT. Panca Bima Bergawa',
                'phone' => '081234567890',
                'password' => Hash::make(config('app.initial_admin_password', 'PBB-RasunaSaid-2026!')),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // 2. Akun Uji Coba Staf Internal
        $staffPassword = Hash::make(config('app.initial_staff_password', 'PBB-Staff-2026!'));

        $salesUser = User::firstOrCreate(
            ['email' => 'sales@bimabergawa.com'],
            [
                'name' => 'Ahmad Fauzi (Sales PIC)',
                'phone' => '081299887766',
                'password' => $staffPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $salesUser->syncRoles(['sales_pic']);

        $financeUser = User::firstOrCreate(
            ['email' => 'finance@bimabergawa.com'],
            [
                'name' => 'Budi Santoso (Finance)',
                'phone' => '081311223344',
                'password' => $staffPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $financeUser->syncRoles(['finance']);

        $logisticsUser = User::firstOrCreate(
            ['email' => 'logistics@bimabergawa.com'],
            [
                'name' => 'Bayu Saputra (Logistik)',
                'phone' => '081355667788',
                'password' => $staffPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $logisticsUser->syncRoles(['logistics']);

        // 3. Akun Mitra Agen Freelance & Profil Terenkripsi UU PDP
        $agentUser = User::firstOrCreate(
            ['email' => 'agen@bimabergawa.com'],
            [
                'name' => 'Hendra Nugraha (Mitra Agen)',
                'phone' => '081377889900',
                'password' => $staffPassword,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $agentUser->syncRoles(['freelance_sales']);

        $defaultTierId = CommissionTier::where('name', 'Silver Agent')->value('id') ?? CommissionTier::first()?->id;

        $agentProfile = FreelanceSales::firstOrCreate(
            ['user_id' => $agentUser->id],
            [
                'commission_tier_id' => $defaultTierId,
                'name' => $agentUser->name,
                'referral_code' => 'REF-PBB-001',
                'nik' => '3171012345670001',
                'npwp_number' => '09.123.456.7-012.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Hendra Nugraha',
                'industry_focus' => 'Kontraktor Listrik & Otomasi Pabrik',
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        if ($agentUser->freelance_sales_id === null) {
            $agentUser->update(['freelance_sales_id' => $agentProfile->id]);
        }
    }
}
```

---

## 4. Inisialisasi Jenjang Komisi Freelance (`CommissionTierSeeder.php`)

Sesuai dengan kamus data resmi `DOC-PBB-SDD-01 Bagian 2.3` (tabel `commission_tiers`):

```php
namespace Database\Seeders;

use App\Models\CommissionTier;
use Illuminate\Database\Seeder;

class CommissionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Silver Agent',
                'min_turnover_amount' => 0.00,
                'commission_rate' => 2.50,
                'is_active' => true,
            ],
            [
                'name' => 'Gold Agent',
                'min_turnover_amount' => 100000000.00,
                'commission_rate' => 3.50,
                'is_active' => true,
            ],
            [
                'name' => 'Platinum Agent',
                'min_turnover_amount' => 500000000.00,
                'commission_rate' => 5.00,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            CommissionTier::firstOrCreate(
                ['name' => $tier['name']],
                $tier
            );
        }
    }
}
```

---

## 5. Metadata Profil Perusahaan & Pengaturan Komersial (`SystemSettingSeeder.php`)

```php
namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Identitas & Umum (group: general)
            ['key' => 'general.company_name', 'value' => 'PT. Panca Bima Bergawa', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.company_tagline', 'value' => 'Distributor Resmi Elektrikal, Mekanikal & Otomasi Industri', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.company_address', 'value' => 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920', 'group' => 'general', 'type' => 'text', 'is_public' => true],
            ['key' => 'general.contact_email', 'value' => 'sales@bimabergawa.com', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.contact_phone', 'value' => '+62 21 5292 0000', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.contact_whatsapp', 'value' => '6281112345678', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.default_timezone', 'value' => 'Asia/Jakarta', 'group' => 'general', 'type' => 'string', 'is_public' => false],
            ['key' => 'general.date_format', 'value' => 'd/m/Y', 'group' => 'general', 'type' => 'string', 'is_public' => false],

            // Komersial & Pajak Dinamis UU HPP (group: commercial_tax)
            ['key' => 'commercial_tax.tax_vat_rate', 'value' => '11.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.critical_margin_threshold', 'value' => '8.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.warning_margin_threshold', 'value' => '15.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.director_approval_limit', 'value' => '100000000.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.quote_validity_days', 'value' => '14', 'group' => 'commercial_tax', 'type' => 'integer', 'is_public' => false],

            // CRM & Sales SLA (group: crm_sales)
            ['key' => 'crm_sales.inquiry_stale_hours', 'value' => '24', 'group' => 'crm_sales', 'type' => 'integer', 'is_public' => false],
            ['key' => 'crm_sales.whatsapp_quotation_template', 'value' => 'Halo {pic_name}, terima kasih telah menghubungi PT. Panca Bima Bergawa. Berikut kami lampirkan dokumen penawaran resmi {quote_number}.', 'group' => 'crm_sales', 'type' => 'text', 'is_public' => false],

            // Logistik & Gudang (group: logistics)
            ['key' => 'logistics.default_warehouse_origin', 'value' => 'Gudang Pusat Rasuna Said, Jakarta Selatan', 'group' => 'logistics', 'type' => 'string', 'is_public' => false],
            ['key' => 'logistics.authorized_couriers', 'value' => json_encode(['Dakota Cargo', 'Baraka Sarana Tama', 'JNE Trucking', 'Kurir Internal PBB']), 'group' => 'logistics', 'type' => 'json', 'is_public' => false],

            // Konten CMS Beranda (group: cms_content)
            ['key' => 'cms.hero_headline', 'value' => 'Distributor Resmi Elektrikal, Mekanikal & Otomasi Industri', 'group' => 'cms_content', 'type' => 'string', 'is_public' => true],
            ['key' => 'cms.hero_subheadline', 'value' => 'Menyediakan solusi distribusi Schneider Electric, Philips, ABB, dan Legrand untuk kebutuhan proyek komersial, gedung, dan manufaktur di seluruh Indonesia.', 'group' => 'cms_content', 'type' => 'text', 'is_public' => true],
            ['key' => 'cms.stats_counter', 'value' => json_encode([
                ['number' => '4', 'label' => 'Prinsipal Global Resmi'],
                ['number' => '2500+', 'label' => 'SKU Siap Kirim'],
                ['number' => '500+', 'label' => 'Klien Korporat & Proyek'],
                ['number' => '100%', 'label' => 'Keaslian Terjamin'],
            ]), 'group' => 'cms_content', 'type' => 'json', 'is_public' => true],

            // Halaman Legalitas (group: legal_page)
            ['key' => 'legal.terms_and_conditions', 'value' => 'Syarat dan Ketentuan resmi transaksi komersial B2B PT. Panca Bima Bergawa.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
            ['key' => 'legal.privacy_policy', 'value' => 'Kebijakan Privasi PT. Panca Bima Bergawa berlandaskan kepatuhan UU Perlindungan Data Pribadi No. 27/2022.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
            ['key' => 'legal.warranty_policy', 'value' => 'Kebijakan Garansi Resmi Manufaktur Prinsipal Schneider, Philips, ABB, dan Legrand.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
```
