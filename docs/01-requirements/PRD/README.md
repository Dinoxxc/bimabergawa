# Product Requirement Document (PRD) - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-PRD-IDX` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Product Management Team | Draf Awal | Definisi visi produk, persona pengguna, dan fitur MVP |
| `1.1.0` | 03 Sep 2026 | Lead Product Manager | Ditinjau | Penambahan spesifikasi perbandingan spesifikasi & bulk SKU paste |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan arsitektur database tunggal & standarisasi kontrol revisi |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Visi Produk & Proposisi Nilai (Product Vision)

### 1.1 Visi Produk
Membangun platform digital terpadu berkecepatan tinggi yang memadukan **etalase katalog produk industri ribuan SKU dari berbagai prinsipal resmi** dengan **mesin operasional komersial (ERP/CRM)** yang memfasilitasi percepatan siklus penawaran harga (*Lead-to-Order*) tanpa friksi registrasi akun bagi pembeli korporat.

### 1.2 Proposisi Nilai bagi Pengguna (Value Proposition)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       PROPOSISI NILAI BERDASARKAN PENGGUNA                  │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Pembeli Korporat (B2B Procurement):                                      │
│    "Menemukan part number spesifik dalam 3 detik, mengumpulkan daftar       │
│     kebutuhan proyek (RFQ Cart) tanpa wajib registrasi, dan menerima berkas │
│     Surat Penawaran Resmi ber-Kop Surat dalam hitungan menit."              │
│                                                                             │
│ 2. Tim Sales & Komersial Internal:                                          │
│    "Menerima notifikasi prospek instan lengkap dengan jejak asal promosi,  │
│     menyusun penawaran harga resmi (Quotation PDF) dalam 1-klik, dan        │
│     menerbitkan Sales Order serta Surat Jalan tanpa entri data berulang."   │
│                                                                             │
│ 3. Tim Digital Marketing & Manajemen:                                       │
│    "100% SKU teroptimasi SEO otomatis, kemudahan impor ribuan data produk  │
│     via Excel, dan transparansi ROI kampanye iklan hingga closing deal."    │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Struktur Suite Dokumen PRD

Dokumen PRD ini disusun secara modular agar tim pengembang (*engineers*), desainer antarmuka (*UI/UX designers*), dan pemilik produk (*product owners*) dapat memahami kebutuhan fungsional secara komprehensif:

```
docs/01-requirements/PRD/
├── README.md                                         <-- [Dokumen Saat Ini] Visi Produk & Release Goals
├── 01-user-personas-and-scenarios.md                 <-- 6 Persona Pengguna Mendalam & User Journey Map (Termasuk Mitra Freelance)
├── 02-feature-specifications-public-web.md           <-- Spesifikasi Rinci 12 Modul Web Publik (Termasuk /lacak-rfq & Compare Tool)
├── 03-feature-specifications-internal-erp.md         <-- Spesifikasi Rinci 10 Modul ERP Filament (Termasuk 1-Click WA & Smart Autofill)
├── 04-user-stories-and-acceptance-criteria.md        <-- 20 Agile User Stories & Kriteria Penerimaan Lengkap (Gherkin Syntax)
└── 05-product-metrics-and-release-plan.md            <-- Metrik Keberhasilan Produk & Rencana Rilis MVP
```

---

## 3. Sasaran Rilis Versi 1.0.0 (Release Goals)

| Sasaran Rilis (MVP Goals) | Kriteria Keberhasilan |
|:---|:---|
| **Pencarian Katalog Cepat** | Pencarian ribuan SKU via Live Search merespons < 500ms di browser klien. |
| **Konversi Dual-Track RFQ** | Memfasilitasi permintaan penawaran instan (WhatsApp & Modal Single) serta pengadaan massal (RFQ Cart). |
| **Otomasi Dokumen Komersial** | Pembuatan berkas PDF Surat Penawaran Resmi ber-Kop Surat via DomPDF dalam waktu < 2 detik. |
| **Pelacakan Sumber Traffic** | Menangkap 100% data UTM Source, Medium, Campaign pada setiap prospek yang masuk. |
| **Efisiensi Server Shared Hosting** | Semua gambar terkompresi otomatis ke WebP; antrean queue berjalan lancar via scheduler cron per menit. |
