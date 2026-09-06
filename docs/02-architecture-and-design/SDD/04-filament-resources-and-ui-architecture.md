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
| `1.4.1` | 06 Sep 2026 | Principal System Architect | Disetujui | Spesifikasi Dark/Light Mode Filament Provider, Matriks RBAC Tab Pengaturan Sistem, & Standar Multimoda UI |
| `1.4.2` | 06 Sep 2026 | Principal System Architect | Disetujui | Spesifikasi arsitektur notifikasi multi-kanal (Database In-App Bell & Email Transaksional) |
| `1.4.3` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan komponen Livewire diskusi internal dokumen transaksi (Internal Comments Timeline) |

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

### 2.6 `ManageSystemSettings` (Pengaturan Terpusat Berbasis Tab & Matriks RBAC)

Halaman khusus di bawah rute `/admin/system-settings` untuk mengelola parameter operasional, komersial, perpajakan, dan identitas perusahaan secara dinamis melalui antarmuka web ERP tanpa memerlukan akses teknis ke server:

#### Schema Formulir Berbasis Tab & Hak Akses (Spatie RBAC):
1. **Tab 1: Umum & Identitas Perusahaan (`group: general`):**
   - `company_name`: Nama resmi entitas (`PT. Panca Bima Bergawa`).
   - `company_address`: Alamat kantor pusat Rasuna Said untuk kop surat cetak PDF.
   - `company_logo_path`, `company_favicon_path`: Logo resmi perusahaan.
   - `default_timezone`: `Asia/Jakarta`, `date_format`: `d/m/Y`.
   - *Otorisasi:* Super Admin `[Penuh]`, Staf Internal `[Lihat Saja]`, Mitra Agen `[Dilarang]`.
2. **Tab 2: Pajak & Komersial (`group: commercial_tax`):**
   - `tax_vat_rate`: Tarif PPN aktif saat ini (contoh: `11.00`, `12.00`). Menjadi sumber kebenaran seketika (*real-time snapshot*) bagi quotation baru.
   - `critical_margin_threshold`: Ambang batas margin kritis (Default: `8.00%`).
   - `warning_margin_threshold`: Ambang batas margin tipis (Default: `15.00%`).
   - `director_approval_limit`: Batas nominal transaksi Sales Order yang mewajibkan otorisasi Direktur (Default: `Rp 50.000.000`).
   - `company_bank_accounts`: JSON array nomor rekening korporat resmi untuk instruksi pembayaran.
   - *Otorisasi:* Super Admin & Finance `[Penuh]`, Sales/Logistik/Agen `[Dilarang - Rahasia HPP]`.
3. **Tab 3: Operasional Logistik (`group: logistics`):**
   - `default_warehouse_origin`: Gudang asal pengiriman standar.
   - `authorized_couriers`: Daftar ekspedisi resmi (Dakota Cargo, Baraka, Kurir Internal).
   - `delivery_order_notes_template`: Template catatan serah terima fisik barang.
   - *Otorisasi:* Super Admin & Logistik `[Penuh]`, Role lain `[Lihat Saja]`.
4. **Tab 4: CRM & Penjualan (`group: crm_sales`):**
   - `inquiry_stale_hours`: Ambang jam batas lead sebelum memicu peringatan Stale (Default: `24 jam`).
   - `whatsapp_quotation_template`: Format pesan penawaran via WhatsApp resmi.
   - *Otorisasi:* Super Admin & Sales PIC `[Penuh]`, Role lain `[Lihat Saja]`.
5. **Tab 5: Skema Komisi Kemitraan (`group: commission_tiers`):**
   - Konfigurasi ambang omset dan persentase komisi per tier agen, serta batas minimum pencairan komisi.
   - *Otorisasi:* Super Admin & Finance `[Penuh]`, Agen `[Dilarang - Diatur Pusat]`.
6. **Tab 6: Sistem & Maintenance (`group: system_maintenance`):**
   - `maintenance_mode_public`: Sakelar pemeliharaan web publik `bimabergawa.com`.
   - Kredensial SMTP OTP & API Key Gateway telemetri.
   - *Otorisasi:* Super Admin `[Penuh Eksklusif]`.

#### Mekanisme Penyimpanan & Cache Invalidation:
- Formulir menyimpan data langsung ke tabel `system_settings` via `SystemSettingService`.
- Melakukan *invalidation* otomatis pada tag cache Redis / Database Cache sehingga seluruh transaksi baru langsung mengacu pada konfigurasi terkini.

---

### 2.7 Profil Pengguna & Pengalih Tema Tampilan (`AdminPanelProvider.php` & `/admin/profile`)

1. **Dukungan Dark Mode & Light Mode:**
   - Diimplementasikan pada `AdminPanelProvider` menggunakan deklarasi `->darkMode(true)`.
   - Menggunakan sakelar tema (*Theme Switcher*) native Filament yang terintegrasi dengan kelas `dark` pada Tailwind CSS.
   - Disimpan pada `localStorage` peramban untuk responsivitas instan tanpa jeda kueri basis data.
2. **Halaman Profil Mandiri (`/admin/profile`):**
   - Dapat diakses oleh seluruh pengguna (Super Admin, Sales, Finance, Logistik, Mitra Agen).
   - Mengelola: Nama lengkap, nomor telepon/WhatsApp, unggah avatar profil, dan riwayat sesi perangkat aktif.
   - Perubahan alamat email wajib melalui verifikasi OTP 6-digit (masa aktif 300 detik).

---

### 2.8 Standar Multimoda Tampilan UI & Pagination Mandiri

