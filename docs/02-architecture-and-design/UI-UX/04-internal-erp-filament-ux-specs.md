# 04. Spesifikasi UX Panel Internal ERP Filament
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-04` |
| **Versi Dokumen** | 1.4.4 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | ERP UX Designer | Draf Awal | Desain navigasi Filament Admin, Kanban Pipeline, & Quotation Builder |
| `1.1.0` | 03 Sep 2026 | Commercial Systems Lead | Ditinjau | Penambahan UX kalkulator Live Gross Margin HPP & form pengiriman Surat Jalan parsial |
| `1.2.0` | 05 Sep 2026 | Principal Design Architect | Disetujui | Desain UX Portal Mandiri Agen Freelance (kartu komisi & unduh slip transfer bank) |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Pembersihan total seluruh karakter emoji ke Icon formal dan penyelarasan copy B2B |
| `1.4.0` | 06 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Spesifikasi Dark/Light Mode, preferensi personal pengguna, matriks RBAC System Settings, dan standar multimoda UI (Table/Grid/Kanban) |
| `1.4.1` | 06 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Spesifikasi UX drawer lonceng notifikasi ERP, unread badge, & format email transaksional |
| `1.4.2` | 06 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Spesifikasi UX widget diskusi dan linimasa catatan internal dokumen transaksi |
| `1.4.3` | 06 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Spesifikasi UX Mention `@User` popover, dual-layer visibility tab (`[Catatan Internal]` vs `[Pesan ke Agen]`), dan isolasi portal agen |
| `1.4.4` | 07 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Spesifikasi navigasi sidebar grup `KONTEN & WEBSITE CMS` (Artikel, Konten Web, & Legalitas), wireframe visual CMS, dan cache invalidation |

---

## 1. Arsitektur Navigasi & Struktur Menu ERP Berbasis Peran

Panel ERP diakses melalui `app.domain.com` dengan otentikasi aman (*Passwordless Email OTP*) dan pembagian navigasi berbasis peran (RBAC):

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [LOGO PBB ERP]  PT. PANCA BIMA BERGAWA   [Cari: Ctrl+K]  [Theme: Light/Dark]  [Notif: 2]  [Akun: Sales]│
├───────────────────────┬────────────────────────────────────────────────────────────────────────────────┤
│ MENU UTAMA:           │ KONTEN UTAMA MODUL ERP                                                         │
│                       │                                                                                │
│ [Icon: Dashboard]     │                                                                                │
│ [Icon: Kanban]        │                                                                                │
│ [Icon: Inquiries]     │                                                                                │
│ [Icon: Quotations]    │                                                                                │
│ [Icon: SalesOrders]   │                                                                                │
│ [Icon: DeliveryOrder] │                                                                                │
│ [Icon: Invoices]      │                                                                                │
│                       │                                                                                │
│ MANAJEMEN MASTER:     │                                                                                │
│ [Icon: Catalog]       │                                                                                │
│ [Icon: Brands]        │                                                                                │
│ [Icon: Customers]     │                                                                                │
│ [Icon: FreelanceAgent]│                                                                                │
│ [Icon: SystemSettings]│                                                                                │
└───────────────────────┴────────────────────────────────────────────────────────────────────────────────┘
```

- **Standar Ikon Filament:** Seluruh menu sidebar menggunakan pustaka `heroicon-o-*` (misal: `heroicon-o-chart-bar`, `heroicon-o-view-columns`, `heroicon-o-document-text`, `heroicon-o-truck`, `heroicon-o-banknotes`) tanpa elemen emoji.

---

## 2. Visual Kanban Pipeline & Peringatan Prospek Tertunda (Stale Lead Alert)

