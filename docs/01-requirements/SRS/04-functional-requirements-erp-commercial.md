# 04. Kebutuhan Fungsional: Internal Commercial ERP Engine
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Functional Systems Analyst | Draf Awal | Spesifikasi teknis FR-AUTH, FR-INQ, FR-QUOTE, FR-SO, & FR-DO |
| `1.1.0` | 03 Sep 2026 | ERP Software Architect | Ditinjau | Penambahan FR-AGENT (Portal Freelance) & FR-CALC (Live Margin HPP) |
| `1.2.0` | 05 Sep 2026 | Lead Software Engineer | Disetujui | Finalisasi FR-NOTIF (Reminder otomatis H-3) & format DO tanpa harga |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Modul Autentikasi Masuk Panel ERP (`FR-AUTH`)

```
+----------------------------------------------------------------------------------------------------+
|                                  SPESIFIKASI TEKNIS FR-AUTH                                        |
+--------------+--------------------------+----------------------------------------------------------+
| ID Kebutuhan | Nama Fungsi              | Logika Pemrosesan Teknis & Output                        |
+--------------+--------------------------+----------------------------------------------------------+
| FR-AUTH-01   | Permintaan OTP ke Email  | Pengguna menginput email terdaftar di login panel ERP,   |
|              | (Request Login OTP)      | sistem men-generate kode numerik 6 digit, simpan hash di |
|              |                          | DB (kedaluwarsa 10 menit), & kirim via SMTP Resend/Brevo.|
| FR-AUTH-02   | Verifikasi OTP & Sesi    | Pengguna memasukkan kode 6 digit, sistem memvalidasi     |
|              | (Verify OTP & Login)     | kecocokan hash & timestamp, hanguskan OTP, inisiasi sesi |
|              |                          | login, dan redirect ke dashboard sesuai role pengguna.   |
| FR-AUTH-03   | Rate Limiter & Throttle  | Jeda minimal 60 detik antar request OTP, maks 3 kali     |
|              |                          | salah input per kode, dan maks 5 percobaan per IP/menit. |
+--------------+--------------------------+----------------------------------------------------------+
```

---

## 2. Modul Manajemen Prospek Multi-Channel (`FR-ERP`)

```
+----------------------------------------------------------------------------------------------------+
|                                  SPESIFIKASI TEKNIS FR-ERP                                         |
+--------------+--------------------------+----------------------------------------------------------+
| ID Kebutuhan | Nama Fungsi              | Logika Pemrosesan Teknis & Output                        |
+--------------+--------------------------+----------------------------------------------------------+
| FR-ERP-01    | Manual Lead Entry Form   | Form input prospek offline di ERP dengan pilihan sumber: |
|              |                          | direct_whatsapp, walk_in_phone, exhibition, referral.    |
| FR-ERP-02    | Kanban Pipeline Board    | Filament board drag-and-drop mengubah enum kolom status  |
|              |                          | ('new' -> 'contacted' -> 'quote' -> 'deal' -> 'lost').   |
| FR-ERP-03    | Lead Telemetry Profile   | Menampilkan parameter atribusi digital (UTM, Device, IP) |
|              |                          | dan tombol cepat buka WhatsApp Web.                      |
| FR-ERP-04    | Timeline Activity Notes  | Menyimpan catatan follow-up sales bertanggal/waktu.      |
| FR-ERP-05    | Smart Customer Autofill  | Query AJAX debounced riwayat pelanggan (PIC, email, NPWP)|
|              |                          | berdasarkan kecocokan nomor WhatsApp / nama PT.          |
| FR-ERP-06    | Automated Audit Trail Log| Spatie Activitylog mencatat log tidak dapat diubah atas  |
|              |                          | approval diskon, perubahan komisi, dan revisi harga quote|
+--------------+--------------------------+----------------------------------------------------------+
```

---

## 3. Modul Surat Penawaran Resmi & Komisi (`FR-QUOTE`)

### FR-QUOTE-01: 1-Click Conversion dari Inquiry
- **Proses:** Membaca data `inquiry_items`, membuat record baru di tabel `quotations` berstatus `draft`, dan menyalin setiap baris barang ke tabel `quotation_items`.

### FR-QUOTE-02: Kalkulator Komersial, Margin HPP, Skema Pembayaran & Pajak
- **Formula Perhitungan:**
  $$\text{Subtotal} = \sum (\text{item\_price} \times \text{quantity})$$
  $$\text{DPP} = \text{Subtotal} - \text{discount\_amount}$$
  $$\text{PPN (11\%)} = \text{DPP} \times 0.11$$
  $$\text{Grand Total} = \text{DPP} + \text{PPN}$$
  $$\text{Gross Profit Margin \%} = \frac{\text{DPP} - \sum (\text{cost\_price} \times \text{quantity})}{\text{DPP}} \times 100\%$$
