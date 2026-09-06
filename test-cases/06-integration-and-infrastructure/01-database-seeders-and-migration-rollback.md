# Test Cases: Seeder Basis Data, Taksonomi Awal, & Rollback Migrasi
## Domain 06: Integrasi & Infrastruktur (Integration & Infrastructure)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-INF-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & DevOps/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Database DevOps QA | Draf Awal | Pengujian migrasi tabel & seeder peran dasar |
| `1.1.0` | 03 Sep 2026 | Database Architect | Ditinjau | Penambahan uji seeder taksonomi 4 brand prinsipal & kategori hierarkis |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan pengujian integritas foreign key saat rollback skema |
| `1.3.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Pembuatan berkas mandiri domain integrasi & standardisasi status Not Executed |
| `1.4.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan ekspansi 20 tabel domain, 24 migrasi domain, dan total 28 berkas migrasi |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-MIG-01` (Spesifikasi Pipeline Database Seeder & 5 Peran Spatie RBAC)
  - `DOC-PBB-MIG-02` (Spesifikasi Seeding Master Brand Prinsipal & Kategori Hierarkis)
  - `DOC-PBB-SDD-01` (Skema Basis Data MySQL 8.0 & Kamus Data 20 Tabel Domain)
  - `DOC-PBB-SDD-02` (Definisi Model Eloquent, Relasi, & Foreign Key Constraints)
- **Modul Sistem:** Modul Inisialisasi Basis Data, Seeder, & Migrasi Skema
- **Target Kelas / Seeder / Perintah Artisan:**
  - `Database\Seeders\DatabaseSeeder`
  - `Database\Seeders\RoleAndPermissionSeeder`
  - `Database\Seeders\BrandAndCategorySeeder`
  - `Database\Seeders\InitialSuperAdminSeeder`
- **Target Perintah CLI:** `php artisan migrate`, `php artisan db:seed`, `php artisan migrate:rollback`, `php artisan migrate:fresh`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **`TC-INF-MIG-001`** | Positif | Eksekusi Migrasi Skema Basis Data Bersih (*Fresh Migration*) | Database MySQL 8.0 kosong | Jalankan `php artisan migrate:fresh` | Seluruh 20 tabel domain (total 28 berkas migrasi) berhasil dibuat, tipe data presisi (`DECIMAL(15,2)`, `VARCHAR`, `TIMESTAMP`), foreign key terpasang tanpa error. | Not Executed |
| **`TC-INF-MIG-002`** | Positif | Inisialisasi 5 Peran Standar & Hak Akses Spatie RBAC | Migrasi database selesai | Jalankan `RoleAndPermissionSeeder` | Terbentuk 5 role baku: `super-admin`, `sales-pic`, `finance`, `logistik`, `mitra-agen` beserta seluruh permissions pada tabel `roles` & `permissions`. | Not Executed |
| **`TC-INF-MIG-003`** | Positif | Inisialisasi Akun Super Admin Awal (*Bootstrap Super Admin*) | Role Spatie telah dibuat | Jalankan `InitialSuperAdminSeeder` | Akun Super Admin `admin@bimabergawa.com` dibuat dengan status `is_active = true` dan terikat pada role `super-admin`. | Not Executed |
| **`TC-INF-MIG-004`** | Positif | Seeding 4 Master Brand Prinsipal Resmi | Tabel `brands` kosong | Jalankan `BrandAndCategorySeeder` | Terbentuk 4 record brand aktif: *Schneider Electric, Philips Lighting, ABB, Legrand* dengan slug dan metadata resmi. | Not Executed |
| **`TC-INF-MIG-005`** | Positif | Seeding Taksonomi Kategori Hierarkis (*Parent-Child Tree*) | Master brand telah ter-seed | Jalankan `BrandAndCategorySeeder` | Terbentuk kategori induk (cth: *Electrical Distribution*) dan sub-kategori terkait dengan `parent_id` terhubung presisi. | Not Executed |
| **`TC-INF-MIG-006`** | Positif | Eksekusi Master Pipeline Seeder Lengkap (*All-in-One Seed*) | Database baru dimigrasi | Jalankan `php artisan db:seed` | `DatabaseSeeder::run()` memanggil seluruh seeder berurutan tanpa error dependensi urutan kunci asing (*foreign key sequence*). | Not Executed |
| **`TC-INF-MIG-007`** | Positif | Idempotensi Eksekusi Seeder Berulang (*Idempotent Seeder Execution*) | Seeder sudah pernah dijalankan | Jalankan `php artisan db:seed` untuk kedua kalinya | Seeder menggunakan `firstOrCreate()` / `updateOrCreate()`, tidak menghasilkan data duplikat atau pelanggaran unique constraint. | Not Executed |
| **`TC-INF-MIG-008`** | Rollback | Eksekusi Rollback Migrasi Bertahap (*Step Rollback*) | Seluruh tabel termigrasi | Jalankan `php artisan migrate:rollback --step=1` | Batch migrasi terakhir berhasil di-drop tanpa kendala constraint dependency, skema kembali ke kondisi sebelumnya. | Not Executed |
| **`TC-INF-MIG-009`** | Rollback | Rollback Penuh Seluruh Skema Basis Data (*Full Rollback*) | Seluruh 20 tabel terisi data | Jalankan `php artisan migrate:reset` | Seluruh 20 tabel domain dan tabel pendukung/Spatie RBAC berhasil di-drop dengan urutan aman (*reverse foreign key order*), 0 tabel tersisa. | Not Executed |
| **`TC-INF-MIG-010`** | Negatif | Deteksi Kesalahan Konfigurasi Koneksi Database (.env) | Konfigurasi DB_PASSWORD di `.env` salah | Jalankan `php artisan migrate` | Artisan menangkap `PDOException (Access Denied)`, menampilkan pesan error konfigurasi yang jelas tanpa crash PHP fatal. | Not Executed |
| **`TC-INF-MIG-011`** | Integritas | Penegakan Foreign Key Constraints `ON DELETE RESTRICT` | Data quotation memiliki item | Jalankan query `DELETE FROM quotations WHERE id = 1` tanpa menghapus items | MySQL menolak aksi dengan error `Cannot delete or update a parent row: a foreign key constraint fails`, menjaga integritas data relasi. | Not Executed |
| **`TC-INF-MIG-012`** | Integritas | Penegakan Cascade Delete pada Tabel Terisolasi (*Cascade Cleanup*) | Hapus inquiry yang belum berstatus kontrak | Eksekusi delete pada inkuiri draf | Seluruh baris terkait pada tabel `inquiry_items` otomatis terhapus bersih (*clean cascade deletion*). | Not Executed |
| **`TC-INF-MIG-013`** | Keamanan | Penegakan Default Enkripsi Kata Sandi / Token saat Seeding | Seeder akun awal dieksekusi | Cek kolom `password` pada tabel `users` | Kata sandi awal (jika ada) di-hash menggunakan algoritma Bcrypt (`Hash::make()`), tidak ada password plaintext di basis data. | Not Executed |
| **`TC-INF-MIG-014`** | Batas Nilai | Integritas Indeks Unik pada Kolom Kritis (*Unique Indexes*) | Tabel `products`, `brands`, `users` | Tes insert duplikasi slug / email / SKU | MySQL menegakkan index unik (`UNIQUE KEY`), menolak duplikasi data di level engine penyimpanan InnoDB. | Not Executed |
| **`TC-INF-MIG-015`** | Performa | Waktu Eksekusi Migrasi & Seeder Lengkap pada Shared Hosting | Hostinger Shared Hosting CLI | Ukur durasi `php artisan migrate:fresh --seed` | Seluruh alur migrasi 28 berkas dan seeding tuntas dalam waktu < 10 detik, memori RAM puncak < 32 MB. | Not Executed |
| **`TC-INF-MIG-016`** | Verifikasi | Perintah Pengecekan Status Migrasi (*Migrate Status Inspection*) | Skema termigrasi penuh | Jalankan `php artisan migrate:status` | Seluruh 28 berkas migrasi berstatus `Ran = Yes` dengan batch number konsisten. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-INF-MIG-006 (Eksekusi DatabaseSeeder Lengkap)

1. **Langkah 1:** Buka terminal server atau local environment.
2. **Langkah 2:** Eksekusi perintah artisan:
   ```bash
   php artisan migrate:fresh --seed
   ```
3. **Verifikasi Teknis:**
   - Seluruh 20 tabel domain dan 8 tabel pendukung berhasil dibuat di database.
   - Output terminal menampilkan:
     - `Seeding: Database\Seeders\RoleAndPermissionSeeder` -> Done.
     - `Seeding: Database\Seeders\InitialSuperAdminSeeder` -> Done.
     - `Seeding: Database\Seeders\BrandAndCategorySeeder` -> Done.
   - Tabel `roles` memuat 5 baris data, tabel `brands` memuat 4 baris data utama.

### 3.2 Prosedur Uji TC-INF-MIG-009 (Verifikasi Full Migration Rollback)

1. **Langkah 1:** Pastikan seluruh tabel dan relasi foreign key telah terisi.
2. **Langkah 2:** Jalankan perintah reset skema:
   ```bash
   php artisan migrate:reset
   ```
3. **Verifikasi Teknis:**
   - Tidak ada error `1217: Cannot delete or update a parent row: a foreign key constraint fails`.
   - Seluruh tabel dihapus secara berurutan dari tabel anak ke tabel induk.
