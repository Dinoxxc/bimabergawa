# 04. Arsitektur Filament Resource & Antarmuka ERP (Filament Resources & UI)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-04` |
| **Versi Dokumen** | 1.10.0 |
| **Tanggal Efektif** | Oktober 2026 |
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
| `1.4.4` | 06 Sep 2026 | Principal System Architect | Disetujui | Spesifikasi fitur Mention `@User`, dispatch notifikasi otomatis, dan pembatasan dual-layer chat agen freelance |
| `1.4.5` | 07 Sep 2026 | Principal System Architect | Disetujui | Arsitektur CMS Website Dinamis (`ManageWebsiteContent`, `ManageLegalPages`), ArticleResource, dan mekanisme Tag Cache Invalidation |
| `1.5.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan spesifikasi `InvoiceResource` (faktur komersial, tempo kredit, upload bukti transfer) & `ActivityLogResource` |
| `1.5.1` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan kalkulasi reaktif `paid_amount` & `remaining_balance` pada tabel dan modal verifikasi pembayaran `InvoiceResource` |
| `1.6.0` | 01 Okt 2026 | Principal Security Architect | Disetujui | Penambahan Bab 3 Protokol Keamanan, Hardening, & Kontrol Akses Panel ERP Fase 4 (Passwordless OTP, HTTP Security Headers, Spatie RBAC gates, anti-IDOR scopes, & proteksi serialisasi model $hidden) |
| `1.7.0` | 02 Okt 2026 | Principal System Architect | Disetujui | Penyelarasan urutan roadmap eksekusi: Panel ERP dan Portal Agen Freelance diprioritaskan menjadi Fase 3 (menggantikan Fase 4) untuk mematangkan master katalog dan alur operasional internal sebelum rilis etalase publik |
| `1.8.0` | 02 Okt 2026 | Principal System Architect | Disetujui | Adopsi Arsitektur Panel Opsi B: Pemisahan 2 Panel Provider Mandiri di subdomain `app.bimabergawa.com` (`AdminPanelProvider` pada `/admin` & `AgentPanelProvider` pada `/mitra`) dengan root auto-redirection cerdas berbasis peran Spatie RBAC, standar kenyamanan tabel (Comfortable Table Spacing Density), dan isolasi resource mutlak 0% kebocoran HPP |
| `1.9.0` | 03 Okt 2026 | Principal System Architect | Disetujui | Penambahan `SupplierResource` (Manajemen Rekanan Vendor & Distributor Prinsipal), Halaman Eksekutif `ReportsHub` (4 Tab: Penjualan, Margin HPP & Profitabilitas, Umur Piutang AR Aging, Rekapitulasi Komisi Agen), Fitur One-Click WhatsApp Dispatch & Digital QR Code Verification pada PDF transaksi |
| `1.10.0` | 03 Okt 2026 | Principal System Architect | Disetujui | Penambahan spesifikasi UI Multi-Sourcing Vendor Matrix pada `ProductResource` (Repeater Sumber Kulakan) dan `SupplierResource` (RelationManager Produk Tersuplai dengan perbandingan HPP & Lead Time) |

---

## 1. Arsitektur Komponen Filament ERP & Topologi Dua Panel Mandiri (Option B Architecture)

Panel operasional internal dan portal kemitraan dibangun di atas **Filament Admin v3** yang memanfaatkan reaktivitas **Livewire 3**, **Alpine.js**, dan **Tailwind CSS**. Sistem mengadopsi arsitektur **Dua Panel Provider Mandiri (Option B)** di bawah subdomain tunggal `app.bimabergawa.com`:

```
                                  [SUBDOMAIN ERP: app.bimabergawa.com]
                                                   │
                           ┌───────────────────────┴───────────────────────┐
                           ▼                                               ▼
             [RUTE ROOT: GET /]                              [OTENTIKASI: /admin/login]
        PanelRedirectionController                           Passwordless Email OTP (300s)
                           │                                               │
             ┌─────────────┴─────────────┐                                 │
             ▼                           ▼                                 │
    [Peran Staf Internal]       [Peran Mitra Agen]                         │
  (Super Admin/Sales/Fin/Log)    (freelance_agent)                         ▼
             │                           │                    [Redirect Berdasarkan Role]
             ▼                           ▼                                 │
    ┌─────────────────┐         ┌─────────────────┐                        │
    │  PANEL INTERNAL │         │  PORTAL MITRA   │◄───────────────────────┘
    │     /admin      │         │     /mitra      │
    └────────┬────────┘         └────────┬────────┘
             │                           │
  [AdminPanelProvider]          [AgentPanelProvider]
  • Master Katalog & HPP        • Inkuiri Milik Sendiri (Terisolasi)
  • CRM Kanban & Stale Alert    • Ringkasan Omset & Komisi
  • Quotation Builder Margin    • Pengajuan Pencairan Dana
  • Sales Order & Approval      • Unduh Slip Transfer Bank
  • Surat Jalan Parsial (DO)    • Chat Tim Sales (Tanpa HPP)
  • Invoicing & Verifikasi Bank • 0% Resource Finansial Terdaftar
  • Manajemen Vendor & Supplier
  • Executive Reports Hub
  • System Settings Hub
