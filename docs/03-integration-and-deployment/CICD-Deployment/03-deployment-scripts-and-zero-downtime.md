# 03. Skrip Deployment Produksi, Zero-Downtime, & Prosedur Rollback
## CI/CD & Deployment Suite (CICD-Deployment)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-DEP-03` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | DevOps Engineer | Draf Awal | Skrip deployment dasar `deploy.sh` |
| `1.1.0` | 03 Sep 2026 | Release Manager | Ditinjau | Penambahan maintenance bypass secret & caching Filament |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Prosedur mitigasi kegagalan migrasi & rollback darurat |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri skrip deploy, langkah rollback, & zero emoji |
| `1.4.0` | 08 Sep 2026 | Principal System Architect & Release Lead | Disetujui | Penambahan Bagian 4: Standar Operasional Prosedur (SOP) Tata Kelola Evolusi Fitur Pasca-Rilis (Penambahan, Perubahan, & Penonaktifan Fitur) |

---

## 1. Skrip Eksekusi Deployment di Server (`deploy.sh`)

Berkas skrip diletakkan pada root proyek server (`/home/uXXXX/domains/bimabergawa.com/public_html/deploy.sh`) dan diberikan hak eksekusi `chmod +x deploy.sh`:

```bash
#!/bin/bash
set -e

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
echo "[DEPLOY-PBB] [$TIMESTAMP] Memulai prosedur deployment produksi..."

# 1. Masuk ke direktori kerja aplikasi
cd /home/uXXXX/domains/bimabergawa.com/public_html

# 2. Aktifkan Maintenance Mode dengan Secret Bypass Token (untuk rilis migrasi besar)
# php artisan down --render="errors::503" --secret="pbb-release-bypass-2026"

# 3. Buat Snapshot Commit Terakhir untuk Keperluan Rollback
PREV_COMMIT=$(git rev-parse HEAD)
echo "[DEPLOY-PBB] Commit saat ini: $PREV_COMMIT"

# 4. Tarik Kode Sumber Terbaru dari Repositori GitHub
echo "[DEPLOY-PBB] Menjalankan git pull origin main..."
git pull origin main

# 5. Instalasi dan Optimasi Dependensi PHP Produksi
echo "[DEPLOY-PBB] Mengoptimasi dependensi composer..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 6. Pastikan Symlink Direktori Storage Publik Terpasang
echo "[DEPLOY-PBB] Memeriksa storage symlink..."
php artisan storage:link || true

# 7. Eksekusi Migrasi Database Terproteksi
echo "[DEPLOY-PBB] Menjalankan migrasi database..."
php artisan migrate --force

# 8. Bersihkan dan Bangun Ulang Seluruh Cache Performa
echo "[DEPLOY-PBB] Memanaskan cache aplikasi..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan icons:cache

# 9. Nonaktifkan Maintenance Mode
# php artisan up

echo "[DEPLOY-PBB] Deployment berhasil diselesaikan secara sempurna pada $(date +"%Y-%m-%d %H:%M:%S")."
```

---

## 2. Pemanasan Cache Aplikasi (Cache Warming Checklist)

| Perintah Artisan | Tujuan Optimasi |
| :--- | :--- |
| `php artisan config:cache` | Menggabungkan seluruh file config menjadi 1 berkas PHP cepat |
| `php artisan route:cache` | Mengompilasi pohon routing untuk pencocokan URL berkecepatan tinggi |
| `php artisan view:cache` | Melakukan pre-kompilasi seluruh template Blade ke PHP murni |
| `php artisan filament:cache-components` | Mengompilasi seluruh skema Form, Table, dan Infolist Filament |
| `php artisan icons:cache` | Melakukan caching pustaka Heroicons & Lucide Icons |

---

## 3. Prosedur Kontinjensi & Rollback Darurat (Rollback Procedures)

Jika terjadi kendala kritis setelah deployment (misal: fatal error pada database atau aplikasi), tim teknis segera menjalankan langkah pemulihan berikut:

### Langkah 1: Kembalikan Kode Sumber ke Commit Sebelumnya
```bash
cd /home/uXXXX/domains/bimabergawa.com/public_html
git reset --hard $PREV_COMMIT
```

