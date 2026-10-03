# Test Cases: Taksonomi Brand Prinsipal, Kategori Hierarkis, & Slug
## Domain 04: Katalog & Manajemen Inventaris (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-CAT-01` |
| **Versi Dokumen** | 1.5.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Catalog QA Tester | Draf Awal | Pengujian CRUD Master Brand dan Kategori dasar |
| `1.1.0` | 03 Sep 2026 | Data Taxonomy Specialist | Ditinjau | Penambahan uji kategori hierarkis Parent-Child & generator slug unik |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan integritas relasi produk & proteksi cascade delete |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 03 Okt 2026 | Principal QA Architect | Disetujui | Penambahan 4 skenario uji manajemen rekanan vendor (`TC-CAT-SUP-001` s.d. `004`) dan relasi `primary_supplier_id` pada master produk |
| `1.5.0` | 03 Okt 2026 | Principal QA Architect | Disetujui | Penambahan skenario uji Multi-Sourcing Vendor Matrix (`TC-CAT-SUP-005` & `006`) untuk validasi pivot `product_suppliers`, komparasi HPP beli multi-vendor, dan prioritas primary |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-CAT-01 s.d. FR-CAT-04)
  - `DOC-PBB-SDD-04` (Filament Admin Resource Brand & Category)
  - `DOC-PBB-MIG-02` (Pipeline Seeding Master Brand Prinsipal & Kategori)
  - `DOC-PBB-UIUX-02` (Grid Logo Brand Partner & Struktur Tree Navigasi Kategori)
- **Modul Sistem:** Modul Manajemen Taksonomi Master Brand & Kategori
- **Target Kelas / Model / Resource:**
  - `App\Filament\Resources\BrandResource`
  - `App\Filament\Resources\CategoryResource`
  - `App\Models\Brand`, `App\Models\Category`
