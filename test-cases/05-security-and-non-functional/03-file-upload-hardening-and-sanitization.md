# Test Cases: Pengerasan Unggahan Berkas, Magic Bytes, & Proteksi RCE
## Domain 05: Keamanan & Pengujian Non-Fungsional (Security & NFR)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-SEC-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Security/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Application Security QA | Draf Awal | Pengujian validasi ekstensi berkas dasar |
| `1.1.0` | 03 Sep 2026 | Penetration Testing Engineer | Ditinjau | Penambahan uji inspeksi Magic Byte, mitigasi RCE, & sanitasi SVG |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan isolasi private disk & konfigurasi pengerasan `.htaccess` |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SEC-03` (Spesifikasi Pengerasan Unggahan Berkas & Validasi MIME)
  - `DOC-PBB-QA-01` (Matriks Uji Unit & Feature Security Pest/PHPUnit)
  - `DOC-PBB-SDD-03` (Service Layer Media Processing & Secure File Storage)
  - `DOC-PBB-SRS-05` (Kebutuhan Non-Fungsional NFR-SEC-03 Proteksi Berkas)
- **Modul Sistem:** Modul Validasi, Sanitasi, & Penyimpanan Berkas Aman
- **Target Kelas / Service / Middleware:**
  - `App\Services\SecureFileUploadService`
  - `App\Services\ProductImageProcessingService`
  - `App\Http\Requests\StorePublicInquiryRequest`
  - Konfigurasi Web Server `public/storage/.htaccess`
- **Target Method:** `validateMagicBytes()`, `storePrivateDocument()`, `sanitizeSvg()`, `stripExif()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-SEC-UPL-001`** | Positif | Validasi Magic Byte Dokumen PDF Asli (`%PDF-`) | Form inkuiri / BOQ dibuka | Unggah berkas PDF biner sah (Magic Byte: `25 50 44 46`) | Berkas lolos validasi, tersimpan di private storage dengan nama UUID baru, ukuran berkas tervalidasi <= 5 MB. | Not Executed |
| **`TC-SEC-UPL-002`** | Positif | Validasi Magic Byte Spreadsheet Excel Asli (`PK..`) | Form import katalog dibuka | Unggah berkas XLSX asli (Magic Byte: `50 4B 03 04`) | Berkas lolos validasi, diproses oleh engine import tanpa hambatan (*valid binary archive*). | Not Executed |
| **`TC-SEC-UPL-003`** | Positif | Validasi Magic Byte Citra JPEG Asli (`FF D8 FF`) | Form foto produk katalog | Unggah foto JPEG asli dari kamera | Lolos validasi, diteruskan ke pipeline konversi WebP, metadata EXIF dibersihkan. | Not Executed |
| **`TC-SEC-UPL-004`** | Positif | Penamaan Berkas Acak UUID v4 Penuh (*Full UUID Renaming*) | Nama berkas asli: `../../dokumen_rahasia_user.pdf` | Simpan berkas | Sistem membuang seluruh nama asli dan menyimpan sebagai `e4b3c2a1-0000-4000-8000-123456789abc.pdf`. | Not Executed |
| **`TC-SEC-UPL-005`** | Positif | Isolasi Berkas Sensitif pada Private Disk Non-Public | KTP agen dan BOQ klien diunggah | Simpan berkas | Berkas disimpan di path `storage/app/private/` (di luar `public_html`), tidak memiliki direct URL publik. | Not Executed |
| **`TC-SEC-UPL-006`** | Positif | Akses Berkas Privat via Temporary Signed URL (Expired 10 Menit) | Admin ingin melihat KTP / BOQ | Klik "Lihat Dokumen" | URL terenkripsi sementara dihasilkan (`/private-docs/{id}?signature=...&expires=...`), kedaluwarsa tepat setelah 10 menit. | Not Executed |
| **`TC-SEC-UPL-007`** | Negatif | Penolakan Skrip PHP Menyamar Ekstensi PDF (*PHP Masquerading*) | Penyerang mengunggah skrip PHP dinamai `shell.php.pdf` | Header berkas berupa teks `<?php echo 'RCE'; ?>` | Pemeriksaan `finfo` / Magic Byte mendeteksi berkas sebagai `text/x-php`, unggahan ditolak seketika (HTTP 422). | Not Executed |
| **`TC-SEC-UPL-008`** | Negatif | Penolakan Berkas Eksekusi Ganda (*Double Extension Exploit*) | Form upload dibuka | Unggah berkas `exploit.php.jpg` atau `backdoor.phar.png` | Validasi tipe MIME dan ekstensi ganda membatalkan unggahan, berkas tidak disimpan di filesystem. | Not Executed |
| **`TC-SEC-UPL-009`** | Negatif | Penolakan Karakter Null Byte Injection pada Nama Berkas | Form upload berkas | Nama berkas: `shell.php%00.pdf` | Sanitizer nama berkas membuang karakter null byte (`\0`), sistem mengevaluasi ekstensi asli dan menolak berkas. | Not Executed |
| **`TC-SEC-UPL-010`** | Negatif | Penolakan Berkas Melebihi Batas Ukuran Maksimum (> 5 MB) | Form inkuiri publik | Unggah berkas PDF ukuran 5.5 MB | Validasi ukuran `File::max(5120)` menolak berkas dengan pesan: "Ukuran berkas lampiran maksimal adalah 5 MB". | Not Executed |
| **`TC-SEC-UPL-011`** | Keamanan | Penonaktifan Eksekusi Skrip PHP pada Direktori Storage (`.htaccess`) | Penyerang berhasil menempatkan berkas `.php` di folder publik | Akses langsung URL: `https://bimabergawa.com/storage/catalog/test.php` | Server LiteSpeed memblokir eksekusi via aturan `.htaccess` (`php_flag engine off` / `Deny from all`), mengembalikan HTTP 403. | Not Executed |
| **`TC-SEC-UPL-012`** | Keamanan | Sanitasi Berkas Vektor SVG dari Skrip XSS Berbahaya | Form upload logo brand | Unggah SVG memuat tag `<script>alert('PBB-XSS')</script>` | Sanitizer XML/SVG memindai dan menghapus seluruh tag skrip berbahaya serta atribut inline event (`onload=`, `onerror=`). | Not Executed |
| **`TC-SEC-UPL-013`** | Keamanan | Perlindungan Terhadap Zip Bomb / Decompression Bomb | Form import Excel | Unggah berkas spreadsheet 10 KB yang mengembang jadi 5 GB | Parser membatasi alokasi kuota memori maksimum (512 MB) dan menghentikan proses parsing jika mendeteksi anomali ekspansi. | Not Executed |
| **`TC-SEC-UPL-014`** | Audit | Pencatatan Percobaan Unggahan Berkas Berbahaya ke Security Log | Penyerang mencoba mengunggah payload shell | Unggahan terdeteksi berbahaya | Sistem mencatat insiden ke tabel `security_audit_logs` memuat: IP, User-Agent, Nama Asli Berkas, dan Payload Magic Byte. | Not Executed |
| **`TC-SEC-UPL-015`** | Batas Nilai | Penanganan Berkas Unggahan 0 Bytes (*Empty File*) | Form unggah berkas | Unggah berkas kosong ukuran 0 bytes | Sistem menolak dengan validasi: "Berkas tidak boleh kosong (0 bytes)". | Not Executed |
| **`TC-SEC-UPL-016`** | Pembersihan | Otomasi Pembersihan Berkas Sementara (*Orphan File Cleanup*) | Berkas diunggah di form namun transaksi dibatalkan | Berkas tersimpan di folder temp > 24 jam | Perintah cron artisan `php artisan pbb:clean-temp-uploads` menghapus berkas sementara yang tidak terasosiasi dengan data DB. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-SEC-UPL-007 (Uji Penolakan PHP Masquerading via Magic Byte)

