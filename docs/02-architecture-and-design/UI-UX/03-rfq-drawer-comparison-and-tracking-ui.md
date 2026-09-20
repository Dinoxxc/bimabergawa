# 03. RFQ Drawer, Perbandingan Produk, & Pelacakan Pesanan UI
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-03` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | UI/UX Designer | Draf Awal | Desain Floating RFQ Drawer slide-over & form review RFQ |
| `1.1.0` | 03 Sep 2026 | Interaction Specialist | Ditinjau | Penambahan modal Bulk SKU Parser & halaman pelacakan status pengadaan |
| `1.2.0` | 05 Sep 2026 | Principal Design Architect | Disetujui | Redesain ke gaya Monochrome Minimalist & form onboarding mitra freelance |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Pembersihan total seluruh karakter emoji ke Icon formal dan penyelarasan copy B2B |
| `1.4.0` | 20 Sep 2026 | Security & UI/UX Architect | Disetujui | Penyesuaian antarmuka pelacakan publik Two-Key Verification (Nomor Dokumen + Kontak PIC), banner Signed URL email, & unduh PDF privat |

---

## 1. Floating RFQ Drawer (Slide-Over Samping Minimalis)

Drawer geser samping (*slide-over*) Alpine.js dengan latar belakang putih bersih, *hairline borders*, dan tombol aksi hitam pekat:

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ DAFTAR PERMINTAAN PENAWARAN (3 Item)                                  [Icon: X]  │
├──────────────────────────────────────────────────────────────────────────────────┤
│ 1. **Ebara Model FSHA 65-50-200**                                                │
│    Kategori: End Suction Pump                                                    │
│    Jumlah Unit : [ - ] [ 2 ] [ + ] Unit                                          │
│    Catatan Spek: [ Flange ANSI 150                                             ] │
│    [ [Icon: Trash] <u>Hapus Item</u> ]                                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│ 2. **Schneider ATV320U55N4B 5.5 kW**                                             │
│    Kategori: Inverter VFD                                                        │
│    Jumlah Unit : [ - ] [ 4 ] [ + ] Unit                                          │
│    Catatan Spek: [ Termasuk modul Modbus RTU                                   ] │
│    [ [Icon: Trash] <u>Hapus Item</u> ]                                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│ 3. **Sensor Omron E2E-X7D1-N**                                                   │
│    Kategori: Proximity Sensor                                                    │
│    Jumlah Unit : [ - ] [ 10 ] [ + ] Unit                                         │
│    Catatan Spek: [ Panjang kabel 5 Meter jika ada                              ] │
│    [ [Icon: Trash] <u>Hapus Item</u> ]                                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│ Punya daftar banyak part number?                                                 │
│ [ <u>TEMPEL DAFTAR PART NUMBER MASSAL (BULK SKU) -></u> ]                        │
├──────────────────────────────────────────────────────────────────────────────────┤
│ [ [Tombol Solid] LANJUTKAN FORMULIR PENAWARAN RESMI ]                            │
│ [ <u>[Icon: Phone] Kirim Daftar Cepat via WhatsApp (+62 812-XXXX-XXXX) -></u> ]   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Modal Input Cepat Part Number Massal (Bulk SKU Quick-Paste)

Kotak input bersih dengan latar belakang `#F4F4F5` dan tombol *Execute* hitam pekat:

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│ TEMPEL PART NUMBER MASSAL (BULK SKU PARSER)                           [Icon: X]  │
├──────────────────────────────────────────────────────────────────────────────────┤
│ Salin dan tempel daftar part number dari dokumen BOQ / Excel Anda:               │
│ Format: `[Part Number] , [Jumlah Unit] , [Catatan Spesifikasi (Opsional)]`       │
│                                                                                  │
│ ┌──────────────────────────────────────────────────────────────────────────────┐ │
│ │ FSHA 65-50-200, 2, Flange ANSI 150                                           │ │
│ │ ATV320U55N4B, 4, 380V                                                        │ │
│ │ FC-051P4K0, 1, IP20                                                          │ │
│ │ E2E-X7D1-N, 10, Kabel 5M                                                     │ │
│ └──────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                  │
│ [ [Tombol Solid] VALIDASI & CARI DI DATABASE KATALOG ]                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│ HASIL PARSING & VALIDASI (4 Item Terdeteksi):                                    │
│ • [Status: Valid] Ebara FSHA 65-50-200 — 2 Unit                                  │
│ • [Status: Valid] Schneider ATV320U55N4B — 4 Unit                                │
│ • [Status: Valid] Danfoss FC-051P4K0 — 1 Unit                                    │
│ • [Status: Terverifikasi Parsial] Omron E2E-X7D1-N — 10 Unit (Kabel standar 2M)  │
├──────────────────────────────────────────────────────────────────────────────────┤
│ [ [Tombol Solid] MASUKKAN KE KERANJANG RFQ ]            [ <u>Batal</u> ]         │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Formulir Pengajuan Penawaran Resmi (`/rfq`)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ # Pengajuan Penawaran Harga Resmi.                                                                     │
│ Lengkapi data perusahaan Anda untuk penerbitan Surat Penawaran Harga resmi ber-Kop Surat PBB.         │
├──────────────────────────────────────────┬─────────────────────────────────────────────────────────────┤
│ INFORMASI PERUSAHAAN (PIC):              │ RINGKASAN REKAPITULASI (3 SKU):                             │
│                                          │                                                             │
│ Nama Lengkap PIC  : [ Budi Santoso     ] │ 1. Ebara FSHA 65-50-200 (Qty: 2 Unit)                      │
│ Nama Perusahaan   : [ PT. Maju Sentosa ] │ 2. Schneider ATV320U55N4B (Qty: 4 Unit)                     │
│ NPWP Badan Usaha  : [ 01.234.567.8-012 ] │ 3. Sensor Omron E2E-X7D1-N (Qty: 10 Unit)                  │
│ Email Kantor PIC  : [ budi@majusentosa ] │                                                             │
│ Nomor WhatsApp    : [ 081234567890     ] │ Preferensi Pembayaran:                                      │
│ Alamat Proyek     : [ KIIC Karawang    ] │ [x] CBD 100% (Cash Before Delivery)                         │
│ Lampiran BOQ (PDF): [ Pilih Dokumen    ] │ [ ] Term of Payment (Net 30)                                │
│                                          │ [ ] Multi-Termin Bertahap (Milestone)                       │
│                                          │                                                             │
│                                          │ [ [Tombol Solid] AJUKAN PERMINTAAN PENAWARAN ]              │
└──────────────────────────────────────────┴─────────────────────────────────────────────────────────────┘
```

---

## 4. Matriks Perbandingan Spesifikasi Produk (`/compare`)

Tabel perbandingan minimalis dengan garis pemisah tipis dan tombol aksi monokrom:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ # Perbandingan Spesifikasi.                                       [ Toggle Tampilkan Beda Saja: ON ]  │
├──────────────────────┬────────────────────────┬────────────────────────┬───────────────────────────────┤
│ Parameter Teknis     │ Ebara FSHA 65-50-200   │ Ebara EVMS 15 4N5/4.0  │ Ebara DL-80 5.5kW             │
├──────────────────────┼────────────────────────┼────────────────────────┼───────────────────────────────┤
│ Gambar Produk        │ [ Foto Asli Produk ]   │ [ Foto Asli Produk ]   │ [ Foto Asli Produk ]          │
│ Kategori             │ Centrifugal End Suction│ Vertical Multistage    │ Submersible Sewage            │
│ Material Casing      │ Cast Iron (FC200)      │ Stainless Steel SS304  │ Cast Iron                     │
│ Kapasitas Aliran     │ **50 m³/jam**          │ **15 m³/jam**          │ **40 m³/jam**                 │
│ Total Head           │ **45 Meter**           │ **72 Meter**           │ **25 Meter**                  │
│ Daya Motor           │ **11 kW (15 HP)**      │ **4.0 kW (5.5 HP)**    │ **5.5 kW (7.5 HP)**           │
│ Ketersediaan         │ [Ready Stock]          │ [Inden 2 Minggu]       │ [Ready Stock]                 │
│ Aksi                 │ `[ + Tambah RFQ ]`     │ `[ + Tambah RFQ ]`     │ `[ + Tambah RFQ ]`            │
└──────────────────────┴────────────────────────┴────────────────────────┴───────────────────────────────┘
```

