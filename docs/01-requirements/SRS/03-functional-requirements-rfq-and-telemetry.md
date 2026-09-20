# 03. Kebutuhan Fungsional: Dual RFQ Engine & Deep Telemetry
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-03` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Functional Systems Analyst | Draf Awal | Spesifikasi teknis Fast-Track WhatsApp & Bulk RFQ Drawer |
| `1.1.0` | 03 Sep 2026 | Telemetry Engineer | Ditinjau | Penambahan spesifikasi telemetri UTM, referrer, & click-stream |
| `1.2.0` | 05 Sep 2026 | Engineering Lead | Disetujui | Finalisasi parser bulk SKU multi-baris & sanitasi input pengajuan penawaran |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.4.0` | 20 Sep 2026 | Security & System Architect | Disetujui | Penambahan spesifikasi 4 lapis keamanan pelacak pengadaan publik (/lacak-rfq): Two-Key Verification (No. Dokumen + WA/Email), Signed URL email, private PDF download guard, dan rate limiting |

---

## 1. Modul Penangkapan Prospek Ganda (`FR-RFQ`)

```
+----------------------------------------------------------------------------------------------------+
|                                  SPESIFIKASI TEKNIS FR-RFQ                                         |
+--------------+--------------------------+----------------------------------------------------------+
| ID Kebutuhan | Nama Fungsi              | Logika Pemrosesan Teknis & Output                        |
+--------------+--------------------------+----------------------------------------------------------+
| FR-RFQ-01    | Fast-Track WhatsApp CTA  | Men-generate URL wa.me dengan pesan teks prefilled       |
|              |                          | urlencoded: Nama Produk, Part Number, dan URL halaman.   |
| FR-RFQ-02    | Single-Item Modal Pop-up | Form modal kilat 1 produk, kirim POST /rfq/single, simpan|
|              |                          | ke `inquiries` (type: 'single_direct') & `inquiry_items`.|
| FR-RFQ-03    | LocalStorage RFQ Storage | Mengelola item terpilih di browser via key               |
|              |                          | 'bima_rfq_cart' (JSON array objek produk).               |
| FR-RFQ-04    | Floating RFQ Tray/Drawer | Komponen melayang reaktif via Alpine store               |
|              |                          | `$store.rfqCart` menampilkan counter & quick list.       |
| FR-RFQ-05    | Bulk RFQ Guest Submit    | Form review /rfq kirim payload POST /rfq/submit,         |
|              |                          | transaksi database multi-baris di `inquiry_items`.       |
| FR-RFQ-06    | Concurrency-Safe Number  | Menghasilkan nomor unik 'PBB/INQ/YYYY/MM/XXXX' dengan locking |
|              |                          | database transaction (`lockForUpdate()`).                |
| FR-RFQ-07    | Form Anti-Spam Pipeline  | Memvalidasi Honeypot, Time-trap (>2s), dan Cloudflare     |
|              |                          | Turnstile token sebelum memproses insert database.        |
| FR-RFQ-08    | Two-Key & Signed Tracker | Rute /lacak-rfq verifikasi 2 kunci (Nomor Dokumen + WA/   |
|              |                          | Email PIC) atau bypass instan via Cryptographic Temporary |
|              |                          | Signed URL email, render status tahapan, tautan unduh aman|
|              |                          | PDF penawaran privat, serta info resi resmi ekspedisi.    |
+--------------+--------------------------+----------------------------------------------------------+

```

### 1.1 Struktur Validasi Request Submit RFQ (`POST /rfq/submit`)
```php
$request->validate([
    'customer_name'    => 'required|string|max:150',
    'company_name'     => 'nullable|string|max:150',
    'phone_wa'         => ['required', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'],
    'email'            => 'nullable|email|max:150',
    'project_location' => 'nullable|string|max:200',
    'notes'            => 'nullable|string',
    'items'            => 'required|array|min:1',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity'   => 'required|integer|min:1',
    'items.*.unit_note'  => 'nullable|string|max:100',
    'items.*.special_requirements' => 'nullable|string',
    'attachment_file'              => 'nullable|file|mimes:pdf,xlsx,xls,jpg,png|max:5120', // Berkas BoQ maks 5MB
    'cf-turnstile-response'        => 'required|string', // Cloudflare Turnstile token
]);

// Anti-Spam Layer 1 & 2: Honeypot & Submission Timer
// Menggunakan Spatie Honeypot: request diabaikan jika field jebakan terisi atau durasi < 2 detik
```

### 1.2 Spesifikasi Teknis Pelacak Pengadaan & Pengamanan Dokumen Publik (FR-RFQ-08)

Mencegah serangan *Insecure Direct Object Reference* (IDOR) dan spionase komersial akibat nomor dokumen berurutan (*sequential*), sistem menerapkan arsitektur pertahanan 4 lapis:

