# 04. Manajemen Media Aset WebP, Brosur PDF, & Prosedur Restore Database
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Media Specialist | Draf Awal | Struktur direktori upload gambar produk |
| `1.1.0` | 03 Sep 2026 | Frontend Architect | Ditinjau | Penambahan pipeline kompresi WebP & penyimpanan brosur PDF |
| `1.2.0` | 05 Sep 2026 | Database Administrator | Disetujui | Prosedur pemulihan bencana (Disaster Recovery) MySQL 8.0 |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri aset media, restore database, & zero emoji |

---

## 1. Struktur Direktori Penyimpanan Aset Media

Seluruh aset produk dan dokumen teknis tersimpan di dalam direktori `storage/app/public/` dan diakses publik melalui tautan symlink `/storage/`:

```
storage/app/public/
├── brands/                           # Logo prinsipal resmi (WebP)
│   ├── ebara-logo.webp
│   ├── schneider-logo.webp
│   └── danfoss-logo.webp
├── products/                         # Galeri foto produk terkompresi
│   ├── ebara/
│   │   ├── fsha-65-50-200-main.webp
│   │   └── fsha-65-50-200-dimensi.webp
│   └── schneider/
│       └── atv320u55n4b-main.webp
├── datasheets/                       # Berkas PDF lembar spesifikasi resmi
│   ├── ebara/
│   │   └── ebara-fsha-series-datasheet.pdf
│   └── schneider/
│       └── schneider-atv320-manual.pdf
└── partner_kyc/                      # Berkas KTP mitra freelance (Akses Terproteksi)
```

---

## 2. Pipeline Konversi Otomatis ke Format WebP

Setiap gambar yang diunggah melalui panel Filament dikonversi otomatis ke WebP dengan spesifikasi:

- **Dimensi Maksimal:** $1200\text{px} \times 1200\text{px}$ (*Aspect Ratio Preserved*).
- **Tingkat Kompresi:** Quality `85%` (Menghemat $60-80\%$ ukuran berkas dibanding PNG/JPG asli).
- **Latar Belakang:** Latar belakang putih murni (*Pure White* `#FFFFFF`).

```php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaOptimizerService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function optimizeProductImage(UploadedFile $file, string $destinationPath): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Skala dimensi proporsional maks 1200px
        $image->scaleDown(width: 1200, height: 1200);

        // Encode ke format WebP kualitas 85
        $encoded = $image->toWebp(quality: 85);

        // Simpan ke storage publik
        Storage::disk('public')->put($destinationPath, (string) $encoded);

        return $destinationPath;
    }
}
```

---

## 3. Prosedur Pemulihan Basis Data (Disaster Recovery & Restore)

Jika terjadi kerusakan data pada server produksi, lakukan langkah pemulihan basis data MySQL 8.0 berikut:

### Langkah 1: Siapkan Berkas Backup Terbaru
Identifikasi berkas arsip backup terbaru pada direktori `storage/app/backups/`:
```bash
ls -la /home/uXXXX/domains/bimabergawa.com/public_html/storage/app/backups/
```

### Langkah 2: Eksekusi Perintah Restore Database
```bash
# Dekompresi dan impor langsung ke MySQL produksi
gunzip < /home/uXXXX/domains/bimabergawa.com/public_html/storage/app/backups/bimabergawa_YYYY-MM-DD_HHiiss.sql.gz \
| mysql --host=127.0.0.1 --user=uXXXX_user --password="DB_PASSWORD" uXXXX_bimabergawa
```

### Langkah 3: Verifikasi Integritas Data & Re-Cache
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```
