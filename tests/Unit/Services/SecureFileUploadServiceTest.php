<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\FileSecurityException;
use App\Exceptions\InvalidFileMagicBytesException;
use App\Services\SecureFileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureFileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    /**
     * TC-SEC-UPL-001: Validasi Magic Byte Dokumen PDF Asli (%PDF-) dan penyimpanan ke private storage.
     */
    public function test_valid_pdf_magic_bytes_passes_and_stores_in_private_disk(): void
    {
        $binaryPdf = "%PDF-1.4\n1 0 obj\n<< /Title (BOQ Proyek Rasuna) >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('../../malicious_traversal_name.pdf', $binaryPdf);

        $storedPath = SecureFileUploadService::storePrivateDocument($file, 'boq', 5120);

        $this->assertNotEmpty($storedPath);
        $this->assertStringStartsWith('boq/', $storedPath);
        $this->assertStringEndsWith('.pdf', $storedPath);
        // Pastikan nama asli dibuang dan path traversal dinetralkan menjadi UUID
        $this->assertStringNotContainsString('malicious_traversal', $storedPath);
        $this->assertStringNotContainsString('..', $storedPath);

        // Verifikasi fisik pada fake private storage
        Storage::disk('private')->assertExists($storedPath);
        $this->assertSame($binaryPdf, Storage::disk('private')->get($storedPath));
    }

    /**
     * TC-SEC-UPL-002: Validasi Magic Byte Spreadsheet Excel Asli (PK..).
     */
    public function test_valid_xlsx_magic_bytes_passes_validation(): void
    {
        // Signature zip/xlsx: 50 4B 03 04
        $binaryXlsx = "PK\x03\x04\x14\x00\x06\x00DummyExcelContentDataForKatalogImport1234567890";
        $file = UploadedFile::fake()->createWithContent('katalog_pbb.xlsx', $binaryXlsx);

        $meta = SecureFileUploadService::validateFile($file, ['spreadsheet'], 10240);

        $this->assertTrue($meta['valid']);
        $this->assertSame('xlsx', $meta['extension']);
    }

    /**
     * TC-SEC-UPL-003: Validasi Magic Byte Citra JPEG Asli (FF D8 FF) dan PNG.
     */
    public function test_valid_jpeg_and_png_magic_bytes(): void
    {
        $binaryJpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x60\x00\x60\x00\x00SampleJpegContent";
        $jpegFile = UploadedFile::fake()->createWithContent('schneider_mcb.jpg', $binaryJpeg);

        $metaJpeg = SecureFileUploadService::validateFile($jpegFile, ['image']);
        $this->assertTrue($metaJpeg['valid']);

        $binaryPng = "\x89PNG\x0D\x0A\x1A\x0A\x00\x00\x00\x0DIHDRSamplePngContent";
        $pngFile = UploadedFile::fake()->createWithContent('logo_schneider.png', $binaryPng);

        $metaPng = SecureFileUploadService::validateFile($pngFile, ['image']);
        $this->assertTrue($metaPng['valid']);
    }

    /**
     * TC-SEC-UPL-007: Penolakan Skrip PHP Menyamar Ekstensi PDF (PHP Masquerading).
     */
    public function test_rejection_of_php_disguised_as_pdf(): void
    {
        $fakePdfPhp = '<?php phpinfo(); ?>';
        $file = UploadedFile::fake()->createWithContent('document.pdf', $fakePdfPhp);

        $this->expectException(InvalidFileMagicBytesException::class);
        SecureFileUploadService::validateFile($file, ['pdf']);
    }

    /**
     * TC-SEC-UPL-008: Penolakan Berkas Ekstensi Ganda (Double Extension Exploit).
     */
    public function test_rejection_of_double_extension_exploit(): void
    {
        $binaryJpeg = "\xFF\xD8\xFF\xE0SampleJpeg";
        $file = UploadedFile::fake()->createWithContent('exploit.php.jpg', $binaryJpeg);

        $this->expectException(FileSecurityException::class);
        $this->expectExceptionMessage('eksploitasi ekstensi ganda terdeteksi');

        SecureFileUploadService::validateFile($file, ['image']);
    }

    /**
     * TC-SEC-UPL-009: Penolakan Karakter Null Byte Injection pada Nama Berkas.
     */
    public function test_rejection_of_null_byte_injection(): void
    {
        $binaryPdf = '%PDF-1.4 ValidContent';
        $file = UploadedFile::fake()->createWithContent("shell.php\0.pdf", $binaryPdf);

        $this->expectException(FileSecurityException::class);
        $this->expectExceptionMessage('null byte');

        SecureFileUploadService::validateFile($file, ['pdf']);
    }

    /**
     * TC-SEC-UPL-010: Penolakan Berkas Melebihi Batas Ukuran Maksimum (> 5 MB).
     */
    public function test_rejection_of_file_exceeding_max_size(): void
    {
        // 6 MB (6144 KB) saat batas adalah 5120 KB
        $largePdf = '%PDF-1.4'.str_repeat('A', 6 * 1024 * 1024);
        $file = UploadedFile::fake()->createWithContent('giant_tender.pdf', $largePdf);

        $this->expectException(FileSecurityException::class);
        $this->expectExceptionMessage('melebihi batas maksimum');

        SecureFileUploadService::validateFile($file, ['pdf'], 5120);
    }

    /**
     * TC-SEC-UPL-015: Penolakan Berkas Kosong Ukuran 0 Bytes.
     */
    public function test_rejection_of_empty_zero_byte_file(): void
    {
        $emptyFile = UploadedFile::fake()->createWithContent('empty.pdf', '');

        $this->expectException(FileSecurityException::class);
        $this->expectExceptionMessage('tidak boleh kosong');

        SecureFileUploadService::validateFile($emptyFile, ['pdf']);
    }

    /**
     * TC-SEC-UPL-006: Pembuatan dan Akses Dokumen Privat via Temporary Signed URL (Kedaluwarsa 10 Menit).
     */
    public function test_temporary_signed_url_generation_and_access(): void
    {
        $binaryPdf = '%PDF-1.4 Official Confidential BOQ Content';
        Storage::disk('private')->put('confidential/boq-rasuna.pdf', $binaryPdf);

        $signedUrl = SecureFileUploadService::generateTemporaryUrl('confidential/boq-rasuna.pdf', 10);

        $this->assertNotEmpty($signedUrl);
        $this->assertStringContainsString('signature=', $signedUrl);
        $this->assertStringContainsString('expires=', $signedUrl);

        // Akses via signed URL yang sah
        $response = $this->get($signedUrl);
        $response->assertOk();
        $this->assertSame($binaryPdf, $response->streamedContent());
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // Akses tanpa tanda tangan sah ditolak 403 Forbidden
        $unsignedUrl = url('/private-documents/download?path=confidential/boq-rasuna.pdf');
        $this->get($unsignedUrl)->assertForbidden();
    }

    /**
     * TC-SEC-UPL-012: Sanitasi Berkas Vektor SVG dari Skrip XSS Berbahaya.
     */
    public function test_svg_sanitization_removes_xss_vectors(): void
    {
        $maliciousSvg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" onload="alert('XSS-ONLOAD')">
            <!ENTITY xxe SYSTEM "file:///etc/passwd">
            <g>
                <circle cx="50" cy="50" r="40" stroke="green" stroke-width="4" fill="yellow" onclick="alert('XSS-CLICK')" />
                <a href="javascript:alert('XSS-HREF')">Link</a>
                <script type="text/javascript">
                    alert('XSS-SCRIPT');
                </script>
            </g>
        </svg>
        SVG;

        $cleanSvg = SecureFileUploadService::sanitizeSvg($maliciousSvg);

        $this->assertStringNotContainsString('<script', $cleanSvg);
        $this->assertStringNotContainsString('alert(', $cleanSvg);
        $this->assertStringNotContainsString('onload=', $cleanSvg);
        $this->assertStringNotContainsString('onclick=', $cleanSvg);
        $this->assertStringNotContainsString('javascript:', $cleanSvg);
        $this->assertStringNotContainsString('!ENTITY', $cleanSvg);
        $this->assertStringContainsString('<circle', $cleanSvg);
    }
}