1. **Verifikasi Dua Kunci Form Publik (`Two-Key Verification`):**
   - Form pencarian publik `POST /lacak-rfq/search` mewajibkan 2 parameter valid:
     ```php
     $request->validate([
         'doc_number'           => ['required', 'string', 'max:50'],
         'contact_verification' => ['required', 'string', 'max:150'], // No. WA atau Email resmi PIC
     ]);
     ```
   - Kueri pencocokan database wajib mengevaluasi kedua kunci:
     ```php
     $inquiry = Inquiry::where('inquiry_number', $request->doc_number)
         ->where(function ($query) use ($request) {
             $query->where('phone_wa', $request->contact_verification)
                   ->orWhere('email', $request->contact_verification);
         })->firstOrFail();
     ```
   - Jika kombinasi nomor dokumen dan kontak tidak cocok, sistem mengembalikan respon HTTP 404/422 dengan pesan seragam (*generic rejection*) untuk mencegah *enumeration attack*.

2. **Akses Instan Tautan Email (Cryptographic Temporary Signed URL):**
   - Tautan pelacakan yang dikirimkan via email konfirmasi RFQ menggunakan signature HMAC-SHA256:
     ```php
     $trackingUrl = URL::temporarySignedRoute(
         'tracking.index',
         now()->addDays(30),
         ['ref' => $inquiry->inquiry_number]
     );
     ```
   - Pengunjung yang mengakses tautan bertanda tangan valid (`$request->hasValidSignature()`) langsung dapat memantau status tanpa perlu menginput kontak verifikasi ulang.

3. **Isolasi Berkas PDF Dokumen (Private Storage + Signed Download Route):**
   - Berkas fisik PDF Quotation, Sales Order, dan Invoice tersimpan di disk privat (`storage/app/private/documents/`).
   - Rute unduh publik `GET /documents/download/{type}/{token}` divalidasi melalui controller perantara dengan masa kedaluwarsa token 15 menit. Dilarang keras menyediakan direct URL file terbuka di `public/storage/`.

4. **Proteksi Anti-Brute Force & Rate Limiting:**
   - Rute `POST /lacak-rfq/search` diproteksi middleware `throttle:5,1` (maksimal 5 kali percobaan per menit per alamat IP).
   - Apabila gagal 3 kali berturut-turut, sistem memunculkan tantangan Cloudflare Turnstile CAPTCHA.

---

---

## 2. Modul Telemetri Mendalam & Atribusi Pemasaran (`FR-TEL`)

### FR-TEL-01: Middleware Penangkapan Parameter Digital (CaptureUtmParameters)
- **Logika Eksekusi:** Pada setiap HTTP Request yang masuk ke website publik:
  ```php
  if ($request->has('utm_source')) {
      session([
          'utm_source'   => $request->query('utm_source'),
          'utm_medium'   => $request->query('utm_medium'),
          'utm_campaign' => $request->query('utm_campaign'),
          'utm_term'     => $request->query('utm_term'),
          'utm_content'  => $request->query('utm_content'),
          'landing_page' => $request->fullUrl(),
          'referrer_url' => $request->headers->get('referer'),
      ]);
  }
  ```

### FR-TEL-02: Pelacakan Kode Referal Freelance Sales (`?ref=...`)
- **Logika Eksekusi:**
  - Jika URL memuat parameter `?ref=KODE_AGEN` (contoh: `?ref=dimas`), middleware mencari agen di tabel `freelance_sales` di mana `referral_code = KODE_AGEN` dan `is_active = true`.
  - Jika ditemukan, sistem menyimpan `freelance_sales_id` ke dalam cookie browser terenkripsi selama **30 hari** (*30-day attribution window*).
  - Saat pengunjung men-submit form penawaran harga (Single maupun Bulk), `freelance_sales_id` otomatis diinjeksi ke baris record `inquiries`.

### FR-TEL-03: Integrasi Google Tag Manager dataLayer Events
- **Event yang Dipicu via JavaScript:**
  1. `dataLayer.push({'event': 'view_item', 'ecommerce': {'items': [...]}})`
  2. `dataLayer.push({'event': 'add_to_rfq_cart', 'ecommerce': {'items': [...]}})`
  3. `dataLayer.push({'event': 'submit_rfq_conversion', 'inquiry_number': 'PBB/INQ/2026/09/0001'})`
  4. `dataLayer.push({'event': 'click_whatsapp_sales', 'product_sku': 'FSHA-50'})`

### FR-TEL-04: Dynamic XML Sitemap Generator (`/sitemap.xml`)
- **Proses:** Rute `GET /sitemap.xml` merespons dengan header `Content-Type: application/xml`.
- **Koleksi URL:** Menghasilkan tag `<url>` untuk:
  - Beranda, Tentang Kami, Kontak, Halaman Induk Katalog.
  - Seluruh Brand aktif (`/brands/{slug}`).
  - Seluruh Produk berstatus `is_published = true` (`/catalog/{slug}`) dengan `lastmod = updated_at`.
  - Seluruh Artikel berstatus `is_published = true` (`/insights/{slug}`).