Papan Kanban interaktif untuk melacak dan memproses prospek masuk secara *real-time*:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PIPELINE PROSPEK & INQUIRY MASUK (Tampilan Kanban)                    [ + Tambah Prospek Manual ]       │
├─────────────────────┬─────────────────────┬─────────────────────┬───────────────────┬──────────────────┤
│ BARU MASUK (3)      │ DIANALISIS / HPP (2)│ PENAWARAN TERKIRIM(4│ PO DIDAPAT / WON(2│ TERKIRIM / DO (5)│
├─────────────────────┼─────────────────────┼─────────────────────┼───────────────────┼──────────────────┤
│ ┌─────────────────┐ │ ┌─────────────────┐ │ ┌─────────────────┐ │ ┌───────────────┐ │ ┌──────────────┐ │
│ │ [STALE >2 Jam]  │ │ │ PT. Jaya Abadi  │ │ │ PT. Semen Prima │ │ │ PT. Indofood  │ │ │ PT. Unilever │ │
│ │ PT. Maju Sentosa│ │ │ Ref: INQ-2026-04│ │ │ QUO-202609-0012 │ │ │ PO: 450012891 │ │ │ DO-202609-01 │ │
│ │ 2x Pompa Ebara  │ │ │ Margin: 18.5%   │ │ │ Rp 145.000.000  │ │ │ Rp 85.000.000 │ │ │ Dakota Cargo │ │
│ │ Sales: Belum Ada│ │ │ Sales: Budi H.  │ │ │ Jatuh Tempo: H-3│ │ │ TOP: Net 30   │ │ │ Resi: DKT881 │ │
│ │ [ Klaim Prospek]│ │ │ [ Buat Quote ]  │ │ │ [ Kirim WA / Re]│ │ │ [ Buat DO ]   │ │ │ [ Detail DO ]│ │
│ └─────────────────┘ │ └─────────────────┘ │ └─────────────────┘ │ └───────────────┘ │ └──────────────┘ │
│ ┌─────────────────┐ │                     │                     │                   │                  │
│ │ PT. Cipta Karya │ │                     │                     │                   │                  │
│ │ 4x Inverter ATV │ │                     │                     │                   │                  │
│ │ Masuk: 15 mnt   │ │                     │                     │                   │                  │
│ └─────────────────┘ │                     │                     │                   │                  │
└─────────────────────┴─────────────────────┴─────────────────────┴───────────────────┴──────────────────┘
```

- **Fitur Stale Lead Badge:** Kartu yang berada di kolom "Baru Masuk" selama $> 2\text{ jam}$ tanpa penetapan sales akan memancarkan badge amber terstruktur `[Badge: STALE > 2 JAM]` dan memicu notifikasi email ke Direksi.
- **Drag & Drop Fast Action:** Memindahkan kartu dari kolom *Baru Masuk* ke *Analisis HPP* otomatis menetapkan PIC sales yang sedang login.

---

## 3. Quotation Builder dengan Kalkulator Margin HPP & Termin Fleksibel

Formulir pembuatan Surat Penawaran Harga resmi dengan kalkulasi margin keuntungan kotor seketika:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PEMBUATAN SURAT PENAWARAN HARGA (QUOTATION BUILDER) - QUO-202609-0015                                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Pelanggan : [ PT. Maju Sentosa v ]  Kontak PIC : [ Budi Santoso (081234567890) ]                       │
│ No. Ref   : [ INQ-202609-0012    ]  Masa Berlaku: [ 14 Hari (s/d 19 Sep 2026)  ]                       │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ RINCIAN ITEM PRODUK (REPEATER LINE ITEMS):                                                             │
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ Item 1: Pompa Ebara FSHA 65-50-200 (Cast Iron, Flange ANSI 150)                                    │ │
│ │ Qty: [ 2 ] Unit | HPP Beli: Rp 18.000.000 | Harga Jual: [ Rp 22.500.000 ] | Total: Rp 45.000.000  │ │
│ │ [Margin Sehat: 20.0%] Margin Bersih: Rp 4.500.000 / Unit                                           │ │
│ ├────────────────────────────────────────────────────────────────────────────────────────────────────┤ │
│ │ Item 2: Inverter Schneider ATV320U55N4B 5.5 kW                                                     │ │
│ │ Qty: [ 4 ] Unit | HPP Beli: Rp 8.200.000  | Harga Jual: [ Rp 9.300.000  ] | Total: Rp 37.200.000  │ │
│ │ [Margin Waspada: 11.8%] Margin Bersih: Rp 1.100.000 / Unit                                         │ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│ [ + Tambah Baris SKU Produk ]                                                                          │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ SKEMA PEMBAYARAN FLEKSIBEL (TERMS OF PAYMENT):                                                         │
│ Tipe Termin: ( ) 100% Penuh (CBD/COD/Net 30)      (x) Multi-Termin Bertahap (Milestone)                │
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ Termin 1: [ DP 30% Saat PO Diterbitkan    ] -> Bobot: [ 30 ] % -> Nominal: Rp 24.660.000           │ │
│ │ Termin 2: [ 50% Saat Barang Siap Kirim    ] -> Bobot: [ 50 ] % -> Nominal: Rp 41.100.000           │ │
│ │ Termin 3: [ Pelunasan 20% Setelah Diterima] -> Bobot: [ 20 ] % -> Nominal: Rp 16.440.000           │ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│ Total Bobot Termin: 100% (Valid) | Total Nilai Penawaran: Rp 82.200.000 (Belum Termasuk PPN 11%)       │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ Simpan Draf ]    [ Preview PDF Ber-Watermark ]    [ Terbitkan & Kirim PDF Resmi ke Klien ]           │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- **Keamanan HPP:** Kolom HPP dan Gross Margin hanya dapat dilihat oleh Sales Internal dan Direksi; tidak pernah terlihat oleh klien atau mitra freelance.
- **DomPDF Watermark:** Status *DRAFT* otomatis menghasilkan stempel watermark merah miring `DRAFT - NOT OFFICIAL`.

---

## 4. Pembuatan Surat Jalan Parsial & Integrasi Ekspedisi (Partial DO Form)

Formulir penerbitan Surat Jalan pengiriman fisik barang tanpa memuat nominal harga:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PENERBITAN SURAT JALAN / DELIVERY ORDER (DO-202609-0018/01) - PARSIAL                                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Nomor Sales Order: [ SO-202609-0010 (PT. Maju Sentosa) v ]  Tanggal Pengiriman: [ 05 Sep 2026 ]        │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ PILIH BARANG & JUMLAH PENGIRIMAN:                                                                      │
│ [x] Pompa Ebara FSHA 65-50-200 : Total Order: 2 Unit | Sisa: 2 Unit | Kirim Sekarang: [ 2 ] Unit      │
│ [ ] Inverter Schneider ATV320  : Total Order: 4 Unit | Sisa: 4 Unit | Kirim Sekarang: [ 0 ] Unit (Nanti)│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ DETAIL LOGISTIK & EKSPEDISI PIHAK KETIGA:                                                              │
│ Nama Ekspedisi / Kurir : [ Dakota Cargo                       ]                                        │
│ Nomor Resi / AWB       : [ DKT-998822110                      ]                                        │
│ Tautan Pelacakan Resi  : [ https://dakotacargo.co.id/track?no=DKT-998822110                          ] │
│ Alamat Tujuan Bongkar  : [ Gudang Workshop PT. Maju Sentosa, Kawasan KIIC Lot C-4, Karawang          ] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ Cetak Surat Jalan PDF (100% Tanpa Harga) ]              [ Konfirmasi Barang Telah Diberangkatkan ]   │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Antarmuka Mandiri Portal Agen Freelance (`app.domain.com/mitra`)

Dirancang dengan *Eloquent Tenant Scope* terisolasi agar agen luar hanya melihat data prospek mereka sendiri:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PORTAL KEMITRAAN FREELANCE SALES - PT. PANCA BIMA BERGAWA                     [Akun: Mitra Level 2]    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ RINGKASAN PERFORMA & KOMISI ANDA BULAN INI:                                                            │
│ ┌──────────────────────────┐ ┌──────────────────────────┐ ┌──────────────────────────────────────────┐ │
│ │ Total Omset Terbuku      │ │ Komisi Menunggu Bayar    │ │ Total Komisi Telah Ditransfer          │ │
│ │ Rp 320.000.000           │ │ Rp 4.500.000 (1 Prospek) │ │ Rp 12.800.000 (3 Transaksi)            │ │
│ └──────────────────────────┘ └──────────────────────────┘ └──────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ AKSI CEPAT:                                                                                            │
│ [ + Input Prospek Baru ]            [ Buat Tautan Penawaran WhatsApp ]              [ Cek Skema Bonus ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ RIWAYAT PROSPEK & PENCAIRAN KOMISI:                                                                    │
│ | Tanggal | Nama Klien / Prospek | Status Pesanan | Nilai Transaksi | Nilai Komisi | Status Bayar | Bukti|│
│ | 01 Sep  | PT. Sawit Makmur     | Lunas (Selesai)| Rp 150.000.000  | Rp 4.500.000 | [DIBAYAR]    | [Unduh]│
│ | 28 Agu  | CV. Teknik Mandiri   | Lunas (Selesai)| Rp 80.000.000   | Rp 2.400.000 | [DIBAYAR]    | [Unduh]│
│ | 04 Sep  | PT. Beton Jaya       | Menunggu Bayar | Rp 150.000.000  | Rp 4.500.000 | [PROSES]     | [-]  │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- **Akses Berkas Bukti Transfer:** Agen dapat mengunduh salinan slip transfer bank resmi (`bukti_transfer.jpg/pdf`) begitu staf Finance menyelesaikan pencairan komisi.

---

## 6. Preferensi Personal Pengguna & Pengalih Tema Tampilan (Theme Switcher & Profile)

Setiap pengguna ERP internal dan mitra portal mandiri memiliki akses ke halaman **Profil & Preferensi Pribadi** (`/admin/profile` atau melalui dropdown menu akun pada header panel). Pengaturan ini bersifat independen per individu dan tidak mempengaruhi konfigurasi pengguna lain maupun sistem perusahaan.

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PENGATURAN PROFIL PENGGANA & PREFERENSI TAMPILAN                                   [Simpan Perubahan] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ PREFERENSI TEMA ANTARMUKA:                                                                             │
│ Mode Tampilan: [ ( ) Terang (Light)    ( ) Gelap (Dark)    (*) Otomatis Ikuti Sistem OS ]              │
│ Catatan: Beralih ke Dark Mode direkomendasikan untuk mengurangi kelelahan mata saat bekerja malam hari.│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ INFORMASI AKUN & IDENTITAS:                                                                            │
│ Nama Lengkap           : [ Ahmad Fauzi, S.T.                                  ]                        │
│ Alamat Email Resmi     : [ ahmad.fauzi@bimabergawa.com                        ] [Ubah Email - Butuh OTP]│
│ Nomor Telepon / WA     : [ 081288990011                                       ]                        │
│ Foto Profil (Avatar)   : [ Unggah Foto Baru... ] (Maksimal 2 MB, Format WebP/JPG/PNG)                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ SESI LOGIN AKTIF & KEAMANAN:                                                                           │
│ • Perangkat Saat Ini   : Chrome on Windows 11 (IP: 182.253.xx.xx) - [Sesi Aktif]                       │
│ • Terakhir Masuk       : 06 September 2026, 08:30 WIB (Otentikasi OTP Berhasil)                       │
│ [ Keluar dari Seluruh Perangkat Lain ]                                                                 │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### 6.1 Karakteristik Teknis Theme Switcher
- **Pustaka Bawaan Filament 3:** Memanfaatkan fitur `->darkMode(true)` pada Filament Panel Provider.
- **Penyimpanan Status:** Disimpan pada peramban lokal (`localStorage.theme = 'light' | 'dark' | 'system'`) untuk transisi tema instan tanpa jeda rendering server (*zero flash of unstyled content*).
- **Akses Cepat Header:** Selain di halaman profil, sakelar tema juga tersedia sebagai tombol cepat satu klik pada *topbar* header ERP di samping menu pencarian global (`Ctrl+K`).

---

## 7. Pengaturan Sistem Terpusat & Matriks Hak Akses Berbasis Peran (System Settings Hub)

Menu **Pengaturan Sistem** (`/admin/system-settings`) merupakan pusat kendali operasional, komersial, perpajakan, dan identitas korporat PT. Panca Bima Bergawa. Seluruh nilai disimpan dalam tabel `system_settings` dan dilindungi oleh otorisasi Spatie RBAC berbasis tab:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PUSAT PENGATURAN SISTEM PERUSAHAAN (PT. PANCA BIMA BERGAWA)                         [Simpan Parameter] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TAB NAVIGASI PENGATURAN:                                                                               │
│ [ Umum & Identitas ] [ Pajak & Margin ] [ Logistik & Gudang ] [ CRM & Sales ] [ Komisi ] [ Maintenance ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ KONTEN TAB: PAJAK & KOMERSIAL (Akses Terbatas: Super Admin & Direksi Finance)                          │
│                                                                                                        │
│ TARIF PAJAK PERTAMBAHAN NILAI (PPN):                                                                   │
│ Tarif PPN Aktif Saat Ini : [ 11.00 ] % (Mendukung pembaruan dinamis multi-periode UU HPP)             │
│ Keterangan Legal         : Menjadi snapshot otomatis (vat_rate_applied) pada setiap penerbitan quote.  │
│                                                                                                        │
│ PERLINDUNGAN MARGIN KOTOR (LIVE MARGIN HPP GUARD):                                                     │
│ Ambang Margin Kritis     : [ 8.00  ] % (Margin < 8% mengunci penerbitan dan wajib Approval Direktur)  │
│ Ambang Margin Tipis      : [ 15.00 ] % (Margin 8.00% - 14.99% memicu badge kuning peringatan tipis)    │
│ Batas Approval Nilai SO  : Rp [ 50.000.000 ] (Sales Order bernilai di atas batas ini wajib persetujuan)│
│                                                                                                        │
│ KETENTUAN TRANSAKSI:                                                                                   │
│ Masa Berlaku Penawaran   : [ 14 ] Hari Kalender                                                        │
│ Rekening Bank Resmi PT   : [ BCA: 001-234-5678 a.n. PT Panca Bima Bergawa, Mandiri: 124-00-998877 ]   │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### 7.1 Matriks Hak Akses Tab Pengaturan Sistem (Spatie RBAC)

| Kategori Tab Pengaturan | Parameter Kunci yang Dikelola | Super Admin | Finance | Sales PIC | Logistik | Mitra Agen |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **1. Umum & Identitas (`general`)** | Nama PT, Alamat Rasuna Said, Logo Kop PDF, Favicon, Format Tanggal, Timezone. | **[Penuh]** | [Lihat Saja] | [Lihat Saja] | [Lihat Saja] | [Dilarang] |
| **2. Pajak & Margin (`commercial_tax`)** | Tarif PPN Dinamis (11%/12%), Batas Margin Kritis (<8%) & Tipis (8-15%), Rekening Bank PT. | **[Penuh]** | **[Penuh]** | **[Dilarang]** | [Dilarang] | [Dilarang] |
| **3. Logistik & Gudang (`logistics`)** | Default Gudang Asal, Rekanan Ekspedisi Resmi (Dakota, Baraka, Kurir PT), Format Syarat DO. | **[Penuh]** | [Lihat Saja] | [Lihat Saja] | **[Penuh]** | [Dilarang] |
| **4. CRM & Sales (`crm_sales`)** | Ambang Jam Stale Leads (24 Jam), Template Pesan WhatsApp Penawaran Resmi. | **[Penuh]** | [Lihat Saja] | **[Penuh]** | [Dilarang] | [Dilarang] |
| **5. Komisi Kemitraan (`commission_tiers`)** | Ambang Batas Omset & Bonus Tier Agen, Batas Minimum Penarikan Saldo (Rp 500rb). | **[Penuh]** | **[Penuh]** | [Lihat Saja] | [Dilarang] | [Dilarang] |
| **6. Sistem & Maintenance (`maintenance`)** | Sakelar Pemeliharaan Web Publik, Kredensial SMTP OTP, WhatsApp Gateway API Key. | **[Penuh]** | [Dilarang] | [Dilarang] | [Dilarang] | [Dilarang] |

- **Keterangan Status Akses:**
  - `[Penuh]`: Pengguna dapat melihat dan mengubah (*read and write*) parameter pengaturan.
  - `[Lihat Saja]`: Pengguna dapat membaca nilai parameter tetapi tombol simpan dan form input terkunci (*read-only*).
  - `[Dilarang]`: Tab atau menu disembunyikan sepenuhnya dari antarmuka pengguna (*hidden & forbidden*).

---

## 8. Standar Interaktivitas Tampilan Multimoda & Pagination (Multi-View & Pagination)

Untuk mengoptimalkan efisiensi kerja staf internal, penyajian data di dalam panel ERP tidak dibatasi pada satu format kaku, melainkan menggunakan tiga moda tampilan yang disesuaikan dengan karakteristik informasi:

### 8.1 Standar Pagination Tabel Interaktif Mandiri
- **Kontrol Lokal Per Halaman:** Kontrol pagination melekat langsung pada bagian bawah setiap tabel data secara interaktif, bukan diatur terpusat di pengaturan sistem.
- **Pilihan Jumlah Baris:** Pengguna dapat memilih jumlah baris per halaman secara fleksibel sesuai ukuran monitor kerja:
  - `[ 10 Baris ]` : Ideal untuk layar laptop portabel 13-14 inci.
  - `[ 25 Baris ]` : Standar default ergonomis kerja harian.
  - `[ 50 Baris ]` : Rekomendasi untuk audit cepat transaksi massal.
  - `[ 100 Baris ]` : Pilihan maksimal untuk rekonsiliasi data bulanan.
- **Efisiensi Kueri SQL:** Pagination wajib menggunakan kueri basis data `LIMIT` dan `OFFSET` yang terindeks (dilarang memuat seluruh koleksi ke memori aplikasi).

### 8.2 Tiga Moda Tampilan Antarmuka ERP (Multi-View Standards)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ MODUL KATALOG PRODUK MASTER                           [ Mode Tampilan: (*) Grid/Card   ( ) Tabel Data ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TAMPILAN GRID / KARTU PRODUK (GRID/CARD VIEW):                                                         │
│ ┌───────────────────────────┐ ┌───────────────────────────┐ ┌───────────────────────────┐             │
│ │ [Foto WebP: Kontaktor]    │ │ [Foto WebP: MCB 3 Phase]  │ │ [Foto WebP: High-Bay LED] │             │
│ │ BRAND: SCHNEIDER ELECTRIC │ │ BRAND: ABB                │ │ BRAND: PHILIPS LIGHTING   │             │
│ │ SKU: LC1D25M7             │ │ SKU: S203-C16             │ │ SKU: BY698P-LED200        │             │
│ │ TeSys D Contactor 25A 220V│ │ MCB 3P 16A 6kA Curve C    │ │ GreenPerform Highbay 150W │             │
│ │ Stok: [Ready: 45 Unit]    │ │ Stok: [Ready: 120 Unit]   │ │ Stok: [Indent: 2-3 Minggu]│             │
│ │ HPP : Rp 480.000 (Internal│ │ HPP : Rp 310.000 (Internal│ │ HPP : Rp 1.450.000 (Intern│             │
│ └───────────────────────────┘ └───────────────────────────┘ └───────────────────────────┘             │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

1. **Moda Tabel Data (*Table View*):**
   - **Karakteristik:** Tampilan baris dan kolom padat informasi, mendukung sorting kolom, filter multi-kriteria, dan batch action.
   - **Penerapan Modul:** Modul transaksional finansial (*Quotations*, *Sales Orders*, *Surat Jalan / Delivery Orders*, *Invoicing*, dan *Commission Ledger*).
2. **Moda Grid / Kartu (*Grid/Card View*):**
   - **Karakteristik:** Tampilan visual kartu berbasis galeri dengan foto produk WebP terkompresi, lencana brand (*brand badge*), nomor SKU tebal, status ketersediaan (*Ready/Indent*), dan parameter ringkas.
   - **Penerapan Modul:** Modul Master Katalog Produk (`ProductResource`), memudahkan Sales mengenali fisik produk secara visual saat berkonsultasi dengan prinsipal atau klien.
3. **Moda Papan Kolom (*Kanban Board View*):**
   - **Karakteristik:** Papan visual alur kerja interaktif dengan kartu yang dapat digeser antar tahapan alur penjualan (*drag-and-drop cards*), dilengkapi indikator visual waktu tunda (*pulsing badge Stale Alert*).
   - **Penerapan Modul:** Modul CRM Prospek & Permintaan Penawaran (`InquiryResource`).

---

## 9. Spesifikasi Lonceng Notifikasi ERP & Email Transaksional (Notifications UX)

Untuk memastikan kelancaran alur kerja operasional dan kepatuhan SLA layanan B2B, panel ERP dilengkapi sistem notifikasi terpadu yang memadukan panel laci (*slide-over drawer*) pada antarmuka web dan surel transaksional (*transactional email alert*):

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PANEL DRAWER NOTIFIKASI ERP (SLIDE-OVER KANAN)                                             [Tutup (X)] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ NOTIFIKASI ANDA                                             [Tandai Semua Telah Dibaca] [Bersihkan]    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [!] BUTUH OTORISASI DIREKTUR                                                         10 Menit Lalu     │
│ Penawaran QUO-202609-0005 untuk PT. Maju Bersama memiliki Margin Kritis 5.2%.                         │
│ [Buka Dokumen Penawaran]                                                                               │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [i] INKUIRI BARU MASUK DARI WEB PUBLIK                                               45 Menit Lalu     │
│ Permintaan RFQ Cart dari CV. Sumber Teknik Mandiri (4 Item Produk Schneider).                          │
│ [Tinjau Inkuiri di Kanban]                                                                             │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [!] PERINGATAN SLA PROSPEK TERTUNDA                                                  2 Jam Lalu        │
│ Inkuiri INQ-202609-0012 telah melewati batas waktu respon awal 24 jam! Segera hubungi prospek.        │
│ [Ambil Tindakan Follow-Up]                                                                             │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [v] BARANG TELAH DIBERANGKATKAN                                                      Kemarin, 16:20    │
│ Surat Jalan DO-202609-0008 dikirim via Dakota Cargo (Resi: DKT-998822110).                            │
│ [Lacak Status Pengiriman]                                                                              │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### 9.1 Format Standar Email Transaksional Korporat
Surel resmi dikirim menggunakan driver SMTP korporat (`sales@bimabergawa.com`) dengan susunan tata letak standar ISO A4 responsif:
1. **Header Surat:** Menampilkan logo resmi PT. Panca Bima Bergawa dan kop alamat kantor pusat Gd. Wirausaha Rasuna Said.
2. **Badge Klasifikasi:** Label jenis pesan formal (contoh: `[Pemberitahuan Resmi]`, `[Eskalasi Direktur]`, atau `[Konfirmasi Pengiriman]`).
3. **Badan Informasi:** Rincian data transaksi (Nomor Dokumen, Nama Klien/Prospek, Daftar SKU Barang, Estimasi Nilai).
4. **Tombol Call-To-Action (CTA):** Tombol aksi tebal monokrom yang langsung mengarahkan staf atau klien ke halaman yang relevan di ERP melalui otentikasi aman OTP.
5. **Footer Legalitas:** Pernyataan kerahasiaan B2B dan informasi hak cipta PT. Panca Bima Bergawa.

### 9.2 Matriks 6 Skenario Notifikasi Utama

| Skenario Notifikasi | Pemicu (*Trigger*) | Sasaran Penerima | Saluran Media | Teks Ringkas Lonceng ERP | Subjek & Aksi Utama Email |
| :--- | :--- | :--- | :---: | :--- | :--- |
| **1. Inkuiri / RFQ Baru** | Submit RFQ Cart Drawer web publik | Sales PIC & Super Admin | In-App & Email | `[Inkuiri Baru] Masuk dari {Nama Klien} ({Jumlah} SKU)` | `[PBB-RFQ] Inkuiri Baru {No Inkuiri} - {Nama Klien}` -> Tombol Tinjau di ERP |
| **2. Peringatan SLA Stale** | Inkuiri status new $> 24\text{ jam}$ | Sales PIC & Manajer | In-App & Email | `[Peringatan SLA] {No Inkuiri} belum direspon!` | `[URGENT] Peringatan Batas SLA Respon Inkuiri {No Inkuiri}` -> Tombol Follow-Up |
| **3. Approval Margin Kritis** | Quote margin $< 8.00\%$ atau SO besar | Direktur / Super Admin | In-App & Email | `[Butuh Otorisasi] {No Quote} Margin Kritis {Margin}%` | `[APPROVAL] Permohonan Persetujuan Margin Kritis {No Quote}` -> Tombol Setujui / Tolak |
| **4. Sales Order Siap Kirim** | SO diverifikasi lunas oleh Finance | Tim Logistik / Gudang | In-App & Email | `[Instruksi Kirim] SO {No SO} siap diproses Surat Jalan` | `[LOGISTIK] Instruksi Penerbitan Surat Jalan Fisik {No SO}` -> Tombol Buat DO |
| **5. Surat Jalan Diberangkatkan** | Logistik menginput no resi ekspedisi | Sales PIC & Klien | In-App & Email | `[Barang Dikirim] DO {No DO} jalan via {Ekspedisi}` | `[PENGIRIMAN] Surat Jalan {No DO} Diberangkatkan (Resi: {Resi})` -> Tombol Lacak |
| **6. Pencairan Komisi Agen** | Finance mengunggah slip transfer | Mitra Agen Terkait | In-App & Email | `[Komisi Ditransfer] Dana Rp {Nominal} telah dikirim` | `[KOMISI] Bukti Transfer Komisi Transaksi {No SO}` -> Tombol Unduh Bukti |

---

## 10. Spesifikasi Panel Diskusi & Linimasa Catatan Dokumen (Contextual Discussion & Mention UX)

Untuk memastikan seluruh koordinasi bisnis, justifikasi persetujuan harga, instruksi logistik, serta klarifikasi teknis dengan mitra agen freelance terdokumentasi rapi tanpa kehilangan konteks transaksi, disematkan panel diskusi berbasis Livewire 3 di bagian bawah halaman detail transaksi:

### 10.1 Wireframe Panel Staf Internal (View Inquiry, Quotation, SO, DO)

Pada panel staf internal, modul inkuiri dilengkapi **Tab Visibilitas Ganda (Dual-Layer Visibility)** untuk memisahkan obrolan rahasia internal dari pesan yang dapat dibaca oleh mitra agen:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ LINIMASA DISKUSI & KOORDINASI TRANSAKSI (INQ-202609-0012 - PROYEK RS SILOAM)                           │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TAB VISIBILITAS:  [● CATATAN INTERNAL STAF (4)]    [  PESAN KE MITRA AGEN (2) ]                        │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ RIWAYAT PERCAKAPAN:                                                                                    │
│                                                                                                        │
│ [Avatar: AF] Ahmad Fauzi  [Sales PIC]                                                   3 Jam Lalu     │
│ Kategori: [Catatan Internal]                                                                           │
│ "Pak @bambang.wibowo, mohon izin diskon ekstra 2.5% untuk item ACB Masterpact MTZ. Agen freelance     │
│ Pak Hendra mengabarkan kompetitor menawarkan ABB dengan selisih tipis."                                │
│ [Lampiran: komparasi_harga_kompetitor.pdf (850 KB)]                                                    │
│                                                                                                        │
│ ────────────────────────────────────────────────────────────────────────────────────────────────────── │
│ [Avatar: BW] Bambang Wibowo  [Direktur]                                                 2 Jam Lalu     │
│ Kategori: [Approval Diskon]                                                                            │
│ "@ahmad.fauzi Disetujui diskon ekstra 2.5%, margin kita masih terjaga di 16.4%. Jangan lupa informasikan│
│ ke agen agar segera kunci PO minggu ini."                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ FORMULIR INPUT CATATAN BARU:                                                                           │
│ Tipe Catatan : (●) Catatan Internal Staf      ( ) Pesan ke Mitra Agen                                  │
│ Kategori     : [ Justifikasi Approval / Diskon v ]                                                     │
│ Pesan        : [ @b                                                                                  ] │
│                ┌──────────────────────────────────────────────────┐                                    │
│                │ AUTOCOMPLETE MENTION:                            │                                    │
│                │ > @bambang.wibowo  - Bambang Wibowo [Direktur]   │                                    │
│                │   @budi.santoso    - Budi Santoso [Finance]      │                                    │
│                │   @bayu.saputra    - Bayu Saputra [Logistik]     │                                    │
│                └──────────────────────────────────────────────────┘                                    │
│ Lampiran     : [ Pilih Berkas Pendukung... ] (PDF/JPG/PNG/WebP, Maksimal 5 MB)                         │
│                                                                            [ Kirim Catatan Internal ]  │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 10.2 Wireframe Tampilan Portal Agen Freelance (`/agent/inquiries/{id}`)

Mitra agen freelance **HANYA** memiliki akses diskusi pada modul Inkuiri miliknya sendiri. Tab catatan internal staf **100% dihilangkan** dari antarmuka agen guna menjamin **0% kebocoran modal HPP dan margin profit**:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ DISKUSI & KLARIFIKASI DENGAN TIM SALES PBB (INQ-202609-0012)                                          │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ PIC SALES ANDA: Ahmad Fauzi (WhatsApp: +62 812-9988-7766)                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ RIWAYAT PESAN DENGAN TIM PBB:                                                                          │
│                                                                                                        │
│ [Avatar: AF] Ahmad Fauzi  [Sales PIC PBB]                                               1 Hari Lalu    │
│ "Halo Pak Hendra, untuk inkuiri Proyek RS Siloam, mohon konfirmasi breaking capacity MCCB yang diminta │
│ apakah 36kA atau 50kA? Terima kasih."                                                                  │
│                                                                                                        │
│ ────────────────────────────────────────────────────────────────────────────────────────────────────── │
│ [Avatar: HN] Hendra N  [Mitra Agen Freelance]                                           18 Jam Lalu    │
│ "@ahmad.fauzi Klien konfirmasi butuh spesifikasi 50kA ya Pak Fauzi. Terlampir lembar single line      │
│ diagram dari konsultan perencana."                                                                     │
│ [Lampiran: sld_rs_siloam_rev2.pdf (2.4 MB)]                                                            │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ FORMULIR BALASAN KE TIM SALES:                                                                         │
│ Pesan Anda   : [ Ketik pesan klarifikasi teknis atau lampiran dokumen ke PIC Sales di sini...        ] │
│ Lampiran     : [ Pilih Dokumen / Gambar... ] (Maksimal 5 MB)                                           │
│                                                                               [ Kirim Pesan ke Sales ] │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 10.3 Aturan Interaksi & Keamanan Fitur Mention & Chat

1. **Mekanisme Mention Popover (`@` Trigger):**
   - Mengetik simbol `@` pada textarea memicu popover autocomplete berbasis Alpine.js.
   - Navigasi dropdown mendukung tombol panah keyboard (`Up`/`Down`) dan pemilihan cepat (`Enter` / `Click`).
   - Teks mention di-highlight dengan latar kontras lembut (contoh: `<span class="bg-zinc-100 text-zinc-900 font-semibold px-1 py-0.5 rounded">@Bambang Wibowo</span>`).
2. **Otomatisasi Notifikasi Multi-Kanal:**
   - Setiap pengguna yang di-mention otomatis menerima notifikasi in-app pada lonceng ERP (`[Notif: N]`).
   - Jika preferensi email pengguna aktif, sistem memicu pengiriman email notifikasi formal berisikan cuplikan pesan dan tombol langsung ke halaman dokumen transaksi.
3. **Pembatasan Hak Akses Mitra Agen (Strict Isolation):**
   - **Zero Access ke Quotation/SO/DO:** Mitra agen dilarang keras mengakses modul penawaran, sales order, dan surat jalan. Chat agen **eksklusif hanya ada pada modul Inkuiri**.
   - **Zero Visibility Catatan Internal:** Nilai kueri difilter ketat `WHERE type = 'agent_visible'` pada portal agen. Catatan HPP atau instruksi direksi tidak akan pernah terkirim ke browser agen.
   - **Restricted Mention Scope:** Mitra agen hanya diizinkan me-mention **Sales PIC yang ditugaskan** pada proyek tersebut. Autocomplete popover agen tidak akan menampilkan nama Direksi, Finance, atau staf lain.

---

## 11. Spesifikasi UX Manajemen Konten Website (CMS) & Halaman Legalitas

Modul **KONTEN & WEBSITE CMS** pada panel internal ERP Filament memungkinkan Super Admin dan staf yang berwenang untuk memperbarui seluruh copy teks, narasi visual, kontak resmi, dan dokumen hukum pada website publik (`bimabergawa.com`) secara dinamis tanpa intervensi teknis kode sumber atau deploy ulang.

---

### 11.1 Wireframe Halaman Pengaturan Konten Web (`/admin/website-content`)

Halaman kustom Filament Page (`ManageWebsiteContent`) menggunakan navigasi tab terstruktur untuk mengelompokkan pengaturan visual beranda dan profil perusahaan:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PENGATURAN KONTEN WEBSITE PUBLIK (bimabergawa.com)                                                     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ Tab: Beranda & Hero ]  [ Tab: Keunggulan PBB ]  [ Tab: Profil & Kantor ]  [ Tab: Kontak & Telemetri ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ PENGATURAN HERO SECTION (BERANDA):                                                                     │
│                                                                                                        │
│ Judul Utama Hero   : [ Distributor Resmi Elektrikal & Otomasi Industri Terpercaya                   ]  │
│ Sub-Judul Hero     : [ Menyediakan solusi distribusi Schneider Electric, Philips, ABB, dan Legrand  ]  │
│                      [ untuk kebutuhan proyek komersial, gedung, dan manufaktur di seluruh Indonesia. ]│
│ Teks Tombol CTA 1  : [ Lihat Katalog Produk               ]  Link CTA 1: [ /products                 ] │
│ Teks Tombol CTA 2  : [ Minta Penawaran Cepat (RFQ)        ]  Link CTA 2: [ /rfq                      ] │
│ Banner Gambar Hero : [ Gambar Terpasang: hero_banner_rasuna.webp (1600x600, 320 KB)                  ] │
│                      [ Ganti Gambar Banner... ] (Otomatis konversi WebP 82%, Max 5 MB)                 │
│                                                                                                        │
│ STATISTIK & PENCAPAIAN UTAMA (COUNTER STATS):                                                          │
│ Stat 1: Angka [ 4 ]      Label [ Prinsipal Global Resmi ]                                              │
│ Stat 2: Angka [ 2500+ ]  Label [ SKU Siap Kirim ]                                                      │
│ Stat 3: Angka [ 500+ ]   Label [ Klien Korporat & Proyek ]                                             │
│ Stat 4: Angka [ 100% ]   Label [ Keaslian Produk Terjamin ]                                            │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                      [ Batal ]  [ Simpan Perubahan Konten Beranda ]     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 11.2 Wireframe Halaman Pengaturan Halaman Legalitas (`/admin/legal-pages`)

Halaman kustom Filament Page (`ManageLegalPages`) menyediakan editor teks kaya (*Rich Editor*) untuk memelihara kepatuhan regulasi UU PDP No. 27/2022 dan tata kelola transaksi komersial B2B:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ PENGELOLAAN DOKUMEN HUKUM & KEBIJAKAN RESMI (LEGAL PAGES)                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ Tab: Syarat & Ketentuan ]  [ Tab: Kebijakan Privasi (UU PDP) ]  [ Tab: Kebijakan Garansi Resmi ]     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ KEBIJAKAN PRIVASI & PERLINDUNGAN DATA PRIBADI (UU PDP NO. 27/2022):                                    │
│                                                                                                        │
│ Tanggal Pembaruan  : [ 07 September 2026                 ]  Status Publikasi: [ (●) Aktif  ( ) Nonaktif│
│ Versi Regulasi     : [ Versi 1.3 - Kepatuhan Audit KUP & PDP 2026                                   ]  │
│                                                                                                        │
│ EDITOR KONTEN KEBIJAKAN PRIVASI:                                                                       │
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ [ B ] [ I ] [ U ] | [ H1 ] [ H2 ] [ H3 ] | [ List Bullets ] [ List Number ] | [ Link ] [ Code ]    │ │
│ ├────────────────────────────────────────────────────────────────────────────────────────────────────┤ │
│ │ 1. Komitmen Perlindungan Data Korporat dan Pribadi                                                 │ │
│ │ PT. Panca Bima Bergawa (PBB) berkomitmen penuh untuk melindungi seluruh data pribadi dan informasi │ │
│ │ korporat yang dikirimkan melalui formulir Request for Quotation (RFQ) maupun registrasi agen.      │ │
│ │                                                                                                    │ │
│ │ 2. Enkripsi dan Batasan Akses                                                                      │ │
│ │ Seluruh identitas sensitif (NIK KTP, NPWP, dan Nomor Rekening Bank) disimpan menggunakan enkripsi  │ │
│ │ simetris tingkat tinggi at-rest sesuai standar ISO/IEC 27001 dan UU PDP No. 27/2022.               │ │
│ │                                                                                                    │ │
│ │ 3. Hak Pemilik Data                                                                                │ │
│ │ Pengguna berhak meminta konfirmasi, pembaruan, maupun penghapusan data kontak setelah transaksi    │ │
│ │ komersial selesai dan melewati masa retensi audit pajak 10 tahun UU KUP.                           │ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                    [ Reset Default ]  [ Publikasikan Dokumen Legal ]   │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 11.3 Wireframe Manajemen Artikel & Blog Teknis (`/admin/articles`)

Manajemen artikel industri dan edukasi teknis menggunakan Filament Resource standar (`ArticleResource`):

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ARTIKEL & EDUKASI TEKNIS B2B                                                   [ + Tulis Artikel Baru ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ Filter: Kategori v ]  [ Filter: Status v ]  [ Filter: Penulis v ]                  [ Cari Artikel... ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ JUDUL ARTIKEL                | KATEGORI        | PENULIS        | STATUS     | DILIHAT | TANGGAL RILIS │
├──────────────────────────────┼─────────────────┼────────────────┼────────────┼─────────┼───────────────┤
│ Panduan Memilih MCCB vs ACB  │ Distribusi Daya │ Ahmad Fauzi    │ [Terbit]   │ 1,240   │ 05 Sep 2026   │
│ Standar Lighting Industri    │ Smart Lighting  │ Bambang Wibowo │ [Terbit]   │ 850     │ 02 Sep 2026   │
│ Proteksi Motor Listrik ABB   │ Otomasi & Drive │ Budi Santoso   │ [Draf]     │ -       │ 07 Sep 2026   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Menampilkan 1-3 dari 3 Artikel                                                    Halaman [ 1 ] dari 1 │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 11.4 Mekanisme Cache Invalidation Publik Seketika (Real-Time Invalidation)

Untuk menjaga performa tinggi shared hosting (LiteSpeed Web Server) sekaligus menjamin data web publik selalu akurat:
1. **Penyimpanan Key-Value Dinamis:** Konten CMS disimpan pada tabel `system_settings` dengan `group = 'cms_content'` atau `group = 'legal_page'`.
2. **Tag Caching Laravel:** Seluruh pembacaan konten pada domain publik (`bimabergawa.com`) dibungkus dalam `Cache::rememberForever('cms_settings_bundle', ...)` atau `Cache::tags(['public_website_content'])`.
3. **Invalidasi Otomatis Saat Simpan:** Setiap pemanggilan tombol `Simpan Perubahan Konten` atau `Publikasikan Dokumen Legal` pada panel Filament secara otomatis mengeksekusi:
   ```php
   Cache::forget('cms_settings_bundle');
   // atau jika mendukung cache tagging:
   Cache::tags(['public_website_content'])->flush();
   ```
4. **Respon Sub-Detik:** Pengunjung website publik langsung menerima teks, nomor telepon WhatsApp, atau banner terbaru pada muat ulang (*reload*) halaman berikutnya tanpa jeda tunda.

