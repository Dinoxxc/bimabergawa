# 02. Isolasi Subdomain ERP, Anti-Scraping, & Kebijakan Rate Limiting
## Security Policy & Regulatory Compliance Suite (Security-Compliance)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SEC-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Web Security Lead | Draf Awal | Aturan `noindex` subdomain ERP & proteksi CSRF |
| `1.1.0` | 03 Sep 2026 | Network Engineer | Ditinjau | Penambahan Cloudflare Turnstile & matriks rate limit |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan mitigasi bot scraping katalog & IP throttling |
| `1.3.0` | 05 Sep 2026 | Principal Security Architect | Disetujui | Pemisahan berkas mandiri isolasi subdomain & zero emoji |

---

## 1. Isolasi Subdomain Panel ERP (`app.bimabergawa.com`)

Untuk menjamin kerahasiaan proses komersial internal (nama klien, nilai penawaran, HPP, margin keuntungan) agar tidak pernah bocor atau terindeks oleh mesin pencari publik (Google/Bing):

### 1.1 Penegakan Header HTTP Anti-Indexing
Middleware Laravel menyematkan header berikut pada **setiap respon** dari subdomain `app.bimabergawa.com`:
```http
X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex
```

### 1.2 Konfigurasi Virtual `robots.txt` pada Subdomain ERP
```txt
# Disallow all indexing on ERP subdomain
User-agent: *
Disallow: /
```

---

## 2. Matriks Pembatasan Laju Permintaan (API Rate Limiting Matrix)

Mencegah serangan *Denial of Service (DoS)*, *credential stuffing*, dan *web scraping* otomatis:

| Endpoint Target | Batas Akses (*Rate Limit*) | Tindakan Saat Pelanggaran | Middleware Laravel |
| :--- | :--- | :--- | :--- |
| `POST /rfq/submit` | 10 request / menit per IP | Respon `429 Too Many Requests` | `throttle:10,1` |
| `POST /api/bulk-sku/parse`| 20 request / menit per IP | Respon `429 Too Many Requests` | `throttle:20,1` |
| `POST /contact/send` | 5 request / menit per IP | Respon `429 Too Many Requests` | `throttle:5,1` |
| `POST /gabung-mitra/apply`| 5 request / menit per IP | Respon `429 Too Many Requests` | `throttle:5,1` |
| `POST /login/send-otp` | 3 request / 10 menit per IP | Kunci 10 menit + Pesan Error | `throttle:3,10` |
| `GET /catalog` (Search) | 60 request / menit per IP | Respon `429 Too Many Requests` | `throttle:60,1` |

---

## 3. Proteksi Bot & Spam Formulir Publik (Cloudflare Turnstile)

Seluruh formulir publik dilindungi oleh widget verifikasi tanpa captcha interaktif (*Cloudflare Turnstile*):
1. Pengunjung web menyelesaikan validasi tak kasat mata (*invisible token*).
2. Backend memverifikasi token ke API Cloudflare sebelum memproses data formulir:
   ```php
   $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
       'secret'   => config('services.turnstile.secret_key'),
       'response' => $request->input('cf-turnstile-response'),
       'remoteip' => $request->ip(),
   ]);

   if (!$response->json('success')) {
       throw ValidationException::withMessages(['turnstile' => 'Verifikasi keamanan bot gagal.']);
   }
   ```