### Langkah 2: Rollback Migrasi Database Terakhir (Jika Diperlukan)
```bash
php artisan migrate:rollback --step=1 --force
```

### Langkah 3: Bersihkan dan Bangun Ulang Cache
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### Langkah 4: Verifikasi Ketersediaan Layanan
Lakukan pengujian respon HTTP pada kedua domain:
```bash
curl -I https://bimabergawa.com
curl -I https://app.bimabergawa.com/login
```

---

## 4. SOP Tata Kelola Evolusi Fitur Pasca-Rilis (Post-Release Feature Lifecycle Management)

Dokumen ini menetapkan *Standard Operating Procedure* (SOP) resmi dan mengikat untuk mengelola penambahan (*additions*), modifikasi (*modifications*), maupun pengurangan/penonaktifan (*deprecations*) fitur setelah sistem PT. Panca Bima Bergawa berada pada fase produksi *live*.

Tujuan utama SOP ini adalah menjamin **Nol Downtime (*Zero Downtime*)**, **Nol Kerusakan Data Historis (*Zero Historical Data Loss*)**, serta **Nol Regresi Fungsional (*Zero Regression*)** pada data komersial aktif.

---

### 4.1 Protokol Skenario A: Penambahan Fitur Baru (Feature Additions)

*Contoh Kasus:* Penambahan integrasi payment gateway (Xendit/Midtrans), modul master direktori pelanggan tetap (*Customer Directory*), atau modul pelacakan logistik pihak ketiga (BiteShip/Deliveree).

#### Prosedur Eksekusi Langkah-demi-Langkah:
1. **Analisis Kebutuhan & Dokumentasi (Documentation-First):**
   - Perbarui dokumen PRD (`DOC-PBB-PRD-XX`), SRS (`DOC-PBB-SRS-XX`), dan buat bab arsitektur baru di SDD.
   - Buat berkas skenario pengujian baru pada direktori `test-cases/`.
2. **Pengembangan pada Working Branch Terisolasi:**
   - Cabangkan branch kerja dari `staging`: `git checkout -b feature/nama-fitur-baru`.
3. **Standar Migrasi Basis Data Aditif (Non-Destructive Migrations):**
   - **DILARANG KERAS** menjalankan `migrate:fresh` atau `migrate:reset` pada server produksi.
   - Setiap tabel baru dibuat melalui berkas migrasi baru (misal: `YYYY_MM_DD_create_customers_table.php`).
   - Jika menambah kolom pada tabel eksisting, gunakan klausa aditif:
     ```php
     Schema::table('inquiries', function (Blueprint $table) {
         $table->foreignId('customer_id')->nullable()->after('freelance_sales_id')->constrained('customers')->onDelete('set null');
     });
     ```
   - Seluruh penambahan kolom foreign key baru **WAJIB bersifat `nullable()`** agar data transaksi lama tidak mengalami error integritas.
   - Setiap berkas migrasi wajib menyertakan logika pembalikan yang utuh pada method `down()`.
4. **Pemisahan Logika Bisnis (Modular Service Layer):**
   - Seluruh logika bisnis fitur baru wajib dibungkus dalam Service mandiri (misal: `App\Services\PaymentGatewayService.php`).
   - Controller dan antarmuka Filament dilarang memuat kueri langsung.
5. **Pengujian Regresi Lokal (Automated Regression Test):**
   - Jalankan `php artisan test` dan `./vendor/bin/pest`.
   - Seluruh test suite eksisting wajib lulus 100% untuk memastikan fitur baru tidak merusak alur transaksi lama.

---

### 4.2 Protokol Skenario B: Perubahan Aturan & Logika Bisnis (Feature Modifications)

*Contoh Kasus:* Direksi mengubah ambang batas otorisasi penawaran dari Rp 100 Juta ke Rp 250 Juta, menaikkan persentase margin tipis, atau menyesuaikan skema tiering komisi agen freelance.

#### Matriks Keputusan Perubahan (Decision Matrix):

