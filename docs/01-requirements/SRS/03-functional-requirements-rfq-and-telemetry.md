# 03. Kebutuhan Fungsional: Dual RFQ Engine & Deep Telemetry
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Functional Systems Analyst | Draf Awal | Spesifikasi teknis Fast-Track WhatsApp & Bulk RFQ Drawer |
| `1.1.0` | 03 Sep 2026 | Telemetry Engineer | Ditinjau | Penambahan spesifikasi telemetri UTM, referrer, & click-stream |
| `1.2.0` | 05 Sep 2026 | Engineering Lead | Disetujui | Finalisasi parser bulk SKU multi-baris & sanitasi input pengajuan penawaran |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

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
| FR-RFQ-06    | Concurrency-Safe Number  | Menghasilkan nomor unik 'RFQ-YYYYMM-XXXX' dengan locking |
|              |                          | database transaction (`lockForUpdate()`).                |
| FR-RFQ-07    | Form Anti-Spam Pipeline  | Memvalidasi Honeypot, Time-trap (>2s), dan Cloudflare     |
|              |                          | Turnstile token sebelum memproses insert database.        |
| FR-RFQ-08    | Self-Service RFQ Tracker | Rute GET /lacak-rfq verifikasi nomor RFQ + WA pemohon     |
|              |                          | render status, unduh Quote PDF, serta info resi & tautan  |
|              |                          | langsung tracking resmi pihak ekspedisi.                  |
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
  3. `dataLayer.push({'event': 'submit_rfq_conversion', 'inquiry_number': 'RFQ-202609-0001'})`
  4. `dataLayer.push({'event': 'click_whatsapp_sales', 'product_sku': 'FSHA-50'})`

### FR-TEL-04: Dynamic XML Sitemap Generator (`/sitemap.xml`)
- **Proses:** Rute `GET /sitemap.xml` merespons dengan header `Content-Type: application/xml`.
- **Koleksi URL:** Menghasilkan tag `<url>` untuk:
  - Beranda, Tentang Kami, Kontak, Halaman Induk Katalog.
  - Seluruh Brand aktif (`/brands/{slug}`).
  - Seluruh Produk berstatus `is_published = true` (`/catalog/{slug}`) dengan `lastmod = updated_at`.
  - Seluruh Artikel berstatus `is_published = true` (`/insights/{slug}`).
