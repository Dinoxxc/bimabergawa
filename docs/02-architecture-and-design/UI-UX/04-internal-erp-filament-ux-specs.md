# 04. Spesifikasi UX Panel Internal ERP Filament
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | ERP UX Designer | Draf Awal | Desain navigasi Filament Admin, Kanban Pipeline, & Quotation Builder |
| `1.1.0` | 03 Sep 2026 | Commercial Systems Lead | Ditinjau | Penambahan UX kalkulator Live Gross Margin HPP & form pengiriman Surat Jalan parsial |
| `1.2.0` | 05 Sep 2026 | Principal Design Architect | Disetujui | Desain UX Portal Mandiri Agen Freelance (kartu komisi & unduh slip transfer bank) |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Pembersihan total seluruh karakter emoji ke Icon formal dan penyelarasan copy B2B |

---

## 1. Arsitektur Navigasi & Struktur Menu ERP Berbasis Peran

Panel ERP diakses melalui `app.domain.com` dengan otentikasi aman (*Passwordless Email OTP*) dan pembagian navigasi berbasis peran (RBAC):

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [LOGO PBB ERP]  PT. PANCA BIMA BERGAWA             [Cari: Ctrl+K]  [Notifikasi: 2]  [Akun: Sales PIC]  │
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
