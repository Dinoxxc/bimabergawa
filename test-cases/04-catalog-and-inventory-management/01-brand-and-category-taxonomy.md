# Test Cases: Taksonomi Brand Prinsipal, Kategori Hierarkis, & Slug
## Domain 04: Katalog & Manajemen Inventaris (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-CAT-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Catalog QA Tester | Draf Awal | Pengujian CRUD Master Brand dan Kategori dasar |
| `1.1.0` | 03 Sep 2026 | Data Taxonomy Specialist | Ditinjau | Penambahan uji kategori hierarkis Parent-Child & generator slug unik |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan integritas relasi produk & proteksi cascade delete |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

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