```

### 1.1 Struktur Direktori Komponen & Panel Provider

```
app/
├── Http/
│   └── Controllers/
│       └── PanelRedirectionController.php      # Auto-redirect root GET / ke /admin atau /mitra
├── Providers/
│   └── Filament/
│       ├── AdminPanelProvider.php              # Panel Staf Internal (Path: /admin, ID: admin)
│       └── AgentPanelProvider.php              # Portal Agen Luar (Path: /mitra, ID: agent)
├── Filament/
│   ├── Resources/                              # Resource Panel Internal (/admin)
│   │   ├── ProductResource.php                 # Master Produk, Spek Repeater, Modal HPP
│   │   ├── CategoryResource.php                # Taksonomi Kategori Multi-Level
│   │   ├── BrandResource.php                   # Master Brand Global Resmi
│   │   ├── SupplierResource.php                # Master Rekanan Vendor & Distributor Tingkat 1
│   │   ├── UnitResource.php                    # Master Satuan Unit Pengukuran (UOM)
│   │   ├── CustomerResource.php                # Master Perusahaan & Kontak Klien B2B
│   │   ├── InquiryResource.php                 # CRM Inquiries, Kanban Board, Stale Alert
│   │   ├── QuotationResource.php               # Quotation Builder, Live Margin Guard
│   │   ├── SalesOrderResource.php              # Sales Order, Locking PO, Approval Direksi
│   │   ├── DeliveryOrderResource.php           # Partial Delivery Order, Ekspedisi & Resi
│   │   ├── InvoiceResource.php                 # Faktur Komersial & Verifikasi Bukti Bank
│   │   ├── FreelanceSalesResource.php          # Manajemen Profil Mitra Agen & Ledger
│   │   ├── CommissionPayoutResource.php        # Verifikasi Pencairan Komisi & Upload Slip
│   │   └── ActivityLogResource.php             # Viewer Jejak Audit Sistem (Super Admin)
│   ├── Pages/                                  # Halaman Khusus Panel Internal (/admin)
│   │   ├── Dashboard.php                       # Executive Operational Dashboard
│   │   ├── ReportsHub.php                      # Executive Business & Financial Reports Hub
│   │   ├── ManageSystemSettings.php            # Pengaturan PPN Dinamis, Margin, & Hotline
│   │   ├── ManageWebsiteContent.php            # CMS Konten Beranda & Profil Perusahaan
│   │   └── ManageLegalPages.php                # CMS Kebijakan Privasi (UU PDP) & Syarat
│   └── Agent/                                  # Resource Eksklusif Portal Agen (/mitra)
│       ├── Resources/
│       │   ├── AgentInquiryResource.php        # Inkuiri Agen (0% HPP, AgentTenantScope)
│       │   └── AgentPayoutResource.php         # Riwayat Komisi & Form Pengajuan Klaim
│       └── Pages/
│           ├── AgentDashboard.php              # Kartu Metrik Performa & Saldo Komisi
│           └── AgentProfilePage.php            # Profil Rekening Bank & Berkas KTP Privat
```

### 1.2 Standar Kepadatan Tampilan Tabel (Comfortable Table Spacing Density)
Seluruh tabel Filament pada kedua panel (`AdminPanelProvider` dan `AgentPanelProvider`) menerapkan konfigurasi kepadatan **Lapang & Ergonomis (*Comfortable Spacing Density*)**:
- Baris tabel memiliki padding vertikal optimal (`py-3.5` atau `py-4`) untuk mencegah kelelahan mata operator yang meninjau ratusan baris data transaksi harian.
- Hairline border horizontal monokrom halus (`#e4e4e7` / Dark: `#27272a`) memisahkan antar baris dengan kontras seimbang.
- Tipografi data menggunakan font **Inter** dengan *line-height* proporsional, serta kode SKU part number dalam font **JetBrains Mono**.
- Selektor jumlah baris interaktif tersedia di footer tabel: pilihan `[ 10 ]`, `[ 25 ]` (default), `[ 50 ]`, dan `[ 100 ]` baris per halaman.

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

### 2.10 Komponen Diskusi & Catatan Transaksi Berkonteks (Internal Comments & Contextual Discussion Component)

Untuk mendukung koordinasi tanpa friksi antar-staf internal serta komunikasi terarah dengan mitra agen freelance tanpa membebani server dengan koneksi WebSocket persisten, sistem menerapkan komponen diskusi berbasis Livewire 3 (`App\Livewire\InternalCommentsTimeline`) yang disematkan pada setiap halaman tampilan transaksi:

