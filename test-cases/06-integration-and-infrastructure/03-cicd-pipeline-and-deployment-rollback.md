# Test Cases: Pipeline CI/CD, Deployment Zero-Downtime, & Rollback
## Domain 06: Integrasi & Infrastruktur (Integration & Infrastructure)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-INF-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & DevOps/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Release Engineer | Draf Awal | Pengujian pipeline CI/CD GitHub Actions dasar |
| `1.1.0` | 03 Sep 2026 | DevOps Specialist | Ditinjau | Penambahan uji skrip deploy.sh symlink zero-downtime & reset OPcache |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan mekanisme auto-rollback & endpoint health check /up |
| `1.3.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Pembuatan berkas mandiri domain integrasi & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-DEP-01` (Pipeline CI/CD GitHub Actions & Workflows)
  - `DOC-PBB-DEP-02` (Konfigurasi Server Hostinger PHP 8.3 & LiteSpeed Web Server)
  - `DOC-PBB-DEP-03` (Skrip Otomasi Deployment `deploy.sh` & Prosedur Rollback)
  - `DOC-PBB-API-02` (Endpoint Health Check Server & Status Infrastruktur)
- **Modul Sistem:** Modul Otomasi Deployment, CI/CD, & Pemulihan Sistem
- **Target Berkas / Skrip / Endpoint:**
  - `.github/workflows/deploy.yml`
  - `deploy.sh`, `rollback.sh`
  - Endpoint `GET /up` / `GET /healthz`
  - Konfigurasi LiteSpeed `.htaccess`