- **Target Method:** `form()`, `table()`, `generateSlug()`, `preventOrphanProducts()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-CAT-TAX-001`** | Positif | Pembuatan Master Brand Baru Lengkap dengan Logo SVG/PNG | Panel Admin Filament aktif | Input: Nama Brand = "Schneider Electric", Negara = "Prancis", Upload Logo SVG | Brand berhasil disimpan, slug `schneider-electric` otomatis terbentuk, logo tersimpan di disk public, status `is_active = true`. | Not Executed |
| **`TC-CAT-TAX-002`** | Positif | Pembuatan Kategori Induk (*Root Category*) | Menu Kategori dibuka | Input: Nama Kategori = "Electrical Distribution", `parent_id = null` | Kategori induk berhasil dibuat, slug `electrical-distribution` ter-generate, muncul di level atas pohon taksonomi. | Not Executed |
| **`TC-CAT-TAX-003`** | Positif | Pembuatan Sub-Kategori Bersarang (*Nested Child Category*) | Kategori induk "Electrical Distribution" ada di DB | Input: Nama = "Miniature Circuit Breakers (MCB)", `parent_id = [ID Electrical]` | Sub-kategori berhasil dibuat, relasi `parent()` dan `children()` Eloquent terhubung presisi, slug `miniature-circuit-breakers-mcb` terbit. | Not Executed |
| **`TC-CAT-TAX-004`** | Positif | Pembuatan Sub-Kategori Tingkat 3 (*3-Tier Nested Category*) | Sub-kategori "MCB" ada di DB | Input: Nama = "1-Pole MCB", `parent_id = [ID MCB]` | Kategori tingkat 3 tersimpan, path breadcrumb terbentuk lengkap: *Electrical Distribution > MCB > 1-Pole MCB*. | Not Executed |
| **`TC-CAT-TAX-005`** | Positif | Auto-Generation & Penanganan Duplikasi Slug (*Slug Collision*) | Brand "Philips" sudah ada (`slug = philips`) | Buat Brand baru dengan nama sama "Philips" | Sistem secara otomatis menambahkan sufiks unik menjadi `philips-1` atau `philips-2` tanpa error MySQL Duplicate Key. | Not Executed |
| **`TC-CAT-TAX-006`** | Positif | Pengaktifan / Penonaktifan Status Publikasi Brand (*Toggle Active*) | Brand berstatus aktif di web publik | Toggle switch `is_active` menjadi `false` | Brand tidak lagi muncul di grid beranda dan filter katalog publik, namun data historis transaksi tetap utuh di ERP. | Not Executed |
| **`TC-CAT-TAX-007`** | Negatif | Validasi Field Wajib Kosong pada Pembuatan Brand (*Required Validation*) | Form Brand dibuka | Kosongkan field `name` dan submit | HTTP 422. Form menolak dengan pesan error: "Nama Brand wajib diisi". | Not Executed |
| **`TC-CAT-TAX-008`** | Negatif | Pencegahan Kategori Menjadi Anak dari Dirinya Sendiri (*Self-Parenting*) | Kategori "Lighting" (ID: 5) diedit | Ubah `parent_id` menjadi 5 (memilih dirinya sendiri) | Validasi menolak dengan pesan: "Kategori tidak dapat dijadikan sub-kategori untuk dirinya sendiri". | Not Executed |
| **`TC-CAT-TAX-009`** | Negatif | Pencegahan Struktur Pohon Kategori Melingkar (*Circular Parent Loop*) | Kategori A adalah parent dari B | Edit Kategori A dan pilih Kategori B sebagai parent-nya | Sistem mendeteksi loop rekursif dan memblokir penyimpanan dengan error: "Struktur hierarki kategori tidak valid (circular reference)". | Not Executed |
| **`TC-CAT-TAX-010`** | Negatif | Pencegahan Penghapusan Brand yang Memiliki Produk Aktif (*Cascade Protection*) | Brand "Schneider" memiliki 150 produk aktif di DB | Admin klik tombol "Hapus Brand" | Sistem memblokir penghapusan (*Restrict on Delete*) dengan pesan: "Brand tidak dapat dihapus karena masih memuat 150 produk aktif". | Not Executed |
| **`TC-CAT-TAX-011`** | Negatif | Pencegahan Penghapusan Kategori yang Memiliki Sub-Kategori | Kategori Induk memuat 4 sub-kategori | Admin klik tombol hapus kategori induk | Sistem menolak penghapusan hingga seluruh sub-kategori di bawahnya dipindahkan atau dihapus terlebih dahulu. | Not Executed |
| **`TC-CAT-TAX-012`** | Keamanan | Sanitasi Format Logo Brand & Pencegahan Eksekusi SVG Berbahaya | Form upload logo brand | Unggah berkas logo SVG berisi payload `<script>alert('XSS')</script>` | Sanitizer XML/SVG membersihkan tag skrip berbahaya sebelum berkas disimpan, hanya elemen grafis vektor murni yang dipertahankan. | Not Executed |
| **`TC-CAT-TAX-013`** | Keamanan | Otorisasi RBAC Khusus Peran Super Admin untuk Manajemen Taksonomi | Login sebagai Sales PIC atau Staf Logistik | Coba akses menu Master Brands & Categories | Menu disembunyikan dan endpoint rute mengembalikan HTTP 403 Forbidden (hanya Super Admin yang berhak memodifikasi taksonomi). | Not Executed |
| **`TC-CAT-TAX-014`** | Batas Nilai | Penanganan Nama Kategori dengan Karakter Simbol B2B (*Ampersand & Slash*) | Form Kategori | Input Nama = "Pumps, Valves & Piping / Fittings" | Slug yang dihasilkan terformat bersih dan ramah URL: `pumps-valves-and-piping-fittings` tanpa karakter simbol ilegal. | Not Executed |
| **`TC-CAT-TAX-015`** | Konkurensi | Pembuatan Kategori Serentak dengan Nama Mirip | 2 Admin membuat kategori "Industrial Automation" bersamaan | Simpan form di milidetik yang sama | Transaksi database atomik memastikan hanya 1 record dibuat atau diterbitkan slug unik berurutan tanpa deadlock. | Not Executed |
| **`TC-CAT-TAX-016`** | Audit | Pencatatan Lengkap Perubahan Data Master Taksonomi | Super Admin mengubah nama brand dari "ABB" ke "ABB Power" | Simpan perubahan | `activity_log` mencatat: `causer_id` (Admin ID), Event (`brand_updated`), Data lama, Data baru, dan Waktu mutasi. | Not Executed |
| **`TC-CAT-SUP-001`** | Positif | Pembuatan Rekanan Vendor Baru Lengkap dengan Brand Prinsipal & Ketentuan Bayar | Menu `/admin/suppliers` dibuka | Input: Nama = "PT. Schneider Prima Dist", Kode = "SUP-SCH", Brand = "Schneider Electric", PIC = "Hendra Wijaya", Phone = "08119876543", Payment Terms = "TOP 30" | Vendor berhasil disimpan, terhubung ke Brand Schneider, muncul di tabel daftar rekanan dengan status `[Aktif]`. | Not Executed |
| **`TC-CAT-SUP-002`** | Positif | Asosiasi Produk Master ke Primary Supplier | Master Produk dibuka di `/admin/products` | Pilih Primary Supplier = "PT. Schneider Prima Dist" pada produk MCB Acti9 | Kolom `primary_supplier_id` terisi di tabel `products`, relasi `primarySupplier()` Eloquent berfungsi, dan jumlah produk (`products_count`) pada vendor bertambah 1. | Not Executed |
| **`TC-CAT-SUP-003`** | Negatif | Validasi Keunikan Kode Vendor (*Unique Vendor Code*) | Vendor dengan kode "SUP-SCH" sudah ada di DB | Coba buat vendor baru dengan kode sama "SUP-SCH" | HTTP 422. Form menolak dengan pesan error validasi: "Kode vendor sudah terdaftar dalam sistem". | Not Executed |
| **`TC-CAT-SUP-004`** | Audit | Proteksi Soft Deletes Rekanan Vendor & Rantai Transaksi Historis | Vendor memiliki 25 produk terhubung | Admin melakukan aksi hapus (soft delete) pada vendor | Record vendor di-soft-delete (`deleted_at` terisi), produk tetap utuh, kueri `Product::with('primarySupplier')->withTrashed()` tetap mengembalikan data vendor untuk kebutuhan audit transaksi historis. | Not Executed |
| **`TC-CAT-SUP-005`** | Positif | Asosiasi Multi-Sourcing Vendor Pivot Matrix (`product_suppliers`) | 1 Part Number Produk didaftarkan ke 3 Vendor Berbeda | Input repeater: Vendor A (HPP 75rb, Lead 0hr, Primary), Vendor B (HPP 78rb, Lead 2hr), Vendor C (HPP 82rb, Lead 0hr) | Tabel pivot `product_suppliers` menyimpan 3 baris dengan `is_primary` bernilai true hanya pada Vendor A. Relasi `Product::suppliers()` mengembalikan 3 koleksi dengan atribut pivot lengkap. | Not Executed |
| **`TC-CAT-SUP-006`** | Negatif | Pencegahan Duplikasi Vendor yang Sama pada 1 Part Number (*Unique Constraint*) | Produk sudah terhubung ke Vendor A | Coba tambahkan lagi Vendor A pada repeater produk yang sama | Sistem/database menolak dengan pelanggaran kekangan unik `uk_prod_sup (product_id, supplier_id)`. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-CAT-TAX-003 (Pembuatan Sub-Kategori Bersarang)

