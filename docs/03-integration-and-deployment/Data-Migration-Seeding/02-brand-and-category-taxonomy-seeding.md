# 02. Master Data Prinsipal, Taksonomi Kategori, & Atribut Teknis
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-02` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Product Specialist | Draf Awal | Seeder brand prinsipal resmi & kategori produk dasar |
| `1.1.0` | 03 Sep 2026 | Catalog Architect | Ditinjau | Penambahan struktur hierarki parent-child & spesifikasi JSON |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan skema atribut teknis per kategori industri |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri taksonomi master data & zero emoji |
| `1.4.0` | 19 Sep 2026 | Catalog Architect & Lead | Disetujui | Penyelarasan 4 prinsipal global kemitraan resmi PBB (Schneider, Philips, ABB, Legrand) & kategori kelistrikan-otomasi industri |

---

## 1. Seeder Brand Prinsipal Resmi (`BrandMasterSeeder.php`)

Mendaftarkan prinsipal manufaktur resmi yang didistribusikan oleh PT. Panca Bima Bergawa:

```php
namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandAndCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi 4 Prinsipal Kemitraan Resmi PT. Panca Bima Bergawa
        $brands = [
            [
                'name'           => 'Schneider Electric',
                'country_origin' => 'Prancis',
                'description'    => 'Distribusi tenaga listrik, MCB, MCCB, ACB, kontaktor TeSys, inverter Altivar, sensor telemecanique, dan relay otomasi.',
                'logo_path'      => 'brands/schneider-electric.webp',
                'is_active'      => true,
            ],
            [
                'name'           => 'Philips Lighting / Signify',
                'country_origin' => 'Belanda',
                'description'    => 'Pencahayaan industri komersial, high-bay LED, lampu sorot stadion/proyek, downlight architectural, dan armatur tahan ledakan.',
                'logo_path'      => 'brands/philips-lighting.webp',
                'is_active'      => true,
            ],
            [
                'name'           => 'ABB',
                'country_origin' => 'Swiss',
                'description'    => 'Sakelar pemutus beban, isolator switch, motor starter, miniature circuit breaker, dan sistem proteksi kelistrikan industri berat.',
                'logo_path'      => 'brands/abb.webp',
                'is_active'      => true,
            ],
            [
                'name'           => 'Legrand',
                'country_origin' => 'Prancis',
                'description'    => 'Sistem pengkabelan terstruktur, kabel tray, stop kontak industri komersial, box panel industri, dan modul otomasi gedung.',
                'logo_path'      => 'brands/legrand.webp',
                'is_active'      => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                array_merge($brand, ['slug' => Str::slug($brand['name'])])
            );
        }

        // 2. Inisialisasi Taksonomi Kategori Hierarkis
        $taxonomy = [
            'Distribusi Daya Listrik' => [
                'Miniature Circuit Breaker (MCB)',
                'Molded Case Circuit Breaker (MCCB)',
                'Air Circuit Breaker (ACB)',
                'Kapasitor Bank & Power Quality',
            ],
            'Otomasi & Kendali Industri' => [
                'Variable Frequency Drive (Inverter VFD)',
                'Kontaktor & Thermal Overload Relay',
                'Programmable Logic Controller (PLC)',
                'Sensor & Limit Switch Industri',
            ],
            'Pencahayaan Industri Komersial' => [
                'High-Bay LED Industri',
                'Lampu Sorot & Floodlight Proyek',
                'Architectural Downlight',
                'Armatur Tahan Ledakan (Explosion-Proof)',
            ],
            'Infrastruktur Pengkabelan & Panel' => [
                'Kabel Tray & Cable Ladder',
                'Box Panel Industri IP66',
                'Stop Kontak & Steker Industri',
            ],
        ];

        foreach ($taxonomy as $parentName => $subCategories) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                [
                    'slug'        => Str::slug($parentName),
                    'parent_id'   => null,
                    'description' => "Kategori induk perlengkapan {$parentName}.",
                    'is_active'   => true,
                ]
            );

            foreach ($subCategories as $subName) {
                Category::firstOrCreate(
                    ['name' => $subName],
                    [
                        'slug'        => Str::slug($subName),
                        'parent_id'   => $parent->id,
                        'description' => "Sub-kategori teknis {$subName}.",
                        'is_active'   => true,
                    ]
                );
            }
        }
    }
}
```

---

## 2. Seeder Pohon Kategori Produk Hierarkis (`CategoryTreeSeeder.php`)

```php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTreeSeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = [
            'Pompa Industri' => [
                'End Suction Centrifugal Pump',
                'Vertical Multistage Pump',
                'Submersible Sewage & Drainage Pump',
                'Split Casing Pump',
            ],
            'Inverter & Speed Drive (VFD)' => [
                'Micro Drive (Compact VFD)',
                'Standard Industrial Drive',
                'HVAC & Pump Dedicated Drive',
                'Heavy Duty Industrial Drive',
            ],
            'Sensor, PLC & Otomasi Pabrik' => [
                'Inductive Proximity Sensor',
                'Photoelectric Sensor',
                'Programmable Logic Controller (PLC)',
                'Human Machine Interface (HMI)',
            ],
            'Valve, Actuator & Perpipaan' => [
                'Cast Iron Gate Valve',
                'Stainless Steel Ball Valve',
                'Butterfly Valve & Actuator',
                'Check Valve & Strainer',
            ],
        ];

        foreach ($taxonomy as $parentName => $subCategories) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                [
                    'slug'        => Str::slug($parentName),
                    'parent_id'   => null,
                    'description' => "Kategori induk perlengkapan {$parentName}.",
                    'is_active'   => true,
                ]
            );

            foreach ($subCategories as $subName) {
                Category::firstOrCreate(
                    ['name' => $subName],
                    [
                        'slug'        => Str::slug($subName),
                        'parent_id'   => $parent->id,
                        'description' => "Sub-kategori teknis {$subName}.",
                        'is_active'   => true,
                    ]
                );
            }
        }
    }
}
```

---

## 3. Kamus Atribut Spesifikasi Teknis Dinamis (`technical_specs` JSON)

Setiap kategori produk memiliki kelompok atribut spesifikasi standar yang tersimpan secara fleksibel dalam kolom `technical_specs` bertipe JSON:

| Kategori Produk | Kunci Atribut JSON | Tipe Data & Satuan | Contoh Nilai |
| :--- | :--- | :--- | :--- |
| **Pompa Industri** | `flow_rate` | String ($\text{m}^3/\text{jam}$) | `50 m3/h` |
| | `head` | String ($\text{Meter}$) | `45 Meter` |
| | `motor_power` | String ($\text{kW} / \text{HP}$) | `11 kW (15 HP)` |
| | `casing_material` | String | `Cast Iron FC200` |
| | `flange_standard` | String | `DIN PN16 / ANSI 150` |
| **Inverter & VFD** | `power_rating` | String ($\text{kW}$) | `5.5 kW (7.5 HP)` |
| | `input_voltage` | String ($\text{Volt}$) | `380 - 480V 3-Phase` |
| | `rated_current` | String ($\text{Ampere}$) | `14.3 A` |
| | `protection_rating`| String | `IP20` |
| | `communication` | String | `Modbus RTU / Profinet` |
| **Sensor & Otomasi** | `sensing_distance`| String ($\text{mm}$) | `7 mm` |
| | `output_type` | String | `NPN NO / PNP NC` |
| | `operating_voltage`| String | `10 - 30 VDC` |
| | `body_diameter` | String | `M18 Shielded` |
