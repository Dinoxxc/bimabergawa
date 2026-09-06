# Test Cases: Artisan Scheduler, Cron Tasks, & Otomasi Backup
## Domain 06: Integrasi & Infrastruktur (Integration & Infrastructure)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-INF-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & DevOps/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Infrastructure QA | Draf Awal | Pengujian scheduler Laravel dasar |
| `1.1.0` | 03 Sep 2026 | Automation Engineer | Ditinjau | Penambahan uji cron SLA inkuiri, pembersihan berkas yatim, & backup DB |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan notifikasi kegagalan cron & integrasi hPanel Hostinger |
| `1.3.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Pembuatan berkas mandiri domain integrasi & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SAD-05` (Arsitektur Otomasi Cron, Backup Bulanan, & Notifikasi)
  - `DOC-PBB-API-04` (Spesifikasi Artisan CLI Scheduler & HTTP Cron Handlers)
  - `DOC-PBB-DEP-04` (Konfigurasi Cron hPanel Hostinger & Pemantauan Server)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional SLA Monitoring & Backup Berkala)
- **Modul Sistem:** Modul Penjadwalan Tugas Otomatis (Scheduler, Cron, & Backup)
- **Target Kelas / Perintah Artisan:**
  - `App\Console\Commands\CheckSlaStaleInquiries` (`pbb:check-sla-stale`)
  - `App\Console\Commands\CleanTemporaryUploads` (`pbb:clean-temp-uploads`)
  - `App\Console\Commands\MonthlyDatabaseBackup` (`pbb:monthly-backup`)
  - `App\Console\Commands\AuditDocumentSequences` (`pbb:audit-sequences`)
  - `App\Console\Kernel`