- **Target Fungsi / Aksi:** `runPipeline()`, `switchSymlink()`, `resetOpcache()`, `executeRollback()`, `healthCheck()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-INF-DEP-001`** | CI/CD | Pemicuan Otomatis Pipeline GitHub Actions saat Push ke Branch Main | Developer melakukan push ke branch `main` | Push commit kode baru | GitHub Actions otomatis tertrigger, menjalankan job linting Pint, static analysis, dan test suite Pest. | Not Executed |
| **`TC-INF-DEP-002`** | CI/CD | Penolakan Deployment Otomatis saat Terdapat Test Gagal (*Test Gate*) | Terdapat 1 unit test yang gagal di codebase | Push ke branch `main` | GitHub Actions membatalkan tahap deploy (*Failed Build*), notifikasi error dikirim, server produksi tetap aman. | Not Executed |
| **`TC-INF-DEP-003`** | Deployment | Eksekusi Skrip Deployment Zero-Downtime (*deploy.sh*) | Build CI lolos verifikasi | Eksekusi `./deploy.sh` pada server | Skrip membuat folder rilis baru `releases/YYYYMMDD_HHMMSS`, rsync aset, pasang symlink `current`, tanpa downtime bagi pengunjung web. | Not Executed |
| **`TC-INF-DEP-004`** | Deployment | Penautan Direktori Shared Storage (*Shared Storage Symlink*) | Rilis baru dibuat | Cek symlink `storage` pada rilis baru | Folder `storage` otomatis terhubung (*symlink*) ke `../../shared/storage` sehingga foto produk dan dokumen tidak hilang saat update kode. | Not Executed |
| **`TC-INF-DEP-005`** | Optimasi | Pembangkitan Cache Konfigurasi, Rute, & View (*Production Optimization*) | Rilis baru aktif | `php artisan config:cache && php artisan route:cache && php artisan view:cache` | Seluruh cache produksi terbentuk sukses, mempercepat bootstrap framework Laravel hingga 3x lipat. | Not Executed |
| **`TC-INF-DEP-006`** | Optimasi | Pembersihan & Reset Memori PHP 8.3 OPcache (*OPcache Flush*) | Kode baru selesai di-deploy | Eksekusi pembersihan OPcache via endpoint internal / CLI | Seluruh bytecode skrip PHP lama di-flush dari memori RAM, kode baru langsung aktif tanpa harus restart server web. | Not Executed |
| **`TC-INF-DEP-007`** | Health Check | Verifikasi Kesehatan Server Pasca-Deployment (*Health Check /up*) | Deployment baru selesai | Request `GET https://bimabergawa.com/up` | HTTP 200 OK. Respon JSON memuat: `{"status": "ok", "database": "connected", "storage": "writable"}` dalam waktu < 50ms. | Not Executed |
| **`TC-INF-DEP-008`** | Rollback | Otomasi Rollback saat Migrasi Database Gagal di Tengah Deploy | Terjadi error SQL saat `migrate --force` | Simulasi SQL error saat deploy | Skrip deployment menangkap error, mengembalikan symlink `current` ke rilis sebelumnya (*previous release*), dan mengirim alert kegagalan. | Not Executed |
| **`TC-INF-DEP-009`** | Rollback | Eksekusi Rollback Manual Cepat via Skrip (*rollback.sh*) | Rilis baru memiliki bug tak terduga | Operator menjalankan `./rollback.sh` via SSH | Skrip mengembalikan pointer symlink `current` ke versi rilis stabil sebelumnya dalam waktu < 5 detik. | Not Executed |
| **`TC-INF-DEP-010`** | Retensi | Pembersihan Otomatis Folder Rilis Lama (*Keep Last 5 Releases*) | Terdapat > 5 folder rilis di `releases/` | Deploy rilis baru selesai | Skrip deployment membersihkan folder rilis tertua secara otomatis, menyisakan tepat 5 rilis terakhir untuk efisiensi disk. | Not Executed |
| **`TC-INF-DEP-011`** | Keamanan | Pengerasan Izin Akses Berkas Konfigurasi (.env Hardening) | Deployment selesai | Inspeksi izin berkas `.env` di shared folder | Berkas `.env` memiliki hak akses ketat `chmod 600` (hanya dapat dibaca oleh user PHP/system), dilarang diakses publik. | Not Executed |
| **`TC-INF-DEP-012`** | Keamanan | Perlindungan Kunci Rahasia SSH & Token Deployment | Pipeline CI/CD GitHub Actions | Inspeksi log output GitHub Actions | Kunci privat SSH dan kredensial server disembunyikan (*masked as secrets*), tidak ada kredensial yang bocor di log publik. | Not Executed |
| **`TC-INF-DEP-013`** | Web Server | Pengujian Konfigurasi Mod_Rewrite & LiteSpeed Cache (.htaccess) | Server LiteSpeed Hostinger | Verifikasi header respon halaman publik | Header memuat `X-LiteSpeed-Cache: hit` atau `miss`, aturan rewrite URL bekerja sempurna tanpa error 500 Internal Server. | Not Executed |
| **`TC-INF-DEP-014`** | Kinerja | Uji Beban Akses Pengguna saat Proses Deployment Berlangsung | 50 pengunjung aktif membuka web publik | Proses `./deploy.sh` dieksekusi bersamaan | Pengunjung tidak mengalami error koneksi terputus atau halaman blank putih (*true zero-downtime atomic symlink switch*). | Not Executed |
| **`TC-INF-DEP-015`** | Audit | Pencatatan Riwayat Deployment pada Log Sistem (*Deployment Log*) | Deployment selesai dieksekusi | Buka berkas `storage/logs/deployment.log` | Tercatat: Timestamp, Git Commit Hash, User pelaksana, Durasi deployment, dan Status sukses. | Not Executed |
| **`TC-INF-DEP-016`** | Pemulihan | Verifikasi Ketersediaan Berkas Backup sebelum Menjalankan Deploy | Skrip deployment dijalankan | Fase pre-deploy snapshot | Skrip memverifikasi snapshot database cepat (*lightweight dump*) sebelum menerapkan migrasi skema baru. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-INF-DEP-003 (Eksekusi Deploy Zero-Downtime)

1. **Langkah 1:** Hubungkan SSH ke server Hostinger.
2. **Langkah 2:** Masuk ke direktori root aplikasi:
   ```bash
   cd ~/domains/bimabergawa.com/deployments
   ./deploy.sh
   ```
3. **Verifikasi Teknis:**
   - Folder rilis baru terbentuk: `releases/20260906_205000/`.
   - Perintah `composer install --no-dev --optimize-autoloader` berhasil dieksekusi.
   - Symlink `current` dialihkan secara atomik: `ln -nfs releases/20260906_205000 current`.
   - Tidak ada gangguan koneksi HTTP bagi pengguna yang sedang aktif.

### 3.2 Prosedur Uji TC-INF-DEP-007 (Verifikasi Endpoint Health Check)

1. **Langkah 1:** Kirim request ke endpoint health check resmi:
   ```bash
   curl -s "https://bimabergawa.com/up"
   ```
2. **Verifikasi Teknis:**
   - Status code: `HTTP/2 200`.
   - Body respon:
     ```json
     {
       "status": "ok",
       "environment": "production",
       "database": "connected",
       "cache": "operational",
       "storage": "writable"
     }
     ```
