# 02. Master Data Prinsipal, Taksonomi Kategori, & Atribut Teknis
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Product Specialist | Draf Awal | Seeder brand prinsipal resmi & kategori produk dasar |
| `1.1.0` | 03 Sep 2026 | Catalog Architect | Ditinjau | Penambahan struktur hierarki parent-child & spesifikasi JSON |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan skema atribut teknis per kategori industri |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri taksonomi master data & zero emoji |

---

## 1. Seeder Brand Prinsipal Resmi (`BrandMasterSeeder.php`)

Mendaftarkan prinsipal manufaktur resmi yang didistribusikan oleh PT. Panca Bima Bergawa:

```php
namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandMasterSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name'         => 'Ebara',
                'country_origin' => 'Jepang',
                'description'  => 'Produsen global terkemuka untuk pompa sentrifugal, pompa submersible, dan sistem perpompaan industri berat.',
                'logo_url'     => 'brands/ebara-logo.webp',
                'is_active'    => true,
            ],
            [
                'name'         => 'Schneider Electric',
                'country_origin' => 'Prancis',
                'description'  => 'Pemimpin global dalam transformasi digital manajemen energi dan otomatisasi industri (Inverter Altivar, Kontaktor, PLC).',
                'logo_url'     => 'brands/schneider-logo.webp',
                'is_active'    => true,
            ],
            [
                'name'         => 'Danfoss',
                'country_origin' => 'Denmark',
                'description'  => 'Solusi efisiensi energi terdepan di dunia untuk Variable Frequency Drive (VFD VLT Series) dan refrigerasi industri.',
                'logo_url'     => 'brands/danfoss-logo.webp',
                'is_active'    => true,
            ],
            [
                'name'         => 'Omron',
                'country_origin' => 'Jepang',
                'description'  => 'Komponen otomasi pabrik presisi tinggi meliputi proximity sensor, photoelectric sensor, timer, dan safety relays.',
                'logo_url'     => 'brands/omron-logo.webp',
                'is_active'    => true,
            ],
            [
                'name'         => 'Mitsubishi Electric',
                'country_origin' => 'Jepang',
                'description'  => 'Peralatan otomasi industri kelas dunia mencakup PLC Seri Melsec, Servo Motors, dan Inverter Seri FR-D/E/A.',
                'logo_url'     => 'brands/mitsubishi-logo.webp',
                'is_active'    => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                array_merge($brand, ['slug' => Str::slug($brand['name'])])
            );
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
