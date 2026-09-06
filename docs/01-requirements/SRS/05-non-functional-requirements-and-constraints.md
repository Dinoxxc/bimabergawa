# 05. Kebutuhan Non-Fungsional & Batasan Sistem (NFR)
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-05` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Quality Assurance & Security Team | Draf Awal | Spesifikasi NFR-PERF (Kinerja), NFR-SEC (Keamanan), & NFR-REL (Reliabilitas) |
| `1.1.0` | 03 Sep 2026 | Security Architect | Ditinjau | Penambahan NFR-SEC-03 (Proteksi HPP) & isolasi subdomain X-Robots-Tag |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penetapan batas memori Hostinger (512MB limit) & backup bulanan |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Kebutuhan Kinerja & Waktu Respons (Performance - `NFR-PERF`)

```
+----------------------------------------------------------------------------------------------------+
|                                    PARAMETRIK KINERJA SISTEM                                       |
+--------------+------------------------------+--------------------+---------------------------------+
| Kode NFR     | Komponen / Skenario          | Parameter Metrik   | Batas Ambang Toleransi (SLA)    |
+--------------+------------------------------+--------------------+---------------------------------+
| NFR-PERF-01  | Time to First Byte (TTFB)    | Response Time Web  | < 300 ms (Cached Routes/Views)  |
| NFR-PERF-02  | Full Page Load Katalog       | Client Page Load   | < 1.8 detik pada jaringan 4G    |
| NFR-PERF-03  | Live Search Response         | Client Search Delay| < 150 ms (Alpine.js Memory Filtr)|
| NFR-PERF-04  | Render DomPDF Quotation      | Server Generation  | < 2.0 detik per dokumen 10 item |
| NFR-PERF-05  | Impor Excel 1.000 SKU        | Batch Processing   | < 45 detik (Chunk 100 baris)    |
| NFR-PERF-06  | Core Web Vitals (SEO Google) | LCP, CLS, INP       | LCP < 2.0s, CLS = 0, INP < 200ms |
+--------------+------------------------------+--------------------+---------------------------------+
```

---

## 2. Kebutuhan Keamanan & Integritas Data (Security - `NFR-SEC`)

1. **NFR-SEC-01 (Passwordless Email OTP Authentication):** Autentikasi masuk panel internal ERP `app.domain.com` tidak menggunakan kata sandi statis, melainkan menggunakan sistem **Kode OTP 6 Digit yang dikirim ke Email Resmi Pengguna**:
   - Masa berlaku kode OTP: Maksimal 10 menit sejak dikirim.
   - Kode OTP di-hash saat disimpan sementara di basis data dan langsung dihanguskan (*single-use*) setelah berhasil login.
   - Pembatasan pengiriman ulang (*Resend Rate Limiting*): Minimal jeda 60 detik per permintaan OTP per alamat email.
   - Batas toleransi salah input OTP: Maksimal 3 kali percobaan sebelum kode dinyatakan kedaluwarsa.
2. **NFR-SEC-02 (CSRF Protection):** Seluruh request formulir HTTP non-GET wajib menyertakan token CSRF valid.
3. **NFR-SEC-03 (Subdomain SEO & Bot Isolation):** Respon subdomain ERP `app.domain.com` menyematkan HTTP Header:
   ```http
   X-Robots-Tag: noindex, nofollow, noarchive, nosnippet
   ```
4. **NFR-SEC-04 (MIME-Type Magic Bytes Validation):** Validasi unggahan berkas memeriksa *magic bytes* asli:
   - Gambar: `image/webp`, `image/jpeg`, `image/png` (Maks 5 MB).
   - Brosur & BoQ Lampiran RFQ: `application/pdf`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (.xlsx), `application/vnd.ms-excel` (.xls) (Maks 5–10 MB).
   - Impor Massal: `.xlsx`, `.csv` (Maks 15 MB).
5. **NFR-SEC-05 (Brute Force Rate Limiting):** Membatasi percobaan request login/OTP panel admin maksimal 5 kali per menit per IP.
6. **NFR-SEC-06 (Multi-Layer Public Form Anti-Spam Defense):** Seluruh formulir publik (Single RFQ, Bulk RFQ Cart, Formulir Kontak, dan Pendaftaran Mitra Freelance) dilindungi pertahanan anti-spam berlapis:
   - **Layer 1 (Invisible Honeypot):** Input field jebakan tak terlihat (`spatie/laravel-honeypot`) yang jika terisi oleh crawler bot, request langsung ditolak tanpa diproses ke database.
   - **Layer 2 (Time-Trap Submission):** Validasi durasi pengisian form; pengiriman form dalam durasi tidak wajar (< 2 detik) ditandai sebagai submission otomatis oleh bot.
   - **Layer 3 (Smart CAPTCHA Verification):** Verifikasi Cloudflare Turnstile / reCAPTCHA v3 tanpa mengganggu kenyamanan pengguna manusia (*frictionless*).
   - **Layer 4 (IP Throttle Limiter):** Pembatasan frekuensi pengiriman formulir publik maksimal 5 kali pengiriman per 1 menit per IP (`throttle:5,1`).
7. **NFR-SEC-07 (Immutable Financial Audit Trail):** Seluruh mutasi status komisi, persetujuan diskon khusus, dan revisi harga penawaran disimpan dalam tabel `activity_log` yang berstatus *Append-Only* (tidak dapat diedit maupun dihapus oleh pengguna manapun selain sistem log rotasi database).




---

## 3. Kebutuhan Keandalan & Efisiensi Server (Reliability - `NFR-REL`)

1. **NFR-REL-01 (Non-Blocking Queue Execution):** Queue worker dijalankan dengan batas waktu 50 detik (`--stop-when-empty --max-time=50`) agar proses keluar secara bersih sebelum cron job menit berikutnya memanggil kembali.
2. **NFR-REL-02 (Zero Session Database Footprint):** Keranjang penawaran tersimpan di *localStorage* browser pengunjung, menjamin 0 konsumsi I/O database saat pengunjung hanya menjelajah katalog.
3. **NFR-REL-03 (WebP Image Automation):** Konversi otomatis ke format WebP menghemat kuota *inodes* dan disk storage hingga 70% dibanding format PNG/JPEG asli.

---

## 4. Karakteristik Kualitas Perangkat Lunak (Software Quality Attributes)

- **Maintainability (Kemudahan Pemeliharaan):** Mengikuti standar konvensi PSR-12, arsitektur Service Layer terpisah (`DocumentNumberService`, `ImageOptimizationService`, `SeoFallbackService`).
- **Testability (Kemudahan Pengujian):** Seluruh rute komersial dan kalkulasi harga dapat diuji otomatis melalui PHPUnit / Pest Feature Tests.
- **Portability (Kemudahan Migrasi):** Seluruh konfigurasi sensitif diekstrak ke dalam file `.env`, memudahkan migrasi antar server tanpa mengubah basis kode sumber.
