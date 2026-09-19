# 02. Master Data Prinsipal, Taksonomi Kategori, & Atribut Teknis
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-02` |
| **Versi Dokumen** | 1.6.0 |
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
| `1.5.0` | 19 Sep 2026 | Catalog Architect & Lead | Disetujui | Sinkronisasi skema tabel brands dan categories (menghapus kolom non-skema country_origin dan is_active), serta dokumentasi normalisasi product_specifications |
| `1.6.0` | 19 Sep 2026 | Catalog Architect & Lead | Disetujui | Penyelarasan taksonomi multi-divisi industri General Supplier (Elektrikal, Mekanikal & Pemipaan, Otomasi, Safety K3 HSE, MRO Tools, Pencahayaan) dan atribut UOM, MOQ, Garansi |

---

## 1. Seeder Prinsipal & Taksonomi Kategori Hierarkis (`BrandAndCategorySeeder.php`)

Mendaftarkan 4 prinsipal kemitraan resmi global beserta taksonomi kategori 2 tingkat (induk dan sub-kategori teknis):

```php
namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandAndCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi 4 Prinsipal Kemitraan Resmi PT. Panca Bima Bergawa
        $brands = [
            [
                'name' => 'Schneider Electric',
                'description' => 'Distribusi tenaga listrik, MCB, MCCB, ACB, kontaktor TeSys, inverter Altivar, sensor telemecanique, dan relay otomasi.',
                'logo_path' => 'brands/schneider-electric.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Philips Lighting / Signify',
                'description' => 'Pencahayaan industri komersial, high-bay LED, lampu sorot stadion/proyek, downlight architectural, dan armatur tahan ledakan.',
                'logo_path' => 'brands/philips-lighting.webp',
                'is_active' => true,
            ],
            [
                'name' => 'ABB',
                'description' => 'Sakelar pemutus beban, isolator switch, motor starter, miniature circuit breaker, dan sistem proteksi kelistrikan industri berat.',
                'logo_path' => 'brands/abb.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Legrand',
                'description' => 'Sistem pengkabelan terstruktur, kabel tray, stop kontak industri komersial, box panel industri, dan modul otomasi gedung.',
                'logo_path' => 'brands/legrand.webp',
                'is_active' => true,
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
                    'slug' => Str::slug($parentName),
                    'parent_id' => null,
                    'description' => "Kategori induk perlengkapan {$parentName}.",
                ]
            );

            foreach ($subCategories as $subName) {
                Category::firstOrCreate(
                    ['name' => $subName],
                    [
                        'slug' => Str::slug($subName),
                        'parent_id' => $parent->id,
                        'description' => "Sub-kategori teknis {$subName}.",
                    ]
                );
            }
        }
    }
}
```

---

## 2. Normalisasi Atribut Spesifikasi Teknis (`product_specifications`)

Atribut spesifikasi teknis dinamis tidak disimpan dalam kolom blob JSON tunggal, melainkan dinormalisasi ke tabel terindeks `product_specifications` untuk mendukung faceted search dan live filter berkecepatan tinggi:

| Kolom Tabel | Tipe Data | Keterangan & Atribut |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `product_id` | BIGINT UNSIGNED | Foreign Key ke `products.id` (ON DELETE CASCADE) |
| `spec_key` | VARCHAR(100) | Kunci Atribut (misal: *Rated Current, Ingress Protection*) |
| `spec_value` | VARCHAR(255) | Nilai Spesifikasi (misal: *25 Ampere, IP65*) |
| `sort_order` | INTEGER | Urutan Tampilan Spesifikasi (Default: 0) |

Indeks Komposit: `['product_id', 'sort_order']` bernama `idx_prod_specs_order`.
