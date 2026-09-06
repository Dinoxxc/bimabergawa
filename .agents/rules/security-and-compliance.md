# Security, Privacy & Compliance Engineering Standards
## PT. Panca Bima Bergawa - Workspace Engineering Rules

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `AGY-PBB-RULE-SECU` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Autonomous Agent & Engineering Guidelines |

---

## 1. Perlindungan Data Pribadi (Kepatuhan UU PDP No. 27/2022)

### 1.1 Enkripsi Kolom Sensitif At-Rest
Setiap data identitas kependudukan, perpajakan, dan perbankan milik mitra agen freelance atau staf internal wajib dienkripsi sebelum disimpan ke dalam basis data:
- **Kolom Terdampak pada Tabel `agent_profiles`:**
  - `nik_ktp` (Nomor Induk Kependudukan 16 digit)
  - `npwp_number` (Nomor Pokok Wajib Pajak 15/16 digit)
  - `bank_account_number` (Nomor Rekening Bank)
- **Mekanisme Enkripsi:** Menggunakan algoritma AES-256-CBC via Eloquent `$casts = ['encrypted']` yang memanfaatkan kunci utama aplikasi (`APP_KEY`).
- **Larangan Pencatatan Plaintext:** Dilarang mencatat nilai NIK, NPWP, atau nomor rekening dalam file log aplikasi (`Log::info()`), pesan error exception, maupun dump sesi debug.

---

## 2. Otentikasi Passwordless Email OTP & Brute-Force Defense

Panel ERP `app.bimabergawa.com` menerapkan mekanisme otentikasi tanpa kata sandi untuk mencegah kebocoran kredensial (*credential stuffing*):

### 2.1 Spesifikasi Teknis OTP
1. **Pembangkitan Kode OTP:** Wajib menggunakan *Cryptographically Secure Pseudo-Random Number Generator* (CSPRNG): `random_int(100000, 999999)`.
2. **Penyimpanan Cache:** Kode OTP tidak disimpan dalam basis data plaintext, melainkan di-hash dengan SHA-256 dan disimpan pada Cache backend:
   ```php
   $otpHash = hash('sha256', (string) $plainOtp);
   Cache::put("erp_otp:{$email}", [
       'hash' => $otpHash,
       'attempts' => 0,
   ], now()->addSeconds(300)); // Masa aktif 5 menit
   ```
3. **Masa Berlaku (TTL):** Tepat **300 detik (5 menit)**. Melebihi batas waktu tersebut, cache otomatis hangus.
4. **Cooldown Kirim Ulang:** Tombol kirim ulang OTP terkunci selama **60 detik** sejak pengiriman terakhir (`Cache::has("erp_otp_cooldown:{$email}")`).
5. **Pembatasan Percobaan (Throttle Lockout):** Maksimal 3 kali percobaan verifikasi yang salah. Pada percobaan ke-4, sesi terkunci selama 10 menit.

---

## 3. Validasi Unggahan Berkas & Inspeksi Magic Bytes

Unggahan berkas BOQ (*Bill of Quantities*) dan bukti transfer bank rentan terhadap serangan *Arbitrary File Upload* dan eksekusi skrip berbahaya.

### 3.1 Verifikasi Biner Kernel (`finfo`)
Sistem dilarang mempercayai ekstensi nama file atau MIME Type dari header HTTP klien:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class SecureFileUploadService
{
    private const ALLOWED_MIME_MAP = [
        'pdf'  => ['application/pdf'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xls'  => ['application/vnd.ms-excel'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    public function validateAndStorePrivate(UploadedFile $file, string $directory): string
    {
        // 1. Verifikasi Magic Bytes biner sesungguhnya
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $clientExt = strtolower($file->getClientOriginalExtension());

        if (! isset(self::ALLOWED_MIME_MAP[$clientExt]) || ! in_array($realMime, self::ALLOWED_MIME_MAP[$clientExt], true)) {
            throw new RuntimeException('Deteksi anomali tipe berkas: Header berkas tidak sesuai dengan konten biner aslinya.');
        }

        // 2. Sanitasi Nama Berkas dengan UUID v4
        $safeFileName = Str::uuid()->toString() . '.' . $clientExt;

        // 3. Simpan di Storage Privat (non-public disk)
        return $file->storeAs($directory, $safeFileName, 'local_private');
    }
}
```

---

## 4. Isolasi Data Multi-Tenant & Pertahanan Anti-IDOR

Untuk mencegah kerentanan *Insecure Direct Object Reference* (IDOR):
1. **Mitra Agen Scope:** Mitra agen hanya diizinkan membaca dan memanipulasi data inkuiri yang memiliki `agent_id === auth()->id()`.
2. **Penerapan Global Scope:** Dilakukan otomatis melalui `AgentTenantScope` pada Eloquent model.
3. **Validasi Tambahan di Controller / Filament Page:**
   ```php
   abort_if($inquiry->agent_id !== auth()->id() && !auth()->user()->hasAnyRole(['super-admin', 'sales-pic']), 403, 'Akses Ditolak: Anda tidak memiliki wewenang atas dokumen inkuiri ini.');
   ```

---

## 5. Konfigurasi HTTP Security Headers & Pembatasan Shared Hosting

### 5.1 Security Headers pada Nginx / LiteSpeed Web Server
Aplikasi wajib menyematkan header keamanan berikut pada seluruh respon HTTP:
```http
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';
```

### 5.2 Manajemen Batas Memori Shared Hosting (512 MB RAM Safeguard)
1. **Larangan `Model::all()`:** Dilarang memuat seluruh baris tabel produk atau customer ke memori. Selalu gunakan pagination atau chunking (`Product::chunk(100, ...)` / `Product::cursor()`).
2. **Chunking Import Excel:** Import katalog Excel wajib memecah pembacaan per 100 baris menggunakan interface `WithChunkReading` pada Laravel Excel.
3. **Garbage Collection Manual:** Panggil `gc_collect_cycles()` setelah pemrosesan batch gambar WebP besar untuk membersihkan alokasi memori GD Driver seketika.
