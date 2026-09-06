# 04. Arsitektur Filament Resource & Antarmuka ERP (Filament Resources & UI)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-04` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Fullstack Engineer | Draf Awal | Definisi Filament Resources untuk Product, Inquiry, Quotation, & SO |
| `1.1.0` | 03 Sep 2026 | Senior Filament Developer | Ditinjau | Implementasi Custom Kanban Board Widget & visual pulsing badge |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Desain repeater form multi-termin milestone & modal klaim lead cepat |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.4.0` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan halaman `ManageSystemSettings` (Pengaturan PPN dinamis, margin, & hotline) |

---

## 1. Arsitektur Komponen Filament Admin (TALL Stack)

Panel internal ERP dibangun di atas **Filament Admin v3/v5** yang memanfaatkan reaktivitas **Livewire 3**, **Alpine.js**, dan komponen **Tailwind CSS**.

```
+─────────────────────────────────────────────────────────────────────────────+
|                         STRUKTUR RESOURCE FILAMENT ERP                      |
+─────────────────────────────────────────────────────────────────────────────+
 app/Filament/Resources/
 ├── ProductResource.php                 # Form Multi-Tab, Spek Repeater, HPP
 ├── InquiryResource.php                 # Kanban Pipeline, Telemetry & Stale Alert
 │   └── Pages/
 │       └── KanbanInquiries.php         # Board Drag-and-Drop 6 Kolom
 ├── QuotationResource.php               # Live Margin Calculator, Payment Terms
 │   └── Actions/
 │       ├── ConvertFromInquiryAction.php
 │       ├── ApproveDirectorAction.php
 │       └── SendWhatsAppAction.php
 ├── SalesOrderResource.php              # Locking PO & Status Pengiriman
 ├── DeliveryOrderResource.php           # Partial Shipment & Resi Ekspedisi
 ├── PartnerApplicationResource.php      # Screening Mitra & Approval Generator
 ├── CommissionTierResource.php          # Konfigurasi Mandiri Single/Multi-Tier
 ├── CommissionPayoutResource.php        # Tagihan Komisi & Uploader Slip Bank
 └── Pages/
     └── ManageSystemSettings.php        # Pengaturan Pajak PPN Dinamis, Margin & Kontak
```

---

## 2. Spesifikasi Form & Action Resource Utama

### 2.1 `InquiryResource` & Kanban Board CRM (`KanbanInquiries.php`)
- **Visual Kanban Board:** Menampilkan 6 kolom status: `new`, `contacted`, `quote_draft`, `quote_sent`, `deal`, `lost`.
- **Stale Lead Timer Badge:** Jika inquiry berstatus `new` berusia $> 2\text{ jam}$ kerja:
  ```blade
  @if($record->status === 'new' && $record->created_at->diffInHours(now()) >= 2)
      <div class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full flex items-center gap-1">
          <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-700" />
          <span>STALE: {{ $record->created_at->diffForHumans() }}</span>
      </div>
  @endif
  ```
- **Aksi Cepat:**
  - `Action::make('hubungi_wa')` -> Membuka `https://wa.me/{phone_wa}` dan otomatis mencatat timestamp `first_contacted_at`.
  - `Action::make('buat_penawaran')` -> Membuka halaman buat `Quotation` dengan seluruh item ter-prefill.

---

### 2.2 `QuotationResource` (Live Margin, Skema Pembayaran & Watermark)

#### Form Schema:
1. **Section Informasi Prospek & Klien:** Data PIC, Nama Perusahaan, Masa Berlaku (Default 14 hari), dan Syarat Penyerahan (*Delivery Terms*).
2. **Repeater Daftar Barang (`items`):**
   - Pilih Produk (Autocomplete), Kuantiti, Lead Time (*Ready Stock / Indent*), Harga Satuan (*Unit Price*), dan Diskon Satuan (%).
   - Kolom `cost_price` (HPP Modal Satuan) otomatis ditarik dari relasi produk (hanya terlihat oleh Super Admin dan Sales Internal).
3. **Widget Live Margin & Diskon:**
   - Menghitung secara reaktif (*Livewire reactive component*): Subtotal, Diskon Global, DPP, PPN 11%, Grand Total, dan **Gross Profit Margin %**.
   - Jika Margin $< 15\%$ atau Diskon $> 10\%$, form menampilkan banner peringatan oranye: *"Penawaran ini mewajibkan otorisasi Direksi sebelum PDF resmi dapat diterbitkan."*
4. **Repeater Skema Pembayaran (`paymentTerms`):**
   - Tombol Cepat: Preset *"100% CBD"*, *"100% COD"*, *"100% TOP 30 Hari"*, *"DP 50% + Pelunasan 50%"*, atau *"DP 30% + 50% Kirim + 20% Retensi"*.
   - Validasi live: Total persentase harus tepat $= 100.00\%$.