- **Target Method:** `schedule()`, `handle()`, `sendFailureNotification()`, `withoutOverlapping()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-INF-CRN-001`** | Positif | Eksekusi Penjadwal Utama (*Master Scheduler Execution*) | Cron hPanel Hostinger aktif | Jalankan `php artisan schedule:run` | Laravel mengevaluasi seluruh daftar tugas terjadwal dan mengeksekusi perintah yang sesuai dengan waktunya. | Not Executed |
| **`TC-INF-CRN-002`** | Positif | Pengecekan SLA Inkuiri Macet Jam-an (*Hourly SLA Stale Checker*) | Terdapat inkuiri status `lead` berusia 26 jam | Jalankan `php artisan pbb:check-sla-stale` | Perintah mendeteksi inkuiri macet, mengirimkan email peringatan ke PIC Sales yang ditugaskan dan menandai notifikasi terkirim. | Not Executed |
| **`TC-INF-CRN-003`** | Positif | Eskalasi Inkuiri Macet Kritis (> 48 Jam) ke Direktur | Terdapat inkuiri status `lead` berusia 50 jam | Jalankan `php artisan pbb:check-sla-stale` | Sistem mendeteksi kondisi kritis (>48 jam), mengirimkan email rekap inkuiri macet langsung ke Direktur Operasional. | Not Executed |
| **`TC-INF-CRN-004`** | Positif | Pembersihan Berkas Sementara Yatim (*Daily Orphan File Cleanup*) | Terdapat berkas temp di `storage/app/temp/` berusia 30 jam | Jalankan `php artisan pbb:clean-temp-uploads` | Berkas sementara berusia > 24 jam berhasil dihapus dari filesystem, berkas baru (< 24 jam) tetap dipertahankan. | Not Executed |
| **`TC-INF-CRN-005`** | Positif | Pembersihan Log Aktivitas Usia > 90 Hari (*Weekly Log Pruning*) | Tabel `activity_log` memuat log berusia 95 hari | Jalankan `php artisan activitylog:clean --days=90` | Log lama (> 90 hari) terhapus dari MySQL, log 90 hari terakhir tetap terjaga utuh. | Not Executed |
| **`TC-INF-CRN-006`** | Positif | Pembuatan Backup Basis Data Gzip Bulanan (*Monthly DB Dump*) | Awal bulan (tanggal 1 pukul 01:00) | Jalankan `php artisan pbb:monthly-backup` | Menghasilkan berkas `backup-pbb-db-2026-09-01.sql.gz` di private storage, mengirimkan notifikasi status sukses ke email Admin. | Not Executed |
| **`TC-INF-CRN-007`** | Positif | Rekonsiliasi Nomor Urut Dokumen Bulanan (*Sequence Audit*) | Pergantian bulan kalender | Jalankan `php artisan pbb:audit-sequences` | Sistem memverifikasi tabel `document_sequences`, memastikan urutan nomor baru siap dimulai dari angka `0001` untuk bulan baru. | Not Executed |
| **`TC-INF-CRN-008`** | Positif | Penanganan Pencegahan Tumpang Tindih Tugas (*withoutOverlapping*) | Tugas backup sedang berjalan memakan waktu lama | Pemicu cron berikutnya aktif di menit yang sama | Sistem menggunakan mutex lock cache, membatalkan eksekusi kedua agar tidak terjadi bentrok pemrosesan (*no overlapping*). | Not Executed |
| **`TC-INF-CRN-009`** | Fallback | Eksekusi Scheduler via Webhook HTTP (*HTTP Cron Fallback*) | Akses SSH/Cron CLI dinonaktifkan di hPanel | Request: `POST /cron/run?token=SECURE_SECRET_TOKEN` | Controller memverifikasi secret token yang valid dan mengeksekusi `Artisan::call('schedule:run')` via web request. | Not Executed |
| **`TC-INF-CRN-010`** | Negatif | Penolakan Akses HTTP Cron Fallback dengan Token Salah | Penyerang mencoba trigger cron | Request: `POST /cron/run?token=WRONG_TOKEN` | Controller menolak akses seketika dengan status code `HTTP 403 Forbidden`, mencatat anomali ke security log. | Not Executed |
| **`TC-INF-CRN-011`** | Ketahanan | Notifikasi Kegagalan Tugas Terjadwal (*On-Failure Alert*) | Perintah backup gagal (misal: storage penuh) | Simulasi kegagalan pada perintah scheduler | Hook `onFailure()` menangkap pengecualian dan mengirimkan notifikasi darurat via email/webhook ke tim DevOps. | Not Executed |
| **`TC-INF-CRN-012`** | Kinerja | Pengaturan Batas Waktu Eksekusi Perintah (*Command Timeout*) | Perintah scheduler memproses dataset masif | Eksekusi perintah melebihi 300 detik | Artisan Scheduler menegakkan batasan timeout aman (`runInBackground()` / `timeout(300)`), mencegah proses menggantung (*hanging process*). | Not Executed |
| **`TC-INF-CRN-013`** | Batas Nilai | Pemrosesan SLA Stale saat Database Memuat 1.000 Inkuiri | 1.000 inkuiri berstatus aktif di CRM | Jalankan `pbb:check-sla-stale` | Perintah menggunakan pemrosesan chunk (`Inquiry::chunk(100)`), memori RAM server tetap stabil di bawah 45 MB. | Not Executed |
| **`TC-INF-CRN-014`** | Audit | Pencatatan Riwayat Eksekusi Backup pada Audit Log | Backup bulanan sukses dibuat | Periksa tabel `activity_log` | Tercatat: Event (`monthly_backup_completed`), Ukuran berkas backup, Durasi proses, dan Lokasi penyimpanan arsip. | Not Executed |
| **`TC-INF-CRN-015`** | Keamanan | Enkripsi Berkas Arsip Backup pada Media Penyimpanan | Berkas backup dihasilkan | Inspeksi berkas `.sql.gz` di storage | Berkas backup disimpan pada direktori privat dengan izin akses ketat (`chmod 600`), tidak dapat diakses langsung dari web publik. | Not Executed |
| **`TC-INF-CRN-016`** | Rotasi Log | Rotasi Otomatis Berkas Log Server (*Log Rotation Retention*) | Berkas log `laravel.log` mencapai ukuran 10 MB | Log harian baru dihasilkan | Laravel dikonfigurasi `LOG_CHANNEL=daily` dengan retensi 14 hari (`LOG_DAILY_DAYS=14`), berkas log lama otomatis dirotasi dan dihapus. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-INF-CRN-002 (Eksekusi Pengecekan SLA Inkuiri Macet)

1. **Langkah 1:** Buat 1 inkuiri berstatus `lead` dengan `created_at` dimundurkan 26 jam dari waktu saat ini.
2. **Langkah 2:** Jalankan perintah artisan secara manual via CLI:
   ```bash
   php artisan pbb:check-sla-stale
   ```
3. **Verifikasi Teknis:**
   - Output terminal: "Found 1 stale inquiries. Dispatching warning notifications... Done."
   - Mailbox log mencatat pengiriman email peringatan ke Sales PIC terkait.
   - Status inkuiri di database terupdate dengan flag `sla_warning_sent_at = NOW()`.

### 3.2 Prosedur Uji TC-INF-CRN-006 (Eksekusi Backup Basis Data Bulanan)

1. **Langkah 1:** Jalankan perintah backup melalui terminal:
   ```bash
   php artisan pbb:monthly-backup
   ```
2. **Verifikasi Teknis:**
   - Perintah mengeksekusi `mysqldump` dengan kompresi gzip.
   - Berkas backup tersimpan di `storage/app/private/backups/backup-pbb-db-YYYY-MM-DD.sql.gz`.
   - Ukuran berkas > 0 bytes dan integritas arsip valid saat diuji dengan perintah `gzip -t`.
