# Test Cases: Profil Perusahaan, Navigasi, & Showcase Brand
## Domain 01: Public Website (bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-PUB-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Engineer | Draf Awal | Pengujian navigasi beranda dan profil dasar |
| `1.1.0` | 03 Sep 2026 | UI/UX QA Tester | Ditinjau | Penambahan uji responsif mobile & verifikasi alamat kantor |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan header keamanan HTTP & validasi tautan brand |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi komprehensif skenario uji & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-02` (Spesifikasi Fitur Web Publik, Identitas Perusahaan, & Showcase)
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-COMPRO-01 s.d. FR-COMPRO-06)
  - `DOC-PBB-UIUX-02` (Design Tokens, Monochrome Palette, Typography & Wireframe)
- **Modul Sistem:** Modul Profil Perusahaan, Navigasi, & Showcase Brand Publik
- **Target Kelas / Controller:** `App\Http\Controllers\HomeController`, `App\Http\Controllers\ContactController`
- **Target Method:** `index()`, `about()`, `contact()`, `privacyPolicy()`, `termsOfService()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-PUB-NAV-001`** | Positif | Rendering Halaman Utama (Landing Page) & Visual Brand Identity | Web server aktif, DB terhubung | Akses `GET /` | HTTP 200 OK. Tampil nama resmi PT. Panca Bima Bergawa, palet monokrom minimalis (Slate/Zinc), 0 emoji, dan logo SVG tajam. | Not Executed |
| **`TC-PUB-NAV-002`** | Positif | Verifikasi Alamat Kantor Pusat Rasuna Said pada Footer | Halaman Beranda dimuat | Scroll ke elemen footer `<footer>` | Tampil teks alamat persis: "Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920" tanpa salah ketik. | Not Executed |
| **`TC-PUB-NAV-003`** | Positif | Verifikasi Halaman Kontak & Integrasi Peta Google Maps | Halaman kontak aktif | Akses `GET /kontak` | Tampil alamat kantor lengkap, nomor telepon hunting, email resmi, dan iframe embed Google Maps terpusat di Gd. Wirausaha Rasuna Said. | Not Executed |
| **`TC-PUB-NAV-004`** | Positif | Navigasi Menu Header Desktop (> 1024px) | Layar viewport lebar (1920x1080) | Klik tautan "Beranda", "Tentang Kami", "Katalog", "Prinsipal", "Kontak" | Seluruh tautan aktif mengarahkan ke rute masing-masing tanpa reload penuh jika menggunakan SPA mode / Turbo Drive. Indikator aktif (underline/bold) sesuai halaman aktif. | Not Executed |
| **`TC-PUB-NAV-005`** | Positif | Navigasi Menu Drawer Mobile (< 768px) | Layar viewport mobile (375x812 - iPhone X) | Klik tombol Hamburger Button | Mobile drawer terbuka halus dengan animasi slide-in, background overlay meredup, scroll body terkunci. Seluruh item menu dapat di-tap dengan mudah. | Not Executed |
| **`TC-PUB-NAV-006`** | Positif | Penutupan Drawer Navigasi Mobile via Overlay & Tombol Escape | Drawer mobile dalam posisi terbuka | Klik di luar drawer (overlay) atau tekan tombol keyboard `Esc` | Drawer menutup kembali dengan mulus, fokus keyboard kembali ke tombol hamburger, scroll body terbuka kembali. | Not Executed |
| **`TC-PUB-NAV-007`** | Positif | Brand Grid Showcase Interactivity | Master brand aktif di DB (min. 4 brand) | Klik logo brand "Schneider Electric" pada grid beranda | Mengarahkan ke rute `GET /katalog?brand=schneider-electric` dengan query filter terpasang dan menampilkan produk relevan. | Not Executed |
| **`TC-PUB-NAV-008`** | Positif | Halaman Profil Perusahaan (About Us) & Struktur Legalitas | Database terisi data profil | Akses `GET /tentang-kami` | Tampil visi misi, profil manajemen B2B, cakupan suplai elektrikal & mekanikal, serta penegasan legalitas PT. Panca Bima Bergawa. | Not Executed |
| **`TC-PUB-NAV-009`** | Positif | Halaman Kebijakan Privasi & Syarat Ketentuan B2B | Halaman statis tersedia | Akses `GET /kebijakan-privasi` & `GET /syarat-ketentuan` | HTTP 200 OK. Menampilkan kepatuhan UU PDP No. 27/2022, hak subjek data, dan ketentuan transaksi komersial B2B. | Not Executed |
| **`TC-PUB-NAV-010`** | Negatif | Penanganan Halaman Tidak Ditemukan (404 Error Page) | Server aktif | Akses URL acak: `GET /halaman-tidak-ada-404` | HTTP 404 Not Found. Merender halaman kustom bertema monokrom rapi, memuat tombol "Kembali ke Beranda", tanpa kebocoran debug trace Laravel. | Not Executed |
| **`TC-PUB-NAV-011`** | Negatif | Penanganan Gangguan Database / Maintenance Mode | DB MySQL sengaja dimatikan / Maintenance mode aktif | Akses `GET /` saat mode maintenance | HTTP 503 Service Unavailable. Merender halaman pemeliharaan formal bertuliskan kontak darurat operasional PT. Panca Bima Bergawa. | Not Executed |
| **`TC-PUB-NAV-012`** | Keamanan | Inspeksi HTTP Security Headers pada Semua Halaman Publik | Domain aktif via HTTPS | Request header inspection via curl/SSL Labs | Memuat header: `Strict-Transport-Security: max-age=31536000`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`. Header `X-Powered-By` tersembunyi. | Not Executed |
| **`TC-PUB-NAV-013`** | Keamanan | Pencegahan Clickjacking pada Seluruh Halaman Publik | Halaman web dimuat dalam `<iframe src="https://bimabergawa.com">` di domain luar | Akses iframe dari domain eksternal | Browser memblokir rendering iframe karena header `X-Frame-Options: SAMEORIGIN` atau `Content-Security-Policy: frame-ancestors 'self'`. | Not Executed |
| **`TC-PUB-NAV-014`** | Batas Nilai | Aksesibilitas Keyboard (Tab Navigation & Screen Reader) | Layar desktop tanpa menggunakan mouse | Navigasi seluruh halaman hanya menggunakan tombol `Tab`, `Shift+Tab`, dan `Enter` | Focus outline terlihat jelas pada seluruh elemen interaktif, tombol memiliki `aria-label`, urutan tab logis dari header ke footer. | Not Executed |
| **`TC-PUB-NAV-015`** | Batas Nilai | Responsivitas Antarmuka pada Viewport Ekstrem (320px s.d. 4K 3840px) | Browser developer tools responsive mode | Resize browser dari 320px (iPhone SE lama) hingga 3840px (Monitor 4K) | Tidak ada *horizontal scrolling* liar pada layar 320px, teks dan grid kartu brand tetap berada dalam kontainer proporsional pada layar 4K. | Not Executed |
| **`TC-PUB-NAV-016`** | SEO & Meta | Verifikasi Meta Tags, Open Graph, & JSON-LD Schema.org | Halaman dimuat | Inspeksi tag `<head>` pada `GET /` dan `GET /tentang-kami` | Memuat `<title>`, `<meta name="description">`, OpenGraph `og:title`, `og:image`, dan script `ld+json` tipe `Organization` memuat alamat resmi Rasuna Said. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-PUB-NAV-002 (Verifikasi Alamat Kantor Rasuna Said)

1. **Langkah 1:** Buka peramban dan navigasikan ke `https://bimabergawa.com/`.
2. **Langkah 2:** Lakukan scroll hingga ke bagian paling bawah (Footer).
3. **Verifikasi Teknis:**
   - Elemen alamat menggunakan tag `<address>` yang semantik.
   - String alamat memuat persis:
     ```
     PT. Panca Bima Bergawa
     Gd. Wirausaha lt.1 Unit 104
     Jl. H.R. Rasuna Said Kav. C5, Setiabudi
     Jakarta Selatan 12920
     ```
   - Tautan email mengarah ke `mailto:info@bimabergawa.com` (atau email resmi terdaftar).

### 3.2 Prosedur Uji TC-PUB-NAV-012 (Verifikasi HTTP Security Headers)

1. **Perintah Eksekusi:**
   ```bash
   curl -I -k https://bimabergawa.com/
   ```
2. **Verifikasi Respon:**
   - Memastikan respons status code `HTTP/2 200` atau `HTTP/1.1 200 OK`.
   - Header `X-Content-Type-Options` bernilai `nosniff`.
   - Header `X-Frame-Options` bernilai `SAMEORIGIN`.
   - Tidak ada header `X-Powered-By: PHP/8.3`.