```
+─────────────────────────────────────────────────────────────────────────────+
|       KOMPONEN DISKUSI & TIMELINE TRANSAKSI BERKONTEKS (Livewire 3)         |
+─────────────────────────────────────────────────────────────────────────────+
 [Model: Quotation / SO / Inquiry / DO] <--- Polymorphic (commentable)
                 │
                 ▼
 [Livewire Component: InternalCommentsTimeline]
 ├── Tab Visibilitas Ganda (Dual-Layer Visibility Tabs)
 │   ├── Tab 1: [Catatan Internal Staf] (type = 'internal') -> Hanya Staf Internal
 │   └── Tab 2: [Pesan ke Agen / Mitra] (type = 'agent_visible') -> Staf & Agen Freelance
 ├── Daftar Percakapan Berurut Waktu (Chronological Activity Feed)
 │   ├── Avatar Pengguna & Text Badge Role ([Sales PIC] / [Direksi] / [Mitra Agen])
 │   ├── Timestamp Relatif ("15 Menit Lalu")
 │   ├── Konten Catatan / Justifikasi Margin / Instruksi Khusus (Support @User Mention)
 │   └── Lampiran Pendukung (Brosur PDF, Bukti Chat Prinsipal, Gambar WebP)
 └── Form Input Catatan Baru
     ├── Input Teks dengan Autocomplete Popover @User (Alpine.js)
     ├── Pilihan Tipe: Catatan Internal vs Pesan ke Agen
     ├── Upload Berkas Lampiran Pendukung
     └── Auto-Dispatch Event:
         ├── Simpan Array mentioned_user_ids (JSON) ke internal_comments
         ├── Kirim UserMentionedNotification (In-App Bell Drawer + Email)
         └── Kirim Event Reaktif ke Komponen Penerima
```

1. **Penyematan pada Halaman Tampilan Filament:**
   - **Internal ERP Panel (`app.bimabergawa.com`):** Disematkan pada footer/sidebar kanan modul `ViewInquiry`, `ViewQuotation`, `ViewSalesOrder`, dan `ViewDeliveryOrder`.
   - **Agent Portal (`app.bimabergawa.com/agent`):** Disematkan **EKSKLUSIF HANYA** pada halaman `ViewInquiry` milik agen itu sendiri. Agen dilarang keras mengakses modul Quotation, SO, maupun DO.

2. **Fitur Mention Pengguna (`@User`) & Integrasi Notifikasi:**
   - **Input Popover Autocomplete:** Saat pengguna mengetik `@`, popover Alpine.js menampilkan daftar pengguna aktif yang relevan.
   - **Parsing & Penyimpanan Data:** Sistem memparsing teks, mengekstrak user ID, dan menyimpannya pada kolom `mentioned_user_ids JSON` (contoh: `[2, 5]`).
   - **Trigger Notifikasi Instan:** Setiap pengguna yang di-mention otomatis menerima:
     - Notifikasi in-app pada database notification drawer (`notifications` table).
     - Notifikasi email formal jika pengaturan notifikasi email pengguna aktif.

3. **Batasan Keamanan & Hak Akses Agen Freelance (Strict Security Boundary):**
   - **Zero Margin & HPP Leak (Rule 3.3 & Rule 4):** Mitra Agen **DILARANG KERAS** melihat diskusi bertipe `internal` yang memuat perhitungan HPP, margin kotor, diskon prinsipal, atau catatan komersial rahasia.
   - **Isolasi Catatan pada Inkuiri:**
     - Staf Internal dapat melihat kedua tab: `[Catatan Internal Staf]` dan `[Pesan ke Agen]`.
     - Mitra Agen pada portal agen **HANYA** melihat tab `[Pesan ke Tim Sales PBB]` (`type = 'agent_visible'`). Tab `[Catatan Internal Staf]` dihilangkan 100% dari antarmuka agen di level query Eloquent (`WHERE type = 'agent_visible'`).
   - **Aturan Mention Mitra Agen:**
     - Mitra Agen **hanya diperbolehkan me-mention Sales PIC yang ditugaskan (*assigned PIC*)** pada inkuiri tersebut. Sistem menolak dan memfilter mention ke Direksi, Finance, atau staf lain di luar hak akses agen.

4. **Efisiensi Server & Kompatibilitas Shared Hosting:**
   - Menggunakan query database relasional `internal_comments` terindeks polimorfik (`commentable_type`, `commentable_id`).
   - Pembaruan UI dilakukan secara reaktif melalui polling interval ringan Livewire (cth: `wire:poll.30s` atau event emit) tanpa koneksi daemon WebSocket (Soketi/Node.js) sehingga 100% aman dan stabil pada LiteSpeed Shared Hosting RAM 512 MB.

---

### 2.11 Arsitektur Modul Konten Website CMS & Manajemen Legalitas

Modul **Konten Website CMS** melayani kebutuhan *Full Dynamic Site CMS* (PRD Section 2.8 & SRS FR-COMPRO-07) yang memungkinkan Super Admin mengelola seluruh copy teks, media promosi, kontak resmi, dan dokumen hukum website publik (`bimabergawa.com`) tanpa mengubah kode sumber:

#### 2.11.1 Filament Custom Page: `ManageWebsiteContent`
- **Tujuan:** Mengelola konten beranda, section profil, dan parameter telemetri kontak.
- **Rute ERP:** `https://app.bimabergawa.com/admin/website-content`
- **Penyimpanan:** Tabel `system_settings` dengan `group = 'cms_content'` dan `is_public = 1`:
  - `cms.hero_headline` : Judul utama hero section (string)
  - `cms.hero_subheadline` : Narasi subjudul hero section (text)
  - `cms.hero_cta_primary_label` / `cms.hero_cta_primary_url` : Tombol aksi utama (Lihat Katalog)
  - `cms.hero_cta_secondary_label` / `cms.hero_cta_secondary_url` : Tombol aksi sekunder (Minta RFQ)
  - `cms.hero_banner_image` : Path file WebP hasil optimasi pada `storage/app/public/cms/`
  - `cms.stats_counter` : JSON array metrik pencapaian (4 prinsipal, 2500+ SKU, dll.)
  - `cms.company_history` : Deskripsi profil korporat PT. Panca Bima Bergawa
  - `cms.rasuna_said_maps_url` : Embed URL Google Maps kantor Gd. Wirausaha Rasuna Said

#### 2.11.2 Filament Custom Page: `ManageLegalPages`
- **Tujuan:** Pengelolaan halaman hukum dan kepatuhan privasi (UU PDP No. 27/2022).
- **Rute ERP:** `https://app.bimabergawa.com/admin/legal-pages`
- **Penyimpanan:** Tabel `system_settings` dengan `group = 'legal_page'` dan `is_public = 1`:
  - `legal.terms_and_conditions` : Syarat dan ketentuan transaksi komersial B2B (HTML/Markdown)
  - `legal.privacy_policy` : Kebijakan privasi perlindungan data pribadi UU PDP (HTML/Markdown)
  - `legal.warranty_policy` : Kebijakan garansi resmi pabrikan prinsipal (HTML/Markdown)
  - `legal.last_updated_at` : Tanggal efektif pembaruan dokumen hukum

#### 2.11.3 Filament Resource: `ArticleResource`
- **Tujuan:** Publikasi artikel teknis industri, studi kasus elektrikal, dan panduan produk B2B.
- **Model Terkait:** `App\Models\Article` dan `App\Models\ArticleCategory`.
- **Fitur Antarmuka:**
  - Auto-generate slug URL ramah SEO dari judul artikel.
  - Unggah thumbnail artikel teroptimasi WebP 82% (Max 1600px).
  - RichEditor / Tiptap dengan dukungan heading, blockquote, tabel, dan format kode.
  - Kolom SEO terdedikasi: `meta_title`, `meta_description`, `canonical_url`.
  - Filter tabel berdasarkan kategori artikel dan status publikasi (`draft` vs `published`).

#### 2.11.4 Mekanisme Cache & Real-Time Invalidation
1. **Public Web Controller Caching:**
   - Controller publik (`HomeController`, `LegalController`, `ArticleController`) membaca konten menggunakan cache:
     ```php
     $cmsContent = Cache::rememberForever('public_cms_bundle', function () {
         return SystemSetting::where('group', 'cms_content')
             ->where('is_public', true)
             ->pluck('value', 'key');
     });
     ```
2. **Instant Invalidation Trigger:**
   - Metode `save()` pada `ManageWebsiteContent` dan `ManageLegalPages` memicu pembersihan cache:
     ```php
     Cache::forget('public_cms_bundle');
     Cache::forget('public_legal_bundle');
     // Tagged cache flush jika didukung:
     // Cache::tags(['public_website_content'])->flush();
     ```
   - Pengunjung website publik menerima pembaruan secara instan pada permintaan HTTP berikutnya tanpa membutuhkan *artisan optimize:clear* manual.

---

### 2.12 Arsitektur Filament Resource: `InvoiceResource` & `ActivityLogResource`

#### 2.12.1 `InvoiceResource` (Manajemen Penagihan & Finansial B2B)
- **Tujuan:** Pengelolaan faktur komersial resmi (`INV-YYYYMM-XXXX`), pemantauan jatuh tempo kredit (Net 14/30/60), dan verifikasi pembayaran transfer bank oleh peran **Finance** dan **Super Admin**.
- **Model Terkait:** `App\Models\Invoice` dan `App\Models\InvoiceItem`.
- **Fitur Antarmuka:**
  - **Tabel Data:** Menampilkan nomor invoice, nomor SO, nama perusahaan klien, tanggal terbit, tanggal jatuh tempo, grand total, status pembayaran (`[Unpaid]`, `[Partially Paid]`, `[Paid]`, `[Overdue]`, `[Cancelled]`), nominal terbayar (`paid_amount`), sisa piutang (`remaining_balance`), serta indikator visual keterlambatan bayar.
  - **Filter Khusus:** Filter status pelunasan, filter rentang tanggal jatuh tempo, dan filter soft deletes (`TrashedFilter`).
  - **Aksi Transaksional:**
    - `Verifikasi Pembayaran`: Modal upload struk / bukti transfer bank (`payment_proof_path`), pilihan metode bayar, dan pencatatan tanggal lunas (`paid_at`).
    - `Unduh PDF Faktur`: Mengunduh dokumen invoice resmi standar A4 kop Rasuna Said via DomPDF.
    - `Restore / Force Delete`: Hak eksklusif Super Admin untuk memulihkan faktur yang terhapus lunak (*soft-deleted*).