---

## 5. Halaman Pelacakan Ekspedisi & Verifikasi Mandiri (`/lacak-rfq`)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ # Pelacakan Status Pengadaan Mandiri.                                                                  │
│                                                                                                        │
│ [Info Banner]: Menerima email konfirmasi resmi PBB? Klik tautan di email Anda untuk akses instan.      │
│                                                                                                        │
│ 1. Nomor Dokumen Pengadaan : [ PBB/INQ/2026/09/0012                  ] (Nomor RFQ atau No. PO Klien)  │
│ 2. Kontak Verifikasi PIC   : [ 081234567890                          ] (Nomor WhatsApp atau Email PIC)│
│                                                                                                        │
│                                    [ [Tombol] VERIFIKASI & LACAK STATUS ]                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ DETAIL PESANAN: **PBB/INQ/2026/09/0012** (PT. Maju Sentosa - Proyek Karawang)                          │
│ PIC PEMOHON   : Ir. Hendra S***** (0812-****-7890)                                                     │
│                                                                                                        │
│ TAHAPAN PROSES:                                                                                        │
│ [x] Diajukan  --->  [x] Penawaran Terbit  --->  [x] PO Won  --->  [x] Dikirim Sebagian  --->  [ ] Lunas│
│                                                                                                        │
│ DOKUMEN RESMI & LOGISTIK:                                                                              │
│ • Dokumen Penawaran Resmi : [ <u>Unduh Berkas PDF Resmi (Tautan Terverifikasi) -></u> ]               │
│ • Surat Jalan Pengiriman  : **PBB/DOR/2026/09/0015/01** (2 Unit Ebara FSHA)                            │
│ • Ekspedisi & Nomor Resi  : **Dakota Cargo** — `DKT-998822110`                                         │
│ • Pelacakan Langsung      : [ <u>Buka Pelacakan Live di Website Dakota Cargo -></u> ]                  │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