- **Aturan Otorisasi:** Diskon $> 10\%$ atau Gross Profit Margin $< 15\%$ mewajibkan `is_approved_by_director = true` sebelum penawaran dapat berstatus `approved` atau `sent`.
- **Skema Pembayaran Fleksibel:**
  - Pilihan Tipe A (100% Penuh): *100% CBD (Awal)*, *100% COD (Akhir)*, *100% TOP Net 14/30/60 Hari*.
  - Pilihan Tipe B (Multi-Termin / Milestones): Repeater baris termin kustom (misal: DP 30% + 50% Kirim + 20% Retensi) dengan validasi total persentase $= 100\%$.

### FR-QUOTE-03: Generator Dokumen Resmi via DomPDF & Dynamic Watermark
- **Proses:** Mengompilasi view Blade `resources/views/pdf/quotation.blade.php` ke format PDF menggunakan `Barryvdh\DomPDF\Facade\Pdf`.
- **Dynamic Watermark:** Menambahkan layer watermark diagonal transparan:
  - Status `draft`: Watermark teks **"DRAFT - NOT OFFICIAL"**
  - Status `sent` / `approved`: Bersih tanpa watermark dengan Kop Surat & Tanda Tangan Resmi
  - Status `expired`: Watermark teks **"EXPIRED / VOID"**
- **Elemen Dokumen:** Kop Surat Korporat, Nomor Unik `QUO-YYYYMM-XXXX`, Masa Berlaku (14 hari), Spesifikasi Lengkap Barang, Skema Pembayaran Resmi, Rekening Bank Resmi, Tanda Tangan Digital Sales PIC.
- **Penyimpanan:** File disimpan di `storage/app/public/quotations/QUO-YYYYMM-XXXX.pdf`.

### FR-QUOTE-04: 1-Click WhatsApp Direct Sender & Auto-Email Prospek
- **1-Click WhatsApp Direct Sender:** Tombol aksi pada Filament View yang men-generate URI:
  `https://wa.me/{phone_wa}?text={urlencode($pesanPengantarDanLinkPdf)}`
- **Expiry Countdown Badge:** Kolom status menghitung `now()->diffInDays($quotation->expires_at)` dan merender badge: Hijau (> 5 hari), Kuning (1–3 hari), Merah (Kedaluwarsa).
- **Auto-Email Prospek:** Scheduled task harian mengecek penawaran dengan sisa waktu $= 3\text{ hari}$ dan otomatis mengirimkan email pengingat santun kepada email PIC klien.

---

## 4. Modul Sales Order & Surat Jalan Ekspedisi (`FR-SO`)

### FR-SO-01: Penerbitan Sales Order & Locking
- **Input:** Perubahan status penawaran ke `deal` + input `customer_po_number`.
- **Proses:** Sistem membuat record `sales_orders` dengan nomor `SO-YYYYMM-XXXX`, mengunci record `quotations` (read-only), dan mengubah status komisi freelance menjadi `payable` (saat invoice lunas).

### FR-SO-02: Generator Surat Jalan Ekspedisi (Partial Delivery Order)
- **Dukungan Partial Shipment:** 1 Sales Order dapat menerbitkan beberapa record `delivery_orders` (`DO-YYYYMM-XXXX/01`, `DO-YYYYMM-XXXX/02`) dan memantau sisa kuantiti belum terkirim.
- **Data Ekspedisi Transparan:** Tercantum Nama Jasa Ekspedisi (`courier_name`), Nomor Resi/Waybill/AWB (`tracking_number`), Estimasi Koli/Berat, Alamat Pabrik/Gudang Tujuan, Nama PIC Penerima Gudang, Instruksi Penanganan (*Handle with care*), serta Kolom Stempel/Tanda Terima Resmi.
- **Privasi Finansial:** **100% TANPA memuat kolom harga atau nominal rupiah**.

### FR-SO-03: Pelacakan Resi Ekspedisi Publik (`/lacak-rfq`)
- **Output:** Menampilkan status operasional barang (*processing -> indent_principal -> ready_to_ship -> shipped -> completed*), nama ekspedisi, nomor resi, foto bukti resi/surat jalan, dan tombol tautan langsung *"Cek Resi di Website Resmi Ekspedisi"*.

---

## 5. Modul Portal Freelance Sales & Komisi Mandiri (`FR-AGENT`)

### FR-AGENT-01: Isolasi Data Mutlak (Global Scope Tenant)
- **Implementasi:** Pada Model `Inquiry`, `Quotation`, `SalesOrder`:
  ```php
  protected static function booted(): void
  {
      static::addGlobalScope('freelance_scope', function (Builder $builder) {
          if (auth()->check() && auth()->user()->hasRole('freelance_sales')) {
              $builder->where('freelance_sales_id', auth()->user()->freelance_sales_id);
          }
      });
  }
  ```