#### 2.12.2 `ActivityLogResource` (Viewer Audit Trail Sistem)
- **Tujuan:** Antarmuka *read-only* bagi Super Admin dan Direksi untuk mengaudit seluruh rekaman mutasi model kritis.
- **Model Terkait:** `App\Models\ActivityLog`.
- **Fitur Antarmuka:**
  - Menampilkan waktu kejadian, subjek target (Quotation, Sales Order, Invoice, System Settings), nama eksekutor (*causer*), IP address, dan modal dialog *diff comparison* data sebelum vs sesudah.

---

### 2.13 `SupplierResource` (Manajemen Rekanan Vendor & Distributor Tingkat 1)

- **Tujuan:** Pengelolaan basis data rekanan rantai pasok industri (*supply chain*), distributor resmi prinsipal tier-1, dan pabrikan pendukung pengadaan komponen B2B oleh peran **Super Admin**, **Sales PIC**, dan **Finance**.
- **Model Terkait:** `App\Models\Supplier`.
- **Rute Panel:** `/admin/suppliers`

#### 1. Form Schema (`form`):
1. **Section Identitas Rekanan Vendor:**
   - `name`: Nama resmi badan usaha PT/CV vendor (contoh: *PT. Schneider Indonesia Distributor*, *PT. Kitz Valve Trading*).
   - `code`: Kode unik internal vendor format alfabetik 3-6 karakter (`code`, contoh: `SUP-SCH`, `SUP-ABB`).
   - `brand_id`: Relasi opsi ke `brands` (menandai jika vendor merupakan distributor utama brand tertentu).
   - `is_active`: Toggle status aktif kerja sama.
2. **Section Kontak PIC & Komunikasi:**
   - `contact_person`: Nama lengkap PIC / Sales Representative rekanan.
   - `phone`: Nomor telepon / WhatsApp resmi kantor/PIC untuk jalur konfirmasi stok dan negosiasi PO.
   - `email`: Alamat email penawaran dan pemesanan (*purchasing*).
3. **Section Alamat & Operasional:**
   - `address`: Alamat kantor pusat atau gudang asal pengiriman barang rekanan.
4. **Section Finansial & Komersial (Terbatas untuk Super Admin & Finance):**
   - `payment_terms`: Ketentuan tempo pembayaran standar vendor (Pilihan: *100% Cash Before Delivery (CBD)*, *Cash on Delivery (COD)*, *Net 14 Hari*, *Net 30 Hari*, *Net 60 Hari*).
   - `discount_structure`: Deskripsi skema diskon prinsipal (contoh: *Diskon Dasar 35% + Proyek 5%*).
   - `notes`: Catatan evaluasi keandalan pengiriman (*lead time*) dan riwayat SLA vendor.

#### 2. Konfigurasi Tabel Data (`table`):
- **Kolom Tabel:**
  - `name`: Nama vendor (dengan indikator status aktif).
  - `code`: Badge kode unik (`SUP-XXX`).
  - `brand.name`: Nama brand prinsipal yang disuplai.
  - `contact_person`: Nama PIC rekanan.
  - `phone`: Nomor telepon/WA PIC dengan aksi klik langsung.
  - `payment_terms`: Badge termin pembayaran.
  - `products_count`: Jumlah part number / SKU yang terhubung (`primary_supplier_id`).
  - `is_active`: Status badge `[Active]` / `[Inactive]`.
- **Filter Tabel:** Filter brand prinsipal, filter status aktif, dan filter termin pembayaran.
- **Aksi Cepat:**
  - `Action::make('hubungi_wa')` -> Membuka WhatsApp chat ke nomor PIC rekanan untuk menanyakan ketersediaan stok atau update harga beli modal.
  - `Restore / Force Delete Action` -> Proteksi *soft deletes* data histori rekanan.

#### 3. Integrasi Multi-Sourcing Vendor Matrix pada `ProductResource`:
Pada form edit/create `ProductResource` di tab *"Sumber Kulakan & Rekanan Vendor"*, disediakan antarmuka pengadaan multi-vendor yang terhubung ke tabel pivot `product_suppliers`:
- `supplier_id`: Dropdown pilihan rekanan vendor (terfilter `is_active = true`).
- `supplier_sku`: Part number / kode katalog resmi versi rekanan vendor tersebut.
- `purchase_price`: Harga beli modal kulakan dari vendor tersebut (HPP per supplier).
- `currency`: Mata uang pembelian (IDR, USD, EUR).
- `lead_time_days`: Estimasi hari pengadaan (0 hari = Ready Stock gudang vendor).
- `min_order_qty`: Minimum Order Quantity (MOQ) pembelian.
- `is_primary`: Toggle penanda rekanan prioritas utama (otomatis mensinkronkan `primary_supplier_id`).
- `notes`: Catatan ketersediaan stok atau ketentuan khusus vendor.

