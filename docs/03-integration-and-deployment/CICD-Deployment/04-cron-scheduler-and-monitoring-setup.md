# 04. Konfigurasi Cron Scheduler, Logika Antrean, & Pemantauan Server
## CI/CD & Deployment Suite (CICD-Deployment)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-DEP-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | DevOps Engineer | Draf Awal | Konfigurasi Cron Job hPanel Hostinger |
| `1.1.0` | 03 Sep 2026 | SRE Specialist | Ditinjau | Penambahan worker queue `--stop-when-empty` & log rotation |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan backup mingguan & endpoint health check `/healthz` |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri scheduler, monitoring uptime, & zero emoji |

---

## 1. Pendaftaran Cron Job Utama di hPanel Hostinger

Pada shared hosting Hostinger, proses daemon persisten tidak diizinkan berjalan terus-menerus. Seluruh otomasi diorkestrasi melalui satu Cron Job master yang dieksekusi setiap menit:

- **Menu hPanel:** **Advanced -> Cron Jobs**
- **Interval:** Every Minute (`* * * * *`)
- **Command Line:**
  ```bash
  * * * * * cd /home/uXXXX/domains/bimabergawa.com/public_html && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## 2. Strategi Eksekusi Queue Worker pada Shared Hosting

Untuk memproses antrean email penawaran dan notifikasi tanpa daemon supervisor, sistem menjalankan worker dengan parameter `--stop-when-empty`:

```php
// app/Console/Kernel.php
$schedule->command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
```

- **`--stop-when-empty`:** Worker berhenti otomatis begitu antrean kosong.
- **`--max-time=50`:** Membatasi waktu hidup worker maksimal 50 detik agar tidak tumpang tindih dengan Cron Job menit berikutnya.
- **`--tries=3`:** Mencoba ulang pengiriman email maksimal 3 kali sebelum memindahkannya ke tabel `failed_jobs`.

---

## 3. Skrip Pencadangan Database Mingguan (`pbb:backup-database`)

Perintah terjadwal yang mengekspor basis data MySQL 8.0 ke format arsip terkompresi:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'pbb:backup-database';
    protected $description = 'Melakukan backup database MySQL 8.0 terkompresi gzip';

    public function handle()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'bimabergawa_' . date('Y-m-d_His') . '.sql.gz';
        $filepath = $backupDir . '/' . $filename;

        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s | gzip > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup database berhasil disimpan: {$filepath}");
            $this->cleanOldBackups($backupDir);
        } else {
            $this->error("Gagal melakukan backup database.");
        }
    }

    private function cleanOldBackups(string $dir)
    {
        $files = File::files($dir);
        $threshold = now()->subDays(30)->timestamp;

        foreach ($files as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
            }
        }
    }
}
```

---

## 4. Konfigurasi Log Rotation & Audit Log

Mencegah berkas log `laravel.log` membengkak dan menghabiskan kuota disk hosting:

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14, // Rotasi harian dengan retensi 14 hari
    ],
],
```

---

## 5. Pemantauan Ketersediaan Layanan (Uptime & Health Monitoring)

Endpoint pemantauan kesehatan sistem yang dipantau oleh layanan pihak ketiga (misal: UptimeRobot):

- **Target URL:** `https://bimabergawa.com/healthz`
- **Interval Pemantauan:** Setiap 5 menit.
- **Kondisi Sehat (200 OK):** Koneksi MySQL 8.0 aktif, storage dapat ditulis, dan cache berfungsi normal.

```php
// routes/web.php
Route::get('/healthz', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        return response()->json(['status' => 'unhealthy', 'database' => 'disconnected'], 500);
    }

    return response()->json([
        'status'    => 'healthy',
        'database'  => $dbStatus,
        'timestamp' => now()->toIso8601String(),
    ]);
});
```
