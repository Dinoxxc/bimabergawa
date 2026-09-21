<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FileSecurityException;
use App\Exceptions\InvalidFileMagicBytesException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SecureFileUploadService
{
    /**
     * Peta kategori tipe MIME resmi yang diizinkan sistem B2B PBB.
     */
    public const ALLOWED_MIME_TYPES = [
        'pdf' => [
            'application/pdf',
        ],
        'spreadsheet' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel',                                         // .xls
            'application/zip',                                                  // beberapa engine membaca xlsx sebagai zip
        ],
        'word' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/msword',                                                     // .doc
            'application/zip',
        ],
        'image' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ];

    /**
     * Ekstensi berkas berbahaya yang dilarang keras, termasuk dalam eksploitasi ekstensi ganda.
     */
    public const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'sh', 'bash', 'exe', 'bat', 'cmd', 'bin', 'dll', 'vbs', 'js',
        'pl', 'cgi', 'py', 'jar', 'jsp', 'asp', 'aspx',
    ];

    /**
     * Validasi mendalam berkas mencakup ukuran, karakter nama, ekstensi ganda,
     * tipe MIME kernel via finfo, signature Magic Byte biner, dan proteksi PHP embedded.
     *
     * @param  UploadedFile|string  $file  Instance UploadedFile atau path mutlak berkas di filesystem
     * @param  array<int, string>  $allowedCategories  Kategori yang diizinkan ('pdf', 'spreadsheet', 'word', 'image')
     * @param  int  $maxSizeKb  Batas ukuran maksimum dalam Kilobytes (default 5120 KB = 5 MB)
     * @return array{valid: bool, mime: string, extension: string, size_bytes: int}
     *
     * @throws FileSecurityException
     * @throws InvalidFileMagicBytesException
     */
    public static function validateFile(
        UploadedFile|string $file,
        array $allowedCategories = ['pdf', 'spreadsheet', 'word', 'image'],
        int $maxSizeKb = 5120
    ): array {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $clientExtension = $file instanceof UploadedFile
            ? strtolower($file->getClientOriginalExtension())
            : strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // 1. Validasi Keberadaan & Ukuran Dasar (TC-SEC-UPL-015 & TC-SEC-UPL-010)
        if (! file_exists($realPath)) {
            throw new FileSecurityException(
                message: 'Berkas fisik tidak ditemukan pada sistem penyimpanan sementara.',
                fileName: $originalName,
                violationType: 'missing_file'
            );
        }

        $sizeBytes = filesize($realPath);
        if ($sizeBytes === false || $sizeBytes === 0) {
            throw new FileSecurityException(
                message: 'Berkas tidak boleh kosong (0 bytes).',
                fileName: $originalName,
                violationType: 'empty_file'
            );
        }

        $maxSizeBytes = $maxSizeKb * 1024;
        if ($sizeBytes > $maxSizeBytes) {
            throw new FileSecurityException(
                message: sprintf('Ukuran berkas lampiran melebihi batas maksimum (%d KB).', $maxSizeKb),
                fileName: $originalName,
                violationType: 'size_exceeded'
            );
        }

        // 2. Proteksi Null Byte Injection (TC-SEC-UPL-009)
        if (str_contains($originalName, "\0") || str_contains($originalName, '%00')) {
            Log::warning(sprintf(
                '[SECURITY_FILE_UPLOAD_BLOCKED] Null byte injection terdeteksi pada berkas: [%s] (IP: %s)',
                $originalName,
                request()->ip() ?? 'CLI'
            ));

            throw new FileSecurityException(
                message: 'Nama berkas memuat karakter null byte yang berbahaya.',
                fileName: $originalName,
                violationType: 'null_byte_injection'
            );
        }

        // 3. Proteksi Ekstensi Berbahaya Tunggal & Ekstensi Ganda (TC-SEC-UPL-007, 008)
        if (in_array($clientExtension, self::DANGEROUS_EXTENSIONS, true)) {
            Log::warning(sprintf(
                '[SECURITY_FILE_UPLOAD_BLOCKED] Berkas dengan ekstensi terlarang ditolak: [%s] (IP: %s)',
                $originalName,
                request()->ip() ?? 'CLI'
            ));

            throw new FileSecurityException(
                message: sprintf('Ekstensi berkas [%s] dilarang keras demi keamanan sistem.', $clientExtension),
                fileName: $originalName,
                violationType: 'forbidden_extension'
            );
        }

        self::assertNoDoubleExtension($originalName);

        // 4. Inspeksi Tipe MIME Kernel via PHP finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo !== false ? (string) finfo_file($finfo, $realPath) : 'application/octet-stream';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        // Kumpulkan daftar MIME yang diharapkan dari kategori yang diizinkan
        $expectedMimes = [];
        foreach ($allowedCategories as $cat) {
            if (isset(self::ALLOWED_MIME_TYPES[$cat])) {
                $expectedMimes = array_merge($expectedMimes, self::ALLOWED_MIME_TYPES[$cat]);
            }
        }
        $expectedMimes = array_values(array_unique($expectedMimes));

        // 5. Verifikasi Signature Magic Byte Biner (TC-SEC-UPL-001 s.d. 003)
        self::verifyBinaryMagicBytes($realPath, $detectedMime, $clientExtension, $expectedMimes, $originalName);

        // 6. Proteksi Terhadap Payload Skrip Tersembunyi (Anti-RCE & PHP Masquerading pada seluruh berkas)
        self::assertNoEmbeddedScriptPayload($realPath, $originalName);

        return [
            'valid' => true,
            'mime' => $detectedMime,
            'extension' => $clientExtension,
            'size_bytes' => $sizeBytes,
        ];
    }

    /**
     * Simpan berkas dokumen sensitif (BOQ, KTP, PO) ke disk privat non-publik.
     * Nama berkas asli dibuang dan digantikan UUID v4 penuh guna mencegah path traversal.
     *
     * @param  UploadedFile|string  $file  Berkas yang diunggah
     * @param  string  $directory  Sub-direktori penyimpanan di dalam disk private
     * @param  int  $maxSizeKb  Batas ukuran dalam KB (default 5120 = 5 MB)
     * @return string Path relatif di dalam disk private (contoh: 'documents/e4b3c2a1-....pdf')
     */
    public static function storePrivateDocument(
        UploadedFile|string $file,
        string $directory = 'documents',
        int $maxSizeKb = 5120
    ): string {
        $meta = self::validateFile($file, ['pdf', 'spreadsheet', 'word', 'image'], $maxSizeKb);

        $extension = $meta['extension'] !== '' ? $meta['extension'] : 'bin';
        $safeFileName = Str::uuid()->toString().'.'.$extension;
        $cleanDirectory = self::sanitizeDirectoryPath($directory);
        $targetPath = ($cleanDirectory !== '' ? $cleanDirectory.'/' : '').$safeFileName;

        if ($file instanceof UploadedFile) {
            Storage::disk('private')->putFileAs($cleanDirectory, $file, $safeFileName);
        } else {
            $contents = file_get_contents($file);
            if ($contents === false) {
                throw new FileSecurityException('Gagal membaca konten berkas sumber.');
            }
            Storage::disk('private')->put($targetPath, $contents);
        }

        return $targetPath;
    }

    /**
     * Simpan citra publik ke disk publik dengan nama UUID v4 penuh.
     */
    public static function storePublicImage(
        UploadedFile|string $file,
        string $directory = 'catalog',
        int $maxSizeKb = 2048
    ): string {
        $meta = self::validateFile($file, ['image'], $maxSizeKb);

        $extension = $meta['extension'] !== '' ? $meta['extension'] : 'webp';
        $safeFileName = Str::uuid()->toString().'.'.$extension;
        $cleanDirectory = self::sanitizeDirectoryPath($directory);
        $targetPath = ($cleanDirectory !== '' ? $cleanDirectory.'/' : '').$safeFileName;

        if ($file instanceof UploadedFile) {
            Storage::disk('public')->putFileAs($cleanDirectory, $file, $safeFileName);
        } else {
            $contents = file_get_contents($file);
            if ($contents === false) {
                throw new FileSecurityException('Gagal membaca konten berkas citra.');
            }
            Storage::disk('public')->put($targetPath, $contents);
        }

        return $targetPath;
    }

    /**
     * Sanitasi mutlak direktori penyimpanan mencegah serangan nested path traversal (....//).
     */
    public static function sanitizeDirectoryPath(string $directory): string
    {
        $normalized = str_replace('\\', '/', $directory);
        $rawSegments = explode('/', $normalized);

        $cleanSegments = [];
        foreach ($rawSegments as $segment) {
            $trimmed = trim($segment);
            if ($trimmed === '' || preg_match('/^\.+$/', $trimmed)) {
                continue;
            }
            $safe = (string) preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $trimmed);
            if ($safe !== '' && ! preg_match('/^\.+$/', $safe)) {
                $cleanSegments[] = $safe;
            }
        }

        return implode('/', $cleanSegments);
    }

    /**
     * Buat Temporary Signed URL untuk mengakses berkas privat dengan masa kedaluwarsa ketat.
     * (Rujukan: TC-SEC-UPL-006, kedaluwarsa default 10 menit).
     */
    public static function generateTemporaryUrl(string $path, int $expirationMinutes = 10): string
    {
        $cleanPath = self::sanitizeDirectoryPath($path);

        return URL::temporarySignedRoute(
            'private.file.download',
            now()->addMinutes($expirationMinutes),
            ['path' => $cleanPath]
        );
    }

    /**
     * Sanitasi berkas citra vektor SVG dari skrip berbahaya (Stored XSS mitigation).
     * (Rujukan: TC-SEC-UPL-012).
     */
    public static function sanitizeSvg(string $svgContent): string
    {
        // 1. Hapus deklarasi DTD Entity yang berpotensi XXE
        $sanitized = (string) preg_replace('/<!ENTITY\s+.*?>/is', '', $svgContent);

        // 2. Hapus tag <script>...</script>
        $sanitized = (string) preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $sanitized);

        // 3. Hapus inline event handlers (contoh: onload=, onerror=, onclick=, onmouseover=)
        $sanitized = (string) preg_replace('/\s+on[a-z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $sanitized);

        // 4. Hapus protokol berbahaya (javascript:, data:text/html, vbscript:) pada atribut href / xlink:href
        $sanitized = (string) preg_replace('/(href|xlink:href)\s*=\s*(["\'])(?:javascript|vbscript|data\s*:\s*text\/html).*?\2/is', '', $sanitized);

        return $sanitized;
    }

    /**
     * Hapus berkas privat dari disk private.
     */
    public static function deletePrivateFile(string $path): bool
    {
        $cleanPath = trim(str_replace(['..', '\\'], ['', '/'], $path), '/');

        return Storage::disk('private')->delete($cleanPath);
    }

    /**
     * Verifikasi header Magic Byte biner sesuai standar ISO dan spesifikasi berkas.
     */
    protected static function verifyBinaryMagicBytes(
        string $filePath,
        string $detectedMime,
        string $extension,
        array $expectedMimes,
        string $originalName
    ): void {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new FileSecurityException('Gagal membuka berkas biner untuk inspeksi header.', $originalName);
        }

        $header16 = (string) fread($handle, 16);
        fclose($handle);

        $len = strlen($header16);

        // Cek jika berkas terdeteksi sebagai teks PHP secara eksplisit
        if (str_contains($detectedMime, 'php') || str_starts_with($detectedMime, 'text/x-php')) {
            throw new InvalidFileMagicBytesException($detectedMime, $expectedMimes, $originalName);
        }

        // Signature Magic Bytes:
        // PDF: '%PDF-' (0x25 0x50 0x44 0x46)
        $isPdf = ($len >= 4 && str_starts_with($header16, '%PDF-'));

        // ZIP / XLSX / DOCX: 'PK\x03\x04' (0x50 0x4B 0x03 0x04)
        $isZipOrOffice = ($len >= 4 && str_starts_with($header16, "PK\x03\x04"));

        // Legacy MS Office (DOC, XLS): OLE Compound File (0xD0 0xCF 0x11 0xE0 0xA1 0xB1 0x1A 0xE1)
        $isLegacyOffice = ($len >= 8 && str_starts_with($header16, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"));

        // JPEG: 0xFF 0xD8 0xFF
        $isJpeg = ($len >= 3 && str_starts_with($header16, "\xFF\xD8\xFF"));

        // PNG: 0x89 'PNG' 0x0D 0x0A 0x1A 0x0A
        $isPng = ($len >= 8 && str_starts_with($header16, "\x89PNG\x0D\x0A\x1A\x0A"));

        // WebP: 'RIFF' di byte 0-3 dan 'WEBP' di byte 8-11
        $isWebp = ($len >= 12 && str_starts_with($header16, 'RIFF') && substr($header16, 8, 4) === 'WEBP');

        $hasValidBinaryHeader = $isPdf || $isZipOrOffice || $isLegacyOffice || $isJpeg || $isPng || $isWebp;

        // Jika ekstensi mendeklarasikan tipe tertentu namun header biner tidak cocok
        if ($extension === 'pdf' && ! $isPdf) {
            throw new InvalidFileMagicBytesException($detectedMime, ['application/pdf'], $originalName);
        }

        if (in_array($extension, ['xlsx', 'docx'], true) && ! $isZipOrOffice) {
            throw new InvalidFileMagicBytesException($detectedMime, ['application/vnd.openxmlformats-officedocument'], $originalName);
        }

        if (in_array($extension, ['jpg', 'jpeg'], true) && ! $isJpeg) {
            throw new InvalidFileMagicBytesException($detectedMime, ['image/jpeg'], $originalName);
        }

        if ($extension === 'png' && ! $isPng) {
            throw new InvalidFileMagicBytesException($detectedMime, ['image/png'], $originalName);
        }

        if ($extension === 'webp' && ! $isWebp) {
            throw new InvalidFileMagicBytesException($detectedMime, ['image/webp'], $originalName);
        }

        // Khusus berkas arsip OpenXML (XLSX, DOCX) dengan signature PK\x03\x04:
        // Beberapa database finfo lingkungan server mengidentifikasi arsip sebagai application/zip atau application/octet-stream
        if (in_array($extension, ['xlsx', 'docx'], true) && $isZipOrOffice) {
            if ($detectedMime === 'application/octet-stream' || str_contains($detectedMime, 'zip') || in_array($detectedMime, $expectedMimes, true)) {
                return;
            }
        }

        if (! $hasValidBinaryHeader || ! in_array($detectedMime, $expectedMimes, true)) {
            throw new InvalidFileMagicBytesException($detectedMime, $expectedMimes, $originalName);
        }
    }

    /**
     * Memastikan nama berkas tidak memiliki ekstensi ganda yang berbahaya.
     */
    protected static function assertNoDoubleExtension(string $fileName): void
    {
        $parts = explode('.', $fileName);
        if (count($parts) > 2) {
            // Periksa seluruh segmen ekstensi di tengah nama berkas (contoh: shell.php.pdf)
            for ($i = 1; $i < count($parts) - 1; $i++) {
                $subExt = strtolower(trim($parts[$i]));
                if (in_array($subExt, self::DANGEROUS_EXTENSIONS, true)) {
                    throw new FileSecurityException(
                        message: sprintf('Berkas ditolak karena eksploitasi ekstensi ganda terdeteksi (segmen .%s terlarang).', $subExt),
                        fileName: $fileName,
                        violationType: 'double_extension'
                    );
                }
            }
        }
    }

    /**
     * Memeriksa keberadaan tag skrip eksekusi tersembunyi (PHP/ASP) di seluruh konten biner berkas.
     * Menggunakan pembacaan aliran chunk 64 KB dengan overlap 64 byte guna mendeteksi pola di setiap offset.
     */
    protected static function assertNoEmbeddedScriptPayload(string $filePath, string $originalName): void
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return;
        }

        $forbiddenPatterns = [
            '<?php',
            '<?=',
            '<script language="php"',
            "<script language='php'",
            '<%',
        ];

        $overlap = '';
        while (! feof($handle)) {
            $chunk = fread($handle, 65536);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $haystack = strtolower($overlap.$chunk);
            foreach ($forbiddenPatterns as $pattern) {
                if (str_contains($haystack, $pattern)) {
                    fclose($handle);

                    Log::warning(sprintf(
                        '[SECURITY_FILE_UPLOAD_BLOCKED] Embedded script payload [%s] terdeteksi pada berkas: [%s] (IP: %s)',
                        $pattern,
                        $originalName,
                        request()->ip() ?? 'CLI'
                    ));

                    throw new FileSecurityException(
                        message: 'Berkas memuat pola skrip eksekusi terlarang di dalam konten biner.',
                        fileName: $originalName,
                        violationType: 'malicious_code_detected'
                    );
                }
            }

            // Simpan 64 byte terakhir dari chunk untuk mencegah pola terputus di batas chunk
            $overlap = substr($chunk, -64);
        }

        fclose($handle);
    }
}