#### 4. RelationManager Produk Tersuplai pada `SupplierResource` (`SuppliedProductsRelationManager`):
Pada halaman tinjauan rekanan vendor, staf internal dapat melihat seluruh katalog part number / SKU yang dapat disuplai oleh rekanan tersebut lengkap dengan kolom harga modal kulakan, lead time, MOQ, dan status *Primary* vs *Alternatif*.

---

### 2.14 `ReportsHub` (Executive Business & Financial Reports Hub)

- **Tujuan:** Pusat pelaporan eksekutif bisnis, audit laba kotor, evaluasi arus kas piutang, dan performa penjualan terintegrasi untuk pengambilan keputusan strategis Direksi dan Departemen Keuangan.
- **Model Terkait:** Agregasi dari `Inquiry`, `Quotation`, `SalesOrder`, `Invoice`, `CommissionPayout`, dan `Product`.
- **Rute Panel:** `/admin/reports`
- **Hak Akses Spatie RBAC:**
  - Super Admin & Direksi: Akses penuh 4 Tab.
  - Finance: Akses Tab 2 (Margin HPP), Tab 3 (AR Aging), dan Tab 4 (Komisi Agen).
  - Sales PIC: Dibatasi hanya dapat melihat Tab 1 (Performa Penjualan & Konversi).
  - Logistik & Mitra Agen: Dilarang total (403 Forbidden).

#### Antarmuka 4 Tab Analisis Bisnis:

1. **Tab 1: Laporan Performa Penjualan & Konversi (*Sales & Conversion Performance*):**
   - **Metrik Utama (Stat Cards):**
     - Total Inkuiri Masuk (Jumlah prospek baru).
     - Rasio Konversi Penawaran (*Win-Loss Deal Ratio* %).
     - Total Nilai Sales Order (Akumulasi omzet bruto terkonfirmasi).
     - Nilai Rata-rata Penjualan (*Average Order Value / AOV*).
   - **Visualisasi & Tabel Data:**
     - Grafik tren omzet bulanan (Realisasi Penjualan vs Periode Lalu).
     - Breakdown perolehan omzet per Brand Prinsipal (Schneider, ABB, Kitz, Omron, dll.).
     - Matriks produktivitas per Sales PIC (Jumlah quotation terbit vs deal).
   - **Filter Kontrol:** Rentang tanggal transaksi, pilihan Sales PIC, Brand, dan tipe inkuiri (Proyek vs Pengadaan Rutin).

2. **Tab 2: Analisis Profitabilitas & Margin HPP (*Profitability & Margin Analysis*):**
   - **Metrik Utama (Stat Cards):**
     - Total Pendapatan Bersih (Total DPP Sales Order).
     - Total Beban Pokok Pendapatan (Akumulasi Realisasi Modal HPP).
     - Total Laba Kotor (*Gross Profit* dalam Rupiah).
     - Rata-rata Margin Transaksi (% Margin Terbobot).
   - **Visualisasi & Tabel Data:**
     - Distribusi tingkat margin transaksi: Hijau (>= 15%), Kuning (8% - 14.99%), Merah Kritis (< 8%).
     - Tabel daftar transaksi berskala margin kritis yang mendapatkan persetujuan khusus Direktur.
     - Analisis marjin kontribusi per kategori produk (Elektrikal vs Mekanikal vs Otomasi).
   - **Proteksi Kerahasiaan:** Nilai HPP dan margin 100% terlindung dari peran operasional non-manajemen.

3. **Tab 3: Laporan Umur Piutang Komersial (*Accounts Receivable / AR Aging Report*):**
   - **Metrik Utama (Stat Cards):**
     - Total Piutang Belum Tertagih (*Total Outstanding AR*).
     - Piutang Lancar (*Current / Belum Jatuh Tempo*).
     - Piutang Jatuh Tempo 1 - 30 Hari (*Overdue Tier 1*).
     - Piutang Jatuh Tempo 31 - 60 Hari (*Overdue Tier 2*).
     - Piutang Kritis / Macet > 60 Hari (*Critical Overdue*).
   - **Tabel Rekap Penagihan Faktur:**
     - Menampilkan: Nama Perusahaan Klien, Nomor Invoice, Tanggal Jatuh Tempo, Nilai Faktur, Terbayar, Sisa Tagihan, dan Kategori Aging.
     - Aksi Cepat: Tombol "Kirim Pengingat WA" (menghasilkan draft pesan penagihan formal ke bagian keuangan klien).

4. **Tab 4: Rekapitulasi Komisi Agen Freelance (*Agent Commission Summary*):**
   - **Metrik Utama (Stat Cards):**
     - Total Akumulasi Komisi Terbit.
     - Total Komisi Telah Dicairkan (*Paid*).
     - Saldo Komisi Tertunda Siap Cair (*Payable Balance*).
   - **Tabel Peringkat & Performa Mitra:**
     - Tabel peringkat agen freelance berdasar perolehan omzet dan komisi.
     - Status verifikasi berkas transfer perbankan oleh Finance.

#### Ekspor Berkas Formal:
- Tersedia tombol "Ekspor Spreadsheet (Excel/CSV)" dengan filter aktif.
- Tersedia tombol "Cetak Ringkasan Eksekutif PDF" standar ISO A4 bebas emoji dengan kop resmi kantor Rasuna Said untuk rapat manajemen bulanan.

