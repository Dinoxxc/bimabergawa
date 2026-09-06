# 05. Otomasi Sistem, Pencadangan Bulanan & Notifikasi
## System Architecture Document (SAD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SAD-05` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Reliability & DevOps Engineer | Draf Awal | Desain penjadwalan cron worker `queue:work` & email notifications |
| `1.1.0` | 03 Sep 2026 | Database Administrator | Ditinjau | Penambahan spesifikasi backup otomatis database bulanan `.sql.gz` |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Aturan trigger alert lead kadaluarsa (>2 jam) & eskalasi email direksi |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Topologi Penjadwalan Tugas Otomatis (Laravel Scheduler Engine)

Seluruh background job, pemeliharaan basis data, dan sistem pengingat otomatis dikendalikan melalui **Satu Baris Cron Job Induk di hPanel Hostinger**:

```bash
* * * * * cd /home/uXXXXX/domains/domain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Definisi Jadwal Tugas di `app/Console/Kernel.php` / `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

// 1. Queue Worker: Berjalan tiap menit dengan batas durasi 50 detik
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(2);

// 2. Notifikasi Harian: Cek Penawaran Mendekati Kedaluwarsa & Lead Menggantung
Schedule::command('crm:check-expiring-quotations')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('crm:check-stale-leads')
    ->hourly()
    ->between('08:00', '18:00')
    ->withoutOverlapping();

// 3. Pencadangan Database: 1x Per Bulan (Tanggal 1 Pukul 01:00 Dini Hari)
Schedule::command('system:backup-database-monthly')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping();
```

---

## 2. Arsitektur Pencadangan Database Bulanan Terkompresi

Untuk menjamin keamanan aset data perusahaan tanpa membebani kuota inodes dan CPU server:

```mermaid
graph TD
    A["Trigger Scheduler: Tanggal 1 Pukul 01:00"] --> B["Command: MonthlyDatabaseBackupCommand"]
    B --> C["mysqldump database bimabergawa"]
    C --> D["Kompresi Gzip Stream (.sql.gz)"]
    D --> E["Simpan ke storage/app/backups/backup-bimabergawa-YYYY-MM-01.sql.gz"]
    E --> F["Rotasi Retensi: Simpan 12 Berkas Terakhir (1 Tahun Arsip)"]
    F --> G["Kirim Notifikasi Sukses ke Email Direksi / IT Admin"]
```

### Spesifikasi Berkas Backup:
- **Nama Berkas:** `backup-bimabergawa-YYYY-MM-01.sql.gz`
- **Format:** MySQL Raw SQL terkompresi Gzip (~80% reduksi ukuran berkas).
- **Akses Unduhan:** Disediakan tombol download berkas arsip langsung pada panel Super Admin ERP.

---

## 3. Mesin Notifikasi & Eskalasi Multi-Kanal (*Notification Engine*)

```mermaid
graph TD
    subgraph Pemicu_Waktu_dan_Status ["Pemicu Kondisi Bisnis"]
        P1["Inquiry Baru Belum Dihubungi > 2 Jam Kerja"]
        P2["Quotation Sisa 3 Hari Sebelum Kedaluwarsa"]
        P3["Pendaftaran Mitra Freelance Baru Masuk"]
    end

    subgraph Saluran_Eskalasi_dan_Notifikasi ["Distribusi Multi-Kanal"]
        K["1. Visual Kanban Alert (Badge Merah Berkedip & Timer)"]
        EP["2. Email Pengingat Otomatis ke Klien Pemohon"]
        ED["3. Email Eskalasi ke Direksi & Kepala Sales"]
    end

    P1 --> K
    P1 --> ED
    P2 --> K
    P2 --> EP
    P2 --> ED
    P3 --> ED
```

### 3.1 Peringatan Visual Kanban (*Stale Lead Alert*)
- Jika sebuah prospek berstatus `new` belum mengalami perubahan status atau belum dihubungi sales dalam waktu $> 2\text{ jam}$ kerja (Senin–Jumat, 08:00–17:00), kartu prospek pada Kanban board otomatis:
  - Berubah warna latar belakang menjadi merah muda berkedip (*subtle pulsing effect*).
  - Menampilkan badge timer *"Belum dihubungi: X jam Y menit"*.

### 3.2 Auto-Email Pengingat Santun ke Prospek (*Prospect Courtesy Reminder*)
- Pada H-3 sebelum tanggal masa berlaku penawaran (`quotation->expires_at`) berakhir, sistem mengirimkan email profesional otomatis:
  - *Subjek:* `[Pengingat] Masa Berlaku Penawaran Resmi PT. Panca Bima Bergawa (PBB) - QUO-YYYYMM-XXXX`
  - *Isi Pesan:* Menyapa PIC klien, mengingatkan nomor penawaran, melampirkan tautan download PDF resmi, dan menawarkan bantuan teknis jika ada pertanyaan lebih lanjut.

### 3.3 Email Eskalasi ke Direksi & Head of Sales
- **Eskalasi Lead Menggantung:** Memberikan visibilitas langsung kepada pimpinan jika ada prospek inbound yang terlambat ditindaklanjuti oleh tim komersial.
- **Rekapitulasi Penawaran Tertunda:** Mengirimkan ringkasan berkala atas penawaran bernilai besar yang mendekati jatuh tempo agar pimpinan dapat memberikan instruksi strategi negosiasi khusus kepada staf sales terkait.