1. **Langkah 1:** Login ke `https://app.bimabergawa.com` sebagai Super Admin.
2. **Langkah 2:** Buka menu **Katalog -> Kategori** -> Klik **"Buat Kategori Baru"**.
3. **Langkah 3:** Masukkan Nama: "Miniature Circuit Breakers (MCB)".
4. **Langkah 4:** Pada dropdown *Parent Category*, pilih "Electrical Distribution".
5. **Langkah 5:** Klik tombol **"Simpan"**.
6. **Verifikasi Teknis:**
   - Tabel `categories` mencatat baris baru dengan `parent_id` terisi ID kategori Electrical Distribution.
   - Kolom `slug` terisi `miniature-circuit-breakers-mcb`.
   - Pada pohon navigasi web publik, MCB muncul sebagai sub-menu di bawah Electrical Distribution.

### 3.2 Prosedur Uji TC-CAT-TAX-010 (Uji Proteksi Penghapusan Brand Berelasi)

1. **Langkah 1:** Identifikasi Brand "Schneider Electric" yang memiliki relasi ke 150 SKU di database.
2. **Langkah 2:** Klik tombol "Hapus" pada baris Brand tersebut dan konfirmasi modal dialog.
3. **Verifikasi Teknis:**
   - Database foreign key constraint `ON DELETE RESTRICT` atau Model Hook `Brand::deleting()` membatalkan aksi.
   - Muncul notifikasi Filament Notification berwarna merah: "Gagal menghapus brand. Masih terdapat 150 produk terkait."
   - Brand dan seluruh 150 produk tetap berada di database secara aman.

### 3.3 Prosedur Uji TC-CAT-SUP-001 (Pembuatan Rekanan Vendor & Relasi Produk)

1. **Langkah 1:** Login ke `https://app.bimabergawa.com` sebagai Super Admin atau Sales PIC.
2. **Langkah 2:** Buka menu **Manajemen Master -> Rekanan Vendor** (`/admin/suppliers`) -> Klik **"Tambah Rekanan"**.
3. **Langkah 3:** Masukkan Nama: "PT. Schneider Prima Distributor Indonesia", Kode: "SUP-SCH", Brand: "Schneider Electric", PIC: "Hendra Wijaya", Telepon: "08119876543", Termin: "Net 30 Hari".
4. **Langkah 4:** Klik tombol **"Simpan Data Vendor"**.
5. **Verifikasi Teknis:**
   - Tabel `suppliers` menyimpan baris baru dengan kode unik `SUP-SCH` dan relasi `brand_id`.
   - Buka `/admin/products/create` atau edit produk yang ada: dropdown *Primary Supplier* menampilkan "PT. Schneider Prima Distributor Indonesia (SUP-SCH)".
   - Simpan produk: kolom `products.primary_supplier_id` terisi dengan ID supplier yang dipilih.

### 3.4 Prosedur Uji TC-CAT-SUP-005 (Multi-Sourcing Vendor Pivot Matrix & Komparasi HPP)

1. **Langkah 1:** Identifikasi 1 produk MCB Acti9 Schneider Electric.
2. **Langkah 2:** Tambahkan asosiasi ke 2 supplier melalui tabel pivot `product_suppliers`:
   - Supplier 1 (Distributor Resmi): `purchase_price` = 75000.00, `lead_time_days` = 0, `is_primary` = true.
   - Supplier 2 (Sub-distributor Alternatif): `purchase_price` = 78000.00, `lead_time_days` = 2, `is_primary` = false.
3. **Verifikasi Teknis:**
   - Tabel `product_suppliers` mencatat 2 baris terpisah dengan `product_id` yang sama.
   - Panggilan method `$product->suppliers` menghasilkan 2 item dengan nilai pivot `purchase_price` dan `is_primary` sesuai input.
   - Percobaan mendaftarkan Supplier 1 kembali pada produk yang sama memicu penolakan integritas relasi `uk_prod_sup`.
