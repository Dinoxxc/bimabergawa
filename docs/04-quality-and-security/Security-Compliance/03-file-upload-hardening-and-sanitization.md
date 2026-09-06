# 03. Pengerasan Unggahan Berkas, Validasi MIME, & Sanitasi Aset
## Security Policy & Regulatory Compliance Suite (Security-Compliance)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SEC-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Security Engineer | Draf Awal | Aturan validasi ekstensi berkas dasar |
| `1.1.0` | 03 Sep 2026 | Application Security Lead | Ditinjau | Penambahan inspeksi Magic Byte & isolasi private disk |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan alur konversi WebP & sanitasi metadata EXIF |
| `1.3.0` | 05 Sep 2026 | Principal Security Architect | Disetujui | Pemisahan berkas mandiri pengerasan unggahan berkas & zero emoji |

---

## 1. Analisis Vektor Ancaman Unggahan Berkas (Threat Vectors)

Sistem menangani berbagai jenis berkas yang diunggah baik dari publik (Lampiran BOQ/Tender pada form inkuiri) maupun internal (KTP/NPWP Agen, Foto Produk, Berkas Excel Import). Setiap berkas masuk diklasifikasikan sebagai potensi ancaman tingkat tinggi:

1. **Remote Code Execution (RCE):** Penyerang mengunggah berkas skrip PHP dengan ekstensi ganda atau menyamarkan header gambar untuk dieksekusi oleh engine web server.
2. **Stored Cross-Site Scripting (XSS):** Penyisipan skrip berbahaya JavaScript di dalam tag berkas vektor SVG atau metadata gambar.
3. **Zip/Excel Bomb & Memory Exhaustion:** Pengunggahan berkas arsip atau spreadsheet raksasa dengan jutaan baris untuk melumpuhkan alokasi memori RAM (512 MB) server cPanel/LiteSpeed.
4. **Path Traversal & Arbitrary File Overwrite:** Pemanfaatan karakter `../` pada nama berkas asli untuk menimpa berkas sistem atau konfigurasi kritis Laravel (`.env`).

---

## 2. Validasi Lapisan Ganda (Magic Byte & MIME Inspection)

Sistem menolak validasi yang hanya bersandar pada ekstensi nama berkas (`getClientOriginalExtension()`). Seluruh validasi wajib menggunakan inspeksi *Magic Byte* biner di level PHP kernel (`fileinfo` / `mime_content_type`):

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePublicInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'boq_attachment' => [
                'nullable',
                File::types(['pdf', 'xlsx', 'xls', 'docx', 'jpg', 'jpeg', 'png'])
                    ->max(5 * 1024), // Maksimal 5 MB
            ],
        ];
    }
}
```

### Matriks Validasi Biner & Magic Byte

| Format Berkas | Ekstensi Resmi | Header Magic Byte Hexadecimal | Batas Ukuran Maks | Disk Penyimpanan |
| :--- | :--- | :--- | :---: | :--- |
| **Dokumen PDF** | `.pdf` | `%PDF-` (`25 50 44 46`) | 5 MB | Private Disk (`local`) |
| **Excel Spreadsheet** | `.xlsx` | `PK..` (`50 4B 03 04`) | 10 MB | Private Disk (`local`) |
| **Word Document** | `.docx` | `PK..` (`50 4B 03 04`) | 5 MB | Private Disk (`local`) |
| **Gambar Produk** | `.jpg`, `.jpeg` | `FF D8 FF` | 2 MB | Public Disk (`public`) via WebP Pipeline |
| **Gambar Produk** | `.png` | `89 50 4E 47 0D 0A 1A 0A` | 2 MB | Public Disk (`public`) via WebP Pipeline |
| **Gambar WebP** | `.webp` | `RIFF....WEBP` (`52 49 46 46`) | 2 MB | Public Disk (`public`) |
| **Identitas KTP/NPWP** | `.jpg`, `.png`, `.pdf` | Sesuai Magic Byte masing-masing | 2 MB | Private Encrypted Disk |

---

## 3. Isolasi Jalur Penyimpanan & Random UUID Renaming

Untuk mencegah *Path Traversal* dan *File Enumeration*, nama berkas asli dari pengguna **selalu dibuang** dan digantikan dengan string acak *UUID v4*:

```php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SecureFileUploadService
{
    public function storePrivateDocument(UploadedFile $file, string $directory): string
    {
        // 1. Amankan nama dengan UUID v4 baru dan sanitasi ekstensi
        $safeExtension = strtolower($file->getClientOriginalExtension());
        $sanitizedFileName = Str::uuid()->toString() . '.' . $safeExtension;

        // 2. Simpan pada private disk non-public (tidak dapat diakses langsung via URL)
        $storedPath = $file->storeAs($directory, $sanitizedFileName, 'private');

        return $storedPath;
    }
}
```

---

## 4. Pipeline Konversi WebP & Stripping Metadata EXIF

Seluruh aset gambar katalog yang diunggah melalui panel admin Filament wajib melewati pipeline optimasi menggunakan library `Intervention/Image`:

1. **Stripping EXIF & GPS Location Data:** Seluruh metadata kamera, koordinat GPS pemotretan, dan tag informasi rahasia otomatis dihapus saat proses rasterisasi.
2. **Standardisasi Format WebP:** Format sumber (JPEG/PNG) dikonversi menjadi WebP murni dengan tingkat kompresi 82% untuk menghemat *bandwidth* dan mempercepat *render*.
3. **Penskalaan Resolusi Maksimal:** Dimensi gambar dibatasi maksimal lebar 1600 piksel dengan mempertahankan aspek rasio proporsional (*aspect ratio constraint*).

```php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function processAndStore(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Penskalaan proporsional jika melebihi 1600px
        $image->scaleDown(width: 1600);

        // Konversi ke WebP kualitas 82% (otomatis membersihkan EXIF)
        $encodedWebp = $image->toWebp(quality: 82);

        $fileName = 'catalog/' . Str::uuid()->toString() . '.webp';
        Storage::disk('public')->put($fileName, (string) $encodedWebp);

        return $fileName;
    }
}
```

---

## 5. Pengerasan Direktori Unggahan Web Server (.htaccess)

Sebagai perlindungan garis pertahanan berlapis (*Defense-in-Depth*), folder publik tempat berkas diunggah dikunci secara ketat menggunakan berkas `.htaccess` untuk menonaktifkan eksekusi skrip server:

```apache
# Lokasi: public/storage/.htaccess
# Mencegah eksekusi skrip PHP atau CGI di dalam direktori penyimpanan publik

<FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|phar|sh|pl|cgi)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar
```
