# 04. Spesifikasi UX Panel Internal ERP Filament
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-04` |
| **Versi Dokumen** | 1.4.0 |
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