1. **Langkah 1:** Buat berkas uji biner palsu:
   ```bash
   echo "<?php phpinfo(); ?>" > evil_payload.php.pdf
   ```
2. **Langkah 2:** Kirimkan berkas tersebut ke form inkuiri publik via curl:
   ```bash
   curl -F "company_name=Test PT" -F "email=test@kontraktor.co.id" -F "boq_attachment=@evil_payload.php.pdf" https://bimabergawa.com/api/inquiries
   ```
3. **Verifikasi Teknis:**
   - Server mengembalikan status `HTTP 422 Unprocessable Entity`.
   - Pesan error: "Berkas lampiran boq attachment harus berupa berkas bertipe: pdf, xlsx, docx".
   - Tidak ada berkas yang tersimpan di disk `storage/app/`.

### 3.2 Prosedur Uji TC-SEC-UPL-011 (Verifikasi Pengerasan .htaccess di Storage)

1. **Langkah 1:** Pastikan berkas `public/storage/.htaccess` memuat konfigurasi:
   ```apache
   <FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|phar|sh|pl|cgi)$">
       Order Deny,Allow
       Deny from all
   </FilesMatch>
   Options -Indexes -ExecCGI
   ```
2. **Langkah 2:** Buat berkas statis uji `info.php` di dalam `public/storage/`.
3. **Langkah 3:** Akses `https://bimabergawa.com/storage/info.php`.
4. **Verifikasi Teknis:**
   - Server merespons dengan `403 Forbidden` (bukan mengeksekusi script PHP).
