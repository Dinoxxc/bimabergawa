# 04. Spesifikasi Cron Jobs, Webhooks, & Background Tasks
## API & Routing Specification Suite (API-Routing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-API-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | DevOps Lead | Draf Awal | Definisi task scheduler antrean email dasar |
| `1.1.0` | 03 Sep 2026 | Backend Engineer | Ditinjau | Penambahan Cron deteksi Stale Lead $>2$ jam & backup database |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Proteksi token keamanan pada endpoint HTTP Cron fallback |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri background jobs, CLI commands, & zero emoji |

---

## 1. Penjadwalan Tugas Otomatis (Artisan CLI Scheduler)

Seluruh background jobs terpusat pada file `app/Console/Kernel.php` dan dieksekusi secara otomatis oleh Cron Job server Hostinger:

```php
protected function schedule(Schedule $schedule): void
{
    // 1. Eksekusi Antrean Email & Notifikasi (Setiap Menit)
    $schedule->command('queue:work --stop-when-empty --max-time=50')
        ->everyMinute()
        ->withoutOverlapping();

    // 2. Deteksi Prospek Tertunda / Stale Leads (Setiap 15 Menit pada Jam Kerja)
    $schedule->command('pbb:check-stale-leads')
        ->cron('*/15 8-17 * * 1-5')
        ->withoutOverlapping();

    // 3. Notifikasi Pengingat Masa Berlaku Penawaran H-3 (Setiap Hari Jam 08:30 WIB)
    $schedule->command('pbb:notify-expiring-quotes')
        ->dailyAt('08:30')
        ->timezone('Asia/Jakarta');

    // 4. Pencadangan Database Mingguan (Setiap Hari Minggu Jam 02:00 WIB)
    $schedule->command('pbb:backup-database')
        ->weeklyOn(0, '02:00')
        ->timezone('Asia/Jakarta');

    // 5. Pembersihan Log Lama & Token Kedaluwarsa (Setiap Hari Jam 00:00 WIB)
    $schedule->command('auth:clear-resets')->daily();
    $schedule->command('activitylog:clean --days=90')->daily();
}
```

---

## 2. Rincian Perintah Artisan Khusus (Custom Artisan Commands)

### 2.1 Perintah Deteksi Prospek Tertunda (`php artisan pbb:check-stale-leads`)
- **Tujuan:** Menemukan seluruh *Inquiry* berstatus `new` yang dibuat lebih dari 2 jam lalu dan belum memiliki `assigned_sales_id`.
- **Aksi Sistem:**
  - Mengubah status visual badge prospek menjadi `STALE > 2 JAM`.
  - Mengirimkan email ringkasan eskalasi ke alamat Direksi (`direksi@bimabergawa.com`) dan Sales Manager.
  - Mencatat log aktivitas eskalasi pada tabel `activity_log`.

### 2.2 Perintah Pengingat Kedaluwarsa Penawaran (`php artisan pbb:notify-expiring-quotes`)
- **Tujuan:** Memeriksa seluruh *Quotation* berstatus `sent` yang masa berlakunya tersisa 3 hari ($H-3$).
- **Aksi Sistem:**
  - Mengirim email pengingat santun otomatis kepada PIC klien yang memuat nomor referensi penawaran dan kontak PIC sales.
  - Mengirim notifikasi internal ke sales bersangkutan untuk melakukan tindak lanjut via telepon/WhatsApp.

### 2.3 Perintah Pencadangan Database Otomatis (`php artisan pbb:backup-database`)
- **Tujuan:** Mengekspor struktur dan seluruh data tabel MySQL 8.0 ke berkas arsip `.sql.gz`.
- **Aksi Sistem:**
  - Menjalankan utilitas `mysqldump` dengan kompresi gzip.
  - Menyimpan berkas di direktori terproteksi `storage/app/backups/bimabergawa_YYYY-MM-DD_HHiiss.sql.gz`.
  - Menghapus arsip pencadangan yang berusia lebih dari 30 hari (*Rolling 30-Day Retention*).

---

## 3. Endpoint HTTP Cron Fallback & Token Keamanan

Jika server hosting mengalami restriksi akses CLI, sistem menyediakan endpoint HTTP terproteksi dengan token otentikasi rahasia (*Bearer Token / Secret Query Parameter*):

| HTTP Method | URI Path | Parameter Token Wajib | Handler Controller |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/cron/run-scheduler` | `?cron_token=SECURE_PBB_CRON_KEY_2026` | `CronController@runScheduler` |
| `POST`| `/api/cron/trigger-backup`| `Header: X-CRON-KEY: SECURE_PBB_CRON_KEY_2026` | `CronController@triggerBackup` |

### Logika Verifikasi Token Keamanan (`CronController.php`)

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function runScheduler(Request $request)
    {
        $expectedToken = config('services.cron.secret_token');
        $providedToken = $request->query('cron_token') ?? $request->header('X-CRON-KEY');

        if (!$providedToken || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Token Cron tidak valid.'
            ], 403);
        }

        Artisan::call('schedule:run');

        return response()->json([
            'success' => true,
            'message' => 'Artisan scheduler berhasil dieksekusi.',
            'output'  => Artisan::output()
        ]);
    }
}
```