### FR-AGENT-02: Konfigurasi Mandiri Level Komisi & Upload Bukti Transfer
- **Konfigurasi Tiering:** Super Admin dapat membuat $N$-level tingkatan komisi (Single Level Flat atau Multi-Tier Bronze/Silver/Gold) berdasarkan ambang batas omzet deal tahunan.
- **Upload Slip Bukti Transfer:** Saat status komisi diubah menjadi `paid`, admin wajib melampirkan berkas bukti transfer bank (`.jpg`, `.png`, `.pdf`) yang langsung dapat diunduh oleh mitra pada portalnya.

### FR-AGENT-03: Alur Screening & Approval Pendaftaran Mitra Freelance
- **Pemicu:** Pendaftaran baru dari form publik `/gabung-mitra` berstatus `pending_review`.
- **Fitur Verifikasi (Super Admin):**
  - Pemeriksaan kelengkapan berkas KTP/NIK, sektor industri yang dikuasai, pengalaman B2B, dan informasi rekening bank.
  - Aksi `Approve`: Super Admin menetapkan tier komisi, men-generate `referral_code`, dan mengubah status menjadi `approved`.
  - Aksi `Reject`: Super Admin menginput `rejection_reason` dan mengubah status menjadi `rejected`.
  - Aksi `Revision`: Mengubah status menjadi `revision_required` dan mengirim link revisi data ke calon mitra.

### FR-AGENT-04: Otomasi Provisioning Akun & Notifikasi Email
- **Proses:** Saat status pendaftaran diubah menjadi `approved`:
  - Sistem otomatis membuat record di tabel `users` (dengan email terdaftar, role `freelance_sales`, dan foreign key `freelance_sales_id`).
  - Sistem men-dispatch event `FreelancePartnerApproved` yang mengirimkan transactional email notifikasi resmi berisi tautan portal login dan panduan masuk via Passwordless Email OTP.

### FR-AGENT-05: WhatsApp Copywriting Share, Link Generator & Simulator
- **Link Generator:** Tombol pada baris produk yang menyalin URL:
  `config('app.url') . "/catalog/{$product->slug}?ref=" . auth()->user()->freelanceSales->referral_code`
- **WhatsApp Share Generator:** Tombol aksi yang membuka link WhatsApp dengan teks rekomendasi B2B resmi: *"Halo Rekan Proyek, berikut rekomendasi resmi untuk kebutuhan pengadaan [Nama Produk]: [URL ber-referal]"*.
- **Commission Simulator Widget:** Komponen visual Livewire pada dashboard freelance yang merender estimasi komisi dinamis: `project_value * (commission_rate / 100)`.

---

## 6. Modul Impor Massal Katalog Produk (`FR-BULK`)

### FR-BULK-01: Batch Chunking Excel Import
- **Implementasi:** Menggunakan `Maatwebsite\Excel` dengan antarmuka `WithChunkReading` dan `WithBatchInserts` (ukuran batch: 100 baris per iterasi) untuk memastikan konsumsi RAM tetap ringan di lingkungan Hostinger Premium Web Hosting.

### FR-BULK-02: Export Financial Sales & Commission Reports to Excel
- **Implementasi:** Menggunakan `Maatwebsite\Excel` via `FromQuery` dan `WithHeadings` untuk streaming ekspor dataset:
  1. **Rekapitulasi Penjualan & Prospek Bulanan:** Kolom `Inquiry Number`, `Customer Name`, `Company`, `Total DPP`, `Total PPN`, `Grand Total`, `Status`, `Sales PIC`, `UTM Source`.
  2. **Rekapitulasi Pembayaran Komisi Mitra Freelance:** Kolom `Partner Name`, `NIK`, `Bank Name`, `Account Number`, `Account Holder`, `Sales Order Number`, `Commission Amount`, `Payment Status` (`payable`/`paid`).

---

## 7. Modul Pemeliharaan Sistem & Otomasi Notifikasi (`FR-SYS` & `FR-NOT`)

### FR-SYS-01: Pencadangan Database Bulanan Otomatis
- **Jadwal:** Dijalankan via Laravel Scheduler setiap tanggal 1 pukul 01:00 dini hari (`0 1 1 * *`).
- **Format:** Dump database MySQL terkompresi `.sql.gz` (`backup-bimabergawa-YYYY-MM-01.sql.gz`), tersimpan aman di storage server dan dapat disinkronkan ke cloud backup offsite.

### FR-NOT-01: Sistem Peringatan & Eskalasi Multi-Kanal
- **Kanban Stale Lead Alert:** Peringatan visual berkedip merah jika ada prospek berstatus `new` yang belum dihubungi $> 2\text{ jam}$ kerja.
- **Eskalasi Direksi:** Pengiriman email notifikasi otomatis ke Direksi dan Head of Sales untuk prospek menggantung $> 2\text{ jam}$ dan rekap mingguan penawaran bernilai besar yang mendekati masa kedaluwarsa.


