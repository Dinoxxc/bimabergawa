# Test Cases: Isolasi Subdomain, Perlindungan Bot, & Anti-Scraping
## Domain 05: Keamanan & Pengujian Non-Fungsional (Security & NFR)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-SEC-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Security/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Security QA Specialist | Draf Awal | Pengujian pembatasan bot & isolasi subdomain dasar |
| `1.1.0` | 03 Sep 2026 | Application Security Lead | Ditinjau | Penambahan uji header `X-Robots-Tag: noindex` & mitigasi bot scraping |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan rate limiter per endpoint dan pemblokiran IP abnormal |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SAD-01` (Topologi Multi-Domain `bimabergawa.com` vs `app.bimabergawa.com`)
  - `DOC-PBB-API-03` (Rute Subdomain ERP Terisolasi & Spatie RBAC)
  - `DOC-PBB-SEC-02` (Kebijakan Isolasi Subdomain, Anti-Scraping, & Header noindex)
  - `DOC-PBB-SRS-05` (Kebutuhan Non-Fungsional NFR-SEC-01 s.d. NFR-SEC-05)
- **Modul Sistem:** Modul Keamanan Jaringan, Isolasi Subdomain, & Anti-Scraping
- **Target Kelas / Middleware / Konfigurasi:**
  - `App\Http\Middleware\SubdomainAccessControl`
  - `App\Http\Middleware\EnforceSecurityHeaders`
  - Konfigurasi Web Server `.htaccess` & `robots.txt`
- **Target Method:** `handle()`, `blockRobotsOnErp()`, `rateLimitPublicRequests()`, `verifyHost()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-SEC-SUB-001`** | Positif | Penegakan Header `X-Robots-Tag: noindex, nofollow` pada Subdomain ERP | Akses subdomain `app.bimabergawa.com` | Request header inspection via curl: `curl -I https://app.bimabergawa.com/login` | Respons memuat header wajib: `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`. Mesin pencari dilarang mengindeks ERP. | Not Executed |
| **`TC-SEC-SUB-002`** | Positif | Konfigurasi Berkas `robots.txt` Subdomain Internal ERP | Akses endpoint `robots.txt` di ERP | Akses URL: `GET https://app.bimabergawa.com/robots.txt` | File memuat instruksi penolakan perayapan total: `User-agent: *\nDisallow: /`. | Not Executed |
| **`TC-SEC-SUB-003`** | Positif | Pengindeksan Sah (*SEO Indexing Allowed*) pada Web Publik | Akses domain utama `bimabergawa.com` | Request header inspection pada `GET https://bimabergawa.com/katalog` | Tidak memuat header `noindex`, sitemap XML tersedia di `/sitemap.xml`, robot Google diizinkan mengindeks katalog produk. | Not Executed |
| **`TC-SEC-SUB-004`** | Positif | Isolasi Sesi Lintas Subdomain (*Cookie Domain Scoping*) | Pengguna login di ERP | Cek cakupan cookie sesi `pbb_erp_session` | Cookie hanya berlaku untuk domain `app.bimabergawa.com`, tidak bocor atau dikirimkan saat membuka web publik `bimabergawa.com`. | Not Executed |
| **`TC-SEC-SUB-005`** | Positif | Penegakan SSL/TLS HTTPS Redirection pada Seluruh Rute | Permintaan masuk via HTTP port 80 | Akses: `http://app.bimabergawa.com/login` | Server web LiteSpeed langsung mengembalikan HTTP 301 Redirect ke `https://app.bimabergawa.com/login`. | Not Executed |
| **`TC-SEC-SUB-006`** | Negatif | Penolakan Host Header Tidak Sah (*Host Header Poisoning*) | Penyerang memanipulasi HTTP Host Header | Request: `Host: evil-phishing.com` ke IP server | Middleware `SubdomainAccessControl` memverifikasi whitelist host, mengembalikan HTTP 400 Bad Request / 403 Forbidden. | Not Executed |
| **`TC-SEC-SUB-007`** | Negatif | Penolakan Akses Rute ERP Melalui Domain Publik Utama | Pengguna mencoba membuka panel admin dari domain publik | Akses: `https://bimabergawa.com/admin/login` | Middleware menolak rute admin pada apex domain, mengembalikan HTTP 404 Not Found (rute admin hanya terdaftar pada domain `app`). | Not Executed |
| **`TC-SEC-SUB-008`** | Keamanan | Deteksi & Pemblokiran Scraping Massal Katalog (*Anti-Scraping*) | Bot melakukan crawling 100 halaman katalog per detik | 100 request dalam rentang 10 detik dari 1 IP | Rate Limiter memicu throttle, server merespons HTTP 429 Too Many Requests, IP dimasukkan ke daftar temporary block (15 menit). | Not Executed |
| **`TC-SEC-SUB-009`** | Keamanan | Pemblokiran User-Agent Pemindai Rentan (*Scanner Blocking*) | Request dikirim dengan User-Agent pemindai berbahaya | Header: `User-Agent: sqlmap/1.6` atau `Nikto/2.1` | `.htaccess` atau middleware keamanan memblokir koneksi seketika dengan HTTP 403 Forbidden. | Not Executed |
| **`TC-SEC-SUB-010`** | Keamanan | Pencegahan Kebocoran Struktur Folder Server (*Directory Browsing Disabled*) | Direktori aset publik | Akses langsung URL folder: `https://bimabergawa.com/storage/` atau `/build/` | Server mengembalikan HTTP 403 Forbidden karena opsi Apache `Options -Indexes` aktif, daftar isi folder tidak ditampilkan. | Not Executed |
| **`TC-SEC-SUB-011`** | Keamanan | Pembatasan Penggunaan HTTP Methods yang Diizinkan | Endpoint web publik | Kirim request menggunakan method `TRACE`, `TRACK`, `DELETE` pada halaman publik | Server menolak method yang tidak terdefinisi dengan status HTTP 405 Method Not Allowed. | Not Executed |
| **`TC-SEC-SUB-012`** | Keamanan | Penegakan Kebijakan Cross-Origin Resource Sharing (CORS) | Situs pihak ketiga mengirim request fetch ke API internal | Request origin: `https://untrusted-site.com` | Browser memblokir respon karena header `Access-Control-Allow-Origin` hanya mengizinkan domain internal yang terdaftar. | Not Executed |
| **`TC-SEC-SUB-013`** | Batas Nilai | Penanganan Beban Request Spike Trafik Organik pada Web Publik | Lonjakan pengunjung 100 req/menit | Akses serentak pada halaman katalog | LiteSpeed Cache merespons dari cache statis (waktu respon < 80ms), beban CPU server tetap stabil di bawah 20%. | Not Executed |
| **`TC-SEC-SUB-014`** | Audit | Pencatatan Percobaan Serangan Otentikasi Abnormal | Percobaan login mencurigakan dari IP asing | 10 request login gagal dalam 1 menit | Sistem mencatat anomali keamanan pada tabel `security_audit_logs` memuat IP, User-Agent, dan waktu insiden. | Not Executed |
| **`TC-SEC-SUB-015`** | Keamanan | Pencegahan Bypassing Rate Limiting Menggunakan X-Forwarded-For Palsu | Penyerang memalsukan header IP | Request memuat header: `X-Forwarded-For: 1.2.3.4` acak | Sistem hanya mempercayai IP dari Cloudflare (*Trusted Proxies Configuration*), IP asli penyerang tetap terdeteksi untuk throttling. | Not Executed |
| **`TC-SEC-SUB-016`** | Privasi | Penonaktifan Fitur Pre-fetching pada Panel Internal ERP | Halaman admin Filament dimuat | Inspeksi tag HTML pada panel ERP | Tidak memuat `<link rel="prerender">` atau DNS prefetching ke layanan analitik pihak ketiga yang berpotensi membocorkan data. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-SEC-SUB-001 (Verifikasi Header noindex Subdomain ERP)

1. **Langkah 1:** Jalankan perintah curl untuk menginspeksi respon header:
   ```bash
   curl -s -I "https://app.bimabergawa.com/login"
   ```
2. **Verifikasi Teknis:**
   - Baris respon wajib memuat:
     ```http
     X-Robots-Tag: noindex, nofollow, noarchive, nosnippet
     ```
   - Memastikan tidak ada metadata HTML `<meta name="robots" content="index, follow">` yang ter-render di dalam view login ERP.

### 3.2 Prosedur Uji TC-SEC-SUB-008 (Uji Anti-Scraping Rate Limiter)

1. **Langkah 1:** Jalankan script ab (Apache Bench) atau curl loop:
   ```bash
   for i in {1..80}; do curl -s -o /dev/null -w "%{http_code}\n" "https://bimabergawa.com/katalog?page=$i"; done
   ```
2. **Verifikasi Teknis:**
   - 60 request pertama mengembalikan status code `200 OK`.
   - Request ke-61 dan seterusnya mulai mengembalikan `429 Too Many Requests`.
   - Server tidak mengalami crash / memory exhaustion.
