# 02. Master Data Prinsipal, Taksonomi Kategori, & Atribut Teknis
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-02` |
| **Versi Dokumen** | 1.7.0 |
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
| `1.7.0` | 19 Sep 2026 | Catalog Architect & Lead | Disetujui | Penambahan 18 prinsipal kemitraan resmi global (Elektrikal, Mekanikal, Pompa, Otomasi, APD K3, MRO Tools, Bahan Kimia Industri) & spesifikasi dinamis |

---

## 1. Seeder Prinsipal & Taksonomi Kategori Hierarkis (`BrandAndCategorySeeder.php`)

Mendaftarkan 18 prinsipal kemitraan resmi global beserta taksonomi kategori 2 tingkat (induk dan sub-kategori teknis):
- **Elektrikal & Tenaga Listrik:** Schneider Electric, ABB, Legrand, Chint.
- **Pencahayaan Industri & Komersial:** Philips Lighting / Signify.
- **Mekanikal, Pompa, & Pemipaan:** Kitz, Grundfos, Torishima, Rucika.
- **Otomasi Pabrik, Kontrol, & Sensor:** Omron, Mitsubishi Electric.
- **Peralatan Keselamatan Kerja, Safety & K3 (HSE):** 3M, Cheetah, Krisbow Safety.
- **Perkakas Teknik & MRO Pabrik (Maintenance, Repair, Operations):** Bosch, Tekiro, Loctite, WD-40.

---

## 2. Normalisasi Atribut Spesifikasi Teknis (`product_specifications`)

Atribut spesifikasi teknis dinamis dinormalisasi ke tabel terindeks `product_specifications` untuk mendukung faceted search dan live filter berkecepatan tinggi:

| Kolom Tabel | Tipe Data | Keterangan & Atribut |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `product_id` | BIGINT UNSIGNED | Foreign Key ke `products.id` (ON DELETE CASCADE) |
| `spec_group` | VARCHAR(50) | Kelompok Spesifikasi (misal: *Kelistrikan, Kapasitas, Proteksi Fisik, Dimensi*) |
| `spec_key` | VARCHAR(100) | Kunci Atribut (misal: *Rated Current, Ingress Protection, Flow Rate*) |
| `spec_value` | VARCHAR(255) | Nilai Spesifikasi (misal: *25, IP65, 3.0*) |
| `spec_unit` | VARCHAR(20) NULL | Satuan Besaran Fisik (misal: *A, V, kW, m3/h, rpm, mm*) |
| `is_highlight` | BOOLEAN | Penanda Spesifikasi Utama pada Card Etalase Publik (Default: false) |
| `sort_order` | INTEGER | Urutan Tampilan Spesifikasi (Default: 0) |

Indeks Komposit: `['product_id', 'sort_order']` bernama `idx_prod_specs_order` dan `['product_id', 'spec_group']`.