---

### 2.15 One-Click WhatsApp Dispatch & Digital QR Code Verification pada Dokumen Transaksi

Untuk mempercepat siklus transaksi B2B dan mencegah risiko pemalsuan dokumen fisik di lapangan, sistem mengintegrasikan telemetri WhatsApp instan dan verifikasi QR Code kriptografis:

#### 1. One-Click WhatsApp Dispatch:
- Disematkan sebagai *header action* pada halaman detail transaksi:
  - `QuotationResource`: Aksi *"Kirim Penawaran via WA"* -> Menghasilkan tautan resmi penawaran harga dengan validitas 14 hari.
  - `SalesOrderResource`: Aksi *"Kirim Konfirmasi SO via WA"* -> Menghubungi procurement klien mengonfirmasi nomor PO yang diterima.
  - `DeliveryOrderResource`: Aksi *"Kirim Resi Logistik via WA"* -> Mengirimkan detail kurir kargo, nomor resi, dan link lacak ke PIC penerima proyek.
  - `InvoiceResource`: Aksi *"Kirim Pengingat Tagihan via WA"* -> Mengirimkan invoice resmi dan instruksi rekening korporat PBB.
- **Mekanisme Teknis:**
  - Membuka URL terenkode: `https://api.whatsapp.com/send?phone={phone_clean}&text={encoded_message}`.
  - Teks pesan disusun otomatis dari template profesional `system_settings` (menyebutkan nomor referensi, nama PIC, rincian barang, dan tautan unduh dokumen).
  - Setiap kali tombol diklik, sistem secara otomatis mencatat riwayat telemetri ke `ActivityLog` dengan keterangan *"WhatsApp dispatch executed"*.

#### 2. Digital QR Code Verification pada Dokumen PDF:
- Seluruh dokumen transaksi formal yang diterbitkan via DomPDF (`Quotation`, `SalesOrder`, dan `DeliveryOrder`) memuat QR Code otentisitas digital di pojok kanan bawah dokumen (di samping lembar tanda tangan / cap basah).
- **Mekanisme Pembangkitan:**
  - Dokumen memuat URL verifikasi unik bertanda tangan kriptografis: `https://app.bimabergawa.com/verify-doc/{type}/{verification_hash}`.
  - Hash verifikasi dibangkitkan dari kombinasi: `hash('sha256', $documentNumber . $createdAt . config('app.key'))`.
- **Halaman Hasil Pemindaian Publik (`/verify-doc/...`):**
  - Halaman ringan berbasis Blade + Tailwind CSS yang dapat diakses oleh petugas pemeriksa lapangan, satpam proyek gudang, atau tim audit klien tanpa perlu login ERP.
  - Menampilkan segel status otentikasi:
    - Status Dokumen: `[ASLI & TERVERIFIKASI RESMI]`
    - Entitas Penerbit: `PT. Panca Bima Bergawa (Gd. Wirausaha Rasuna Said)`
    - Nomor Dokumen: Format korporat resmi (`PBB/...`)
    - Tanggal Penerbitan & Otorisasi: Waktu tepat dokumen disetujui Direksi.
    - Nama Perusahaan Tujuan: Memastikan dokumen tidak disalahgunakan untuk entitas lain.
    - Status Surat Jalan: Khusus Surat Jalan, menampilkan kuantiti fisik barang yang disahkan keluar dari gudang.

---

## 3. Protokol Keamanan, Hardening, & Kontrol Akses Panel ERP (Fase 3 Security Suite)

Untuk menjamin kerahasiaan data operasional internal, margin keuntungan, modal HPP, dan data pribadi mitra agen di subdomain internal (`app.bimabergawa.com`), arsitektur Fase 3 menerapkan 5 lapis pengamanan terpadu:

### 3.1 Protokol Otentikasi Passwordless Email OTP
Panel ERP tidak menggunakan password statis yang rentan terhadap kebocoran (*credential stuffing*). Seluruh staf internal dan mitra agen freelance wajib melakukan otentikasi melalui *Passwordless Email OTP*:
1. **Pembangkitan Token Kriptografis (CSPRNG):**
   - Kode OTP 6-digit dihasilkan menggunakan generator acak kriptografis `random_int(100000, 999999)`.
   - Kode di-hash menggunakan SHA-256 sebelum disimpan ke backend cache (`otp:{email}`).
   - Masa berlaku (*Time-to-Live*) tepat **300 detik (5 menit)**.
2. **Pembatasan Laju Percobaan (*Rate Limiting & Anti-Brute-Force*):**
   - Batas permintaan OTP: Maksimal 3 kali percobaan verifikasi per 10 menit per email/IP (`throttle:3,10`).
   - Tombol "Kirim Ulang OTP" di antarmuka Livewire terkunci *cooldown* 60 detik dengan hitung mundur visual.
3. **Penghangusan Seketika (*One-Time Burn*) & Regenerasi Sesi:**
   - Token OTP langsung dihapus dari cache segera setelah berhasil diverifikasi guna mengeliminasi *replay attack*.
   - Saat sesi otentikasi terbentuk, sistem mengeksekusi `session()->regenerate()` untuk mencegah eksploitasi *session fixation*.