1. **Pagination Mandiri Per Tabel:**
   - Setiap komponen tabel Filament memiliki kontrol pagination independen di bagian footer tabel (`[10 | 25 | 50 | 100]` baris per halaman).
   - Menggunakan query database berindeks `LIMIT` dan `OFFSET` untuk menjaga efisiensi batas memori 512 MB shared hosting.
2. **Tiga Moda Tampilan Antarmuka:**
   - **Table View:** Format tabel data standar untuk modul dokumen transaksional (*Quotation*, *Sales Order*, *Surat Jalan*, *Invoice*, *Commission Ledger*).
   - **Grid / Card View:** Format kartu visual untuk Master Katalog Produk (`ProductResource`), menampilkan gambar WebP, brand badge, SKU, dan stok.
   - **Kanban Board View:** Format papan kolom drag-and-drop untuk CRM Inquiries (`InquiryResource`), dilengkapi indikator visual waktu tunda (*Stale Alert*).

---

### 2.9 Arsitektur Notifikasi Multi-Kanal (Database In-App Bell & Email Transaksional)

Sistem mengadopsi arsitektur *Multi-Channel Event-Driven Notification* berbasis Laravel Notifications yang terintegrasi langsung dengan Filament Database Notifications:

```
[ EVENT BISNIS ERP ]
         │
         ▼
[ NOTIFICATION CLASS: via($notifiable) ]
         │
         ├──────────────────────────────────────────┬──────────────────────────────────────────┐
         ▼                                          ▼                                          ▼
[ CHANNEL: DATABASE ]                     [ CHANNEL: MAIL (QUEUED) ]                [ AUDIT LOG / ACTIVITY ]
         │                                          │                                          │
         ▼                                          ▼                                          ▼
[ TABEL: notifications ]                  [ QUEUE: php artisan queue:work ]         [ FORENSIK AUDIT KUP ]
         │                                          │
         ▼                                          ▼
[ FILAMENT TOPBAR BELL DRAWER ]           [ SMTP KORPORAT: sales@bimabergawa.com ]
• Unread Badge Count: [2]                 • Format Formal Kop Surat Rasuna Said
• Direct Action Link                      • Ringkasan Nilai & SKU Transaksi
• Polling 30 Detik Interaktif             • Tombol Call-To-Action Aman OTP
```

#### 1. Konfigurasi Panel Provider Filament (`AdminPanelProvider.php`):
```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->databaseNotifications()
        ->databaseNotificationsPolling('30s');
}
```

#### 2. Enam Kelas Notifikasi Inti (`app/Notifications/`):
1. `InquiryReceivedNotification`: Dipicu saat ada prospek baru dari formulir RFQ web publik; dikirim ke Sales PIC & Super Admin (Database + Mail).
2. `InquiryStaleAlertNotification`: Dipicu oleh Cron Scheduled Task saat prospek status `new` melampaui 24 jam belum difollow-up; dikirim ke Sales PIC & Manajer Sales (Database + Mail).
3. `DirectorApprovalRequiredNotification`: Dipicu saat penawaran bernilai margin kritis (< 8%) atau SO di atas batas nominal; dikirim eksklusif ke Direktur (Database + Mail).
4. `SalesOrderConfirmedNotification`: Dipicu saat SO telah divalidasi pembayarannya; dikirim ke Tim Logistik untuk menyiapkan Surat Jalan fisik (Database + Mail).
5. `DeliveryOrderDispatchedNotification`: Dipicu saat armada logistik berangkat dan nomor resi kurir diinput; dikirim ke Sales PIC & Klien (Database + Mail).
6. `CommissionPayoutNotification`: Dipicu saat slip transfer bank komisi berhasil diunggah staf Finance; dikirim ke Mitra Agen Freelance terkait (Database + Mail).

---

### 2.10 Komponen Diskusi & Catatan Internal Dokumen (Internal Comments Timeline Component)

Untuk mendukung koordinasi tanpa friksi antar staf tanpa membebani server dengan koneksi WebSocket, sistem menerapkan komponen diskusi internal berbasis Livewire 3 (`App\Livewire\InternalCommentsTimeline`) yang disematkan pada setiap halaman tampilan transaksi:

```
+─────────────────────────────────────────────────────────────────────────────+
|           KOMPONEN DISKUSI & TIMELINE INTERNAL (Livewire 3)                |
+─────────────────────────────────────────────────────────────────────────────+
 [Model: Quotation / SO / Inquiry / DO] <--- Polymorphic (commentable)
                 │
                 ▼
 [Livewire Component: InternalCommentsTimeline]
 ├── Daftar Percakapan Berurut Waktu (Chronological Activity Feed)
 │   ├── Avatar Pengguna & Text Badge Role ([Sales PIC] / [Direksi] / [Logistik])
 │   ├── Timestamp Relatif ("15 Menit Lalu")
 │   ├── Konten Catatan / Justifikasi Margin / Instruksi Khusus
 │   └── Lampiran Pendukung (Brosur, Bukti Chat Prinsipal)
 └── Form Input Catatan Baru
     ├── Pilihan Tipe: General Note, Justifikasi Approval, Update Logistik, Catatan Finance
     ├── Input Teks & Upload Berkas
     └── Auto-Dispatch Event: Mengirim notifikasi in-app lonceng ke PIC terkait
```

1. **Penyematan pada Halaman Tampilan Filament:**
   - Disematkan pada footer atau sidebar kanan modul `ViewInquiry`, `ViewQuotation`, `ViewSalesOrder`, dan `ViewDeliveryOrder`.
2. **Efisiensi Server:**
   - Menggunakan query database relasional `internal_comments` terindeks.
   - Reaktif melalui Livewire component refresh, 100% aman dijalankan di LiteSpeed Shared Hosting 512 MB RAM.