5. **Aksi Otorisasi & Cetak:**
   - `ApproveAction` (Khusus role `super_admin` / Direksi) -> Memberi centang `is_approved_by_director = true` dan mencatat log audit.
   - `DownloadPdfAction` -> Mengunduh PDF resmi (atau draf ber-watermark jika belum di-approve).
   - `SendWhatsAppQuoteAction` -> Membuka WhatsApp Web dengan link PDF penawaran resmi.

---

### 2.3 `DeliveryOrderResource` (Partial DO Khusus Ekspedisi)

#### Form Schema:
1. **Pilih Sales Order:** Relasi ke `sales_orders` yang disetujui.
2. **Repeater Barang Dikirim (`items`):** Memilih item yang dikirim pada batch ini beserta kuantiti (`quantity_shipped`). Sistem menampilkan kuantiti yang telah terkirim sebelumnya dan sisa yang belum terkirim.
3. **Data Ekspedisi Logistik Transparan:**
   - `courier_name` (Input text: misal *Dakota Cargo, Baraka, JNE Trucking*)
   - `tracking_number` (Input text: Nomor Resi / Waybill / AWB)
   - `tracking_url` (Input URL: Tautan langsung portal cek resi ekspedisi)
   - `total_packages` (Input number: Jumlah koli/dus)
   - `shipping_address` & `recipient_pic_name` (Alamat gudang pabrik & kontak PIC penerima)
4. **Aksi Cetak Surat Jalan:**
   - Menghasilkan PDF Surat Jalan resmi via DomPDF **100% TANPA memuat kolom harga rupiah** dan tanpa field driver/armada.

---

### 2.4 `CommissionTierResource` & `CommissionPayoutResource`

#### `CommissionTierResource` (Super Admin Only):
- CRUD tabel tingkatan komisi: Nama Tier (Level 1, Level 2, Level $N$), Minimal Akumulasi Omzet (Rp), dan Persentase Komisi (%).

#### `CommissionPayoutResource`:
- Menampilkan daftar komisi dengan status: `pending_payment` -> `payable` -> `paid`.
- Saat Super Admin mengubah status menjadi `paid`, form **mewajibkan unggah berkas slip bukti transfer bank (`transfer_proof_path`)**.
- Berkas slip transfer otomatis muncul pada dashboard portal mitra freelance terkait.

---

### 2.5 `ProductResource` (Master Katalog & HPP Modal)

#### Form Tabs:
- **Tab 1: Informasi Utama:** Nama Produk, Slug, Brand, Kategori & Sub-Kategori, Nomor Part/Model, Status Ketersediaan (*Ready Stock / Indent / Discontinued*), dan Produk Suksesor (`successor_product_id`).
- **Tab 2: Finansial & HPP (Tersembunyi dari Freelance):** Kolom input `cost_price` (Modal dasar prinsipal).
- **Tab 3: Spesifikasi Teknis:** Repeater Key-Value parameter dinamis (*spec_key* & *spec_value*).
- **Tab 4: Media & Berkas:** Upload foto galeri (otomatis diproses ke format WebP) dan uploader brosur datasheet PDF.
- **Tab 5: SEO Meta:** Kolom meta title, meta description (dengan indikator 160 karakter), dan canonical URL.

---

### 2.6 `ManageSystemSettings` (Pengaturan Pajak, Komersial & Operasional)

Halaman khusus otorisasi `super-admin` dan Direksi Finance untuk mengelola parameter operasional dan perpajakan secara dinamis melalui antarmuka web ERP tanpa memerlukan akses teknis ke server:

#### Schema Formulir Pengaturan:
1. **Grup Perpajakan & Keuangan (*Tax & Finance Settings*):**
   - `tax_vat_rate`: Input numerik persentase tarif PPN aktif saat ini (contoh: `11.00`, `12.00`, `13.00`). Menjadi acuan seketika (*real-time source of truth*) bagi pembuatan transaksi baru.
   - Perubahan nilai PPN otomatis dicatat dalam `activity_log` forensik (User pelaksana, waktu, nilai lama, nilai baru).
2. **Grup Batas Margin & Otorisasi (*Commercial Margin Guard*):**
   - `critical_margin_threshold`: Ambang batas margin kritis (Default: `8.00%`).
   - `warning_margin_threshold`: Ambang batas margin tipis (Default: `15.00%`).
   - `director_approval_limit`: Batas nilai nominal transaksi Sales Order yang mewajibkan otorisasi Direktur (Default: `Rp 100.000.000`).
3. **Grup Kontak & Hotline Publik (*Contact & Company Info*):**
   - `whatsapp_hotline`: Nomor WhatsApp resmi yang menerima telemetri RFQ dan klik katalog publik.
   - `company_address`: Alamat kantor resmi (Gd. Wirausaha Rasuna Said) untuk kop surat dan footer web publik.
   - `company_email`: Email kontak penawaran resmi.
4. **Mekanisme Penyimpanan & Cache:**
   - Formulir menyimpan data langsung ke tabel `system_settings` via `SystemSettingService`.
   - Melakukan *invalidation* otomatis pada tag cache Redis / Database Cache sehingga seluruh node web server LiteSpeed langsung membaca nilai terbaru secara instan.