### 3.2 Hardening HTTP Security Headers Subdomain ERP (`app.bimabergawa.com`)
Seluruh respons HTTP dari subdomain internal wajib menyertakan set header keamanan yang diinjeksikan melalui middleware `App\Http\Middleware\ApplySecurityHeaders`:
- **`X-Frame-Options: SAMEORIGIN`:** Mencegah peretasan berbasis *Clickjacking* atau penyematan panel ERP dalam iframe berbahaya pihak ketiga.
- **`X-Content-Type-Options: nosniff`:** Mencegah eksploitasi manipulasi *MIME-sniffing* saat staf mengunduh atau mengunggah lampiran PDF, gambar faktur, dan bukti transfer.
- **`X-XSS-Protection: 1; mode=block`:** Mengaktifkan filter XSS peramban bawaan.
- **`Referrer-Policy: strict-origin-when-cross-origin`:** Mencegah kebocoran parameter URL sensitif ke domain eksternal.
- **`Content-Security-Policy (CSP)`:** Mengizinkan eksekusi skrip internal aman (`'self' 'unsafe-inline' 'unsafe-eval'`) yang dibutuhkan oleh reaktivitas Livewire 3 dan Alpine.js pada Filament Admin.
- **`X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`:** Menginstruksikan seluruh robot pencari (Googlebot, Bingbot, dll.) untuk tidak mengindeks, mencache, atau menampilkan pratinjau halaman ERP pada hasil pencarian publik.

### 3.3 Penegakan Otorisasi Spatie RBAC & Model Policy Gate
Akses ke setiap Filament Resource dan aksinya dikontrol secara terpusat oleh 5 peran resmi Spatie Permission:
- **`ProductResource`:** Hanya Super Admin dan Sales PIC yang dapat melihat modal HPP (`cost_price`). Peran Logistik hanya melihat kuantitas fisik dan model produk.
- **`InquiryResource`:** Sales PIC hanya dapat melihat inkuiri miliknya dan tim; Mitra Agen terisolasi pada inkuirinya sendiri.
- **`QuotationResource`:** Tombol "Setujui Penawaran Margin Kritis" dikunci secara ketat dan hanya dapat dieksekusi oleh Super Admin / Direksi melalui Gate Otorisasi (`ApproveDirectorAction`).
- **`InvoiceResource`:** Verifikasi status pembayaran lunas dan pengunggahan bukti transfer bank dibatasi secara eksklusif untuk peran **Finance** dan **Super Admin**.
- **`ManageSystemSettings`:** Tab HPP & Margin dibatasi untuk Super Admin/Direksi; Tab Komersial & Pajak untuk Finance; Tab Logistik untuk Staf Gudang.

### 3.4 Isolasi Data Multi-Tenant & Anti-IDOR (Global Scope Enforcement)
Mencegah serangan *Insecure Direct Object References* (IDOR) dari akun agen freelance:
- **`AgentTenantScope`:** Diterapkan secara global pada model `Inquiry`, `Quotation`, dan `CommissionPayout`. Membatasi kueri otomatis:
  ```php
  static::addGlobalScope('agent_tenant', function (Builder $builder) {
      if (auth()->check() && auth()->user()->hasRole('freelance_sales')) {
          $builder->where('freelance_sales_id', auth()->user()->freelance_sales_id);
      }
  });
  ```
  Staf internal (`super_admin`, `sales_pic`, `finance`, `logistics`) secara otomatis mendapat bypass agar operasional ERP tidak terhambat.
- **Isolasi Catatan & Komentar Internal (`agent_comment_visibility`):** Model `InternalComment` memfilter komentar secara otomatis. Akun agen freelance hanya dapat membaca komentar berstatus `type = 'agent_visible'`. Seluruh catatan negosiasi internal staf, margin modal, dan arahan direksi 100% terlindung dari akses agen.

### 3.5 Proteksi Serialisasi Data Sensitif pada Level Model Eloquent (`$hidden`)
Mengatasi celah kebocoran data terdekripsi pasca-kueri saat objek model diubah menjadi array atau JSON (`toArray()` / `toJson()`):
- **`Product` & `QuotationItem`:**
  `protected $hidden = ['cost_price'];`
  Memastikan modal HPP produk dan item penawaran tidak pernah bocor ke respons API, AJAX state, atau komponen antarmuka publik/agen.
- **`Quotation`:**
  `protected $hidden = ['total_cost', 'gross_profit_margin', 'director_notes'];`
  Menjamin total modal penawaran, margin kotor, dan catatan internal direksi tidak dapat diintip melalui inspeksi payload jaringan.
- **`FreelanceSales`:**
  `protected $hidden = ['nik', 'npwp_number', 'bank_account_number', 'ktp_file_path'];`
  Melindungi data pribadi sensitif agen freelance (PII terdekripsi) dari kebocoran tanpa sengaja pada response JSON, sesuai mandat kepatuhan UU PDP No. 27/2022.