| Tingkat Perubahan | Karakteristik Perubahan | Lokasi Penanganan | Kebutuhan Rilis Kode |
| :--- | :--- | :--- | :---: |
| **Tingkat 1: Parameter Konfigurasi** | Nilai ambang batas (*threshold*), persentase PPN dinamis, hotline, teks syarat & ketentuan | **Tabel `system_settings`** via Panel ERP Filament | **Nol Deploy (0% Code Change)** |
| **Tingkat 2: Perubahan Rumus / Alur Servis** | Algoritma kalkulasi margin, formula penomoran baru, logika alur persetujuan bertingkat | **Layer Servis (`app/Services/...`)** | **Deploy Standar (Patch Release)** |
| **Tingkat 3: Perubahan Skema Basis Data** | Penambahan field status baru pada enum, perubahan relasi foreign key | **Migrasi Alter Table** | **Deploy Terjadwal (Minor Release)** |

#### Aturan Penjaminan Data Historis (Immutability Principle):
- **Larangan Mengubah Transaksi Masa Lalu:** Jika aturan komisi atau tarif PPN diubah hari ini, transaksi masa lalu (Quotation, Sales Order, Invoice) yang telah diterbitkan **DILARANG BERUBAH NILAINYA**.
- Setiap transaksi historis mengunci nilai snapshot (`vat_rate_applied`, `commission_rate_applied`) sehingga audit finansial 10 tahun UU KUP tetap 100% valid.

---

### 4.3 Protokol Skenario C: Penonaktifan / Pengurangan Fitur (Graceful Feature Deprecation)

*Contoh Kasus:* Perusahaan memutuskan untuk menutup sementara pendaftaran agen freelance baru, menonaktifkan fitur upload BoQ tender, atau menghentikan opsi pembayaran metode tertentu.

#### Prosedur Penonaktifan Tanpa Merusak Data (Graceful Deprecation SOP):
1. **Larangan Eksekusi Hard-Drop Table:**
   - Dilarang keras menjalankan `Schema::dropIfExists()` pada tabel yang memuat data historis.
   - Tabel fitur yang tidak lagi digunakan tetap dipertahankan di basis data sebagai arsip audit *read-only*.
2. **Penerapan Feature Flag (System Settings Toggle):**
   - Buat konfigurasi sakelar pada `system_settings` (misal: `features.freelance_registration_enabled = false`).
   - Kode antarmuka Blade publik dan Filament memeriksa status flag:
     ```php
     if (! SystemSetting::get('features.freelance_registration_enabled', true)) {
         abort(404, 'Layanan registrasi mitra agen saat ini sedang ditutup.');
     }
     ```
3. **Pencabutan Izin Hak Akses (Spatie RBAC Revocation):**
   - Cabut hak akses fitur yang dinonaktifkan dari peran pengguna melalui tabel `role_has_permissions`.
   - Menu dan tombol aksi di panel ERP Filament secara otomatis hilang dari layar tanpa perlu menghapus kode komponen.
4. **Soft Deletion & Status Deprecated:**
   - Untuk data master produk atau kategori yang tidak lagi dipasok oleh prinsipal, ubah status menjadi `discontinued` atau jalankan *soft delete*. Relasi ke transaksi masa lalu tetap utuh.

---

### 4.4 Matriks Penjaminan Kualitas & Uji Regresi Nol (Zero-Regression Release Checklist)

Sebelum setiap perubahan pasca-rilis di-merge dari branch `staging` ke branch produksi `main`, tim pengembang wajib memenuhi checklist verifikasi berikut:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ CHECKLIST INTEGRASI & RILIS PASCA-PRODUKSI (RELEASE GATEWAY)                                           │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ ] 1. Dokumentasi & Test Case Terbarui: Seluruh dokumen docs/ dan test-cases/ sinkron dengan kode.    │
│ [ ] 2. Uji Regresi Otomatis Lolos 100%: Perintah 'php artisan test' menghasilkan 0 failure & 0 error.  │
│ [ ] 3. Audit Zero Emoji Lolos: Tidak ada karakter emoji Unicode pada kode, commit, maupun antarmuka.   │
│ [ ] 4. Audit Backward Compatibility Migrasi: Berkas migrasi memiliki up() dan down() yang simetris.    │
│ [ ] 5. Uji Coba Terisolasi di Staging: Fitur telah diuji langsung pada server staging sandbox.         │
│ [ ] 6. Persetujuan Formal Pengguna: Pengguna telah menyetujui laporan rilis sebelum eksekusi merge.   │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

