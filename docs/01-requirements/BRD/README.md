# Business Requirement Document (BRD) - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-BRD-IDX` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Tim Business Analyst | Draf Awal | Inisialisasi struktur kebutuhan bisnis & ruang lingkup B2B |
| `1.1.0` | 03 Sep 2026 | Project Director & Legal | Ditinjau | Penyesuaian identitas PT. PBB Rasuna Said, Term of Payment, & DO Ekspedisi |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Harmonisasi Single DB, Portal Agen Freelance Internal, & Standarisasi Template |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Ringkasan Eksekutif (Executive Summary)

Dokumen **Business Requirement Document (BRD)** ini merupakan acuan formal kebutuhan bisnis dan landasan operasional strategis untuk pengembangan sistem **B2B Multi-Brand Product Showcase, Company Profile & Internal Commercial ERP** milik **PT. Panca Bima Bergawa (PBB)**, beralamat di *Gd. Wirausaha lt.1 Unit 104, Jalan Haji R. Rasuna Said Kavling C5, RT.3/RW.1, Karet, Kecamatan Setiabudi, Jakarta Selatan 12920*.

Sistem ini dirancang khusus untuk mentransformasi proses bisnis PT. Panca Bima Bergawa dari distributor konvensional yang mengandalkan pencatatan manual/tersebar menjadi **entitas komersial digital terpadu berkecepatan tinggi**. Sistem melayani dua fungsi inti:
1. **Etalase Digital & Katalog Multi-Brand Terpusat (`domain.com`):** Menampilkan ribuan SKU dari berbagai prinsipal resmi terkemuka dengan fitur pencarian instan, spesifikasi dinamis, dan mekanisme penangkapan prospek ganda (*Dual-Track RFQ*).
2. **Mesin Komersial & Operasional Internal / ERP (`app.domain.com`):** Mengelola alur prospek (*Lead Pipeline*), penerbitan Surat Penawaran Resmi (*Quotation PDF*), pengawasan margin dan diskon, serta penerbitan Sales Order dan Surat Jalan (*Delivery Order*).

---

## 2. Struktur Dokumen Modular BRD

Untuk memastikan pembahasan yang mendalam, terstruktur, dan mudah ditinjau oleh masing-masing divisi terkait, BRD ini dipecah ke dalam 5 berkas spesifik:

```
docs/01-requirements/BRD/
├── README.md                                     <-- [Dokumen Saat Ini] Ringkasan Eksekutif & Ruang Lingkup
├── 01-business-context-and-stakeholders.md       <-- Profil Bisnis, Ekosistem B2B, & Matriks RACI
├── 02-organizational-problems-and-needs.md       <-- Analisis Masalah Organisasi & Kebutuhan Solusi
├── 03-regulations-and-compliance.md              <-- Regulasi Pajak PPN 11%, UU PDP, & Standar Hukum B2B
├── 04-business-process-workflows.md              <-- Alur Proses Bisnis As-Is vs To-Be & State Machine
└── 05-business-rules-and-kpi.md                 <-- Aturan Bisnis Baku, Metrik KPI, & Manajemen Risiko
```

---

## 3. Matriks Ruang Lingkup Bisnis (Scope Management)

### 3.1 In-Scope (Cakupan Pekerjaan Sistem)
- Pembangunan etalase produk ribuan SKU berbasis hierarki multi-level: **Brand Prinsipal -> Kategori -> Sub-Kategori -> Produk Detail** dengan **100% Tanpa Tampilan Harga** di publik (murni katalog RFQ B2B).
- Fasilitas pencarian langsung (*Live Search*), penyaringan multi-parameter (*Faceted Filtering*), *Side-by-Side Comparison Tool*, dan *Bulk SKU Quick-Paste / Fast BoQ Ingestion*.
- Mekanisme penangkapan permintaan penawaran harga ganda:
  - **Fast-Track:** Chat WhatsApp terformat otomatis dan Modal Pop-up Tanya 1 Produk.
  - **Bulk Procurement:** Keranjang penawaran (*RFQ Cart*) tersimpan di *localStorage* browser pengunjung (*Guest Mode*) dengan opsi unduh bundle datasheet PDF.
- Pelacakan atribusi sumber prospek digital secara otomatis via penangkap parameter UTM iklan (Google Ads, Meta Ads, LinkedIn) serta masa aktif atribusi cookie referal agen selama 90 hari.
- Back-Office ERP: Pipeline visual status prospek (Kanban Board dengan Stale Lead Alert), pembuatan Surat Penawaran resmi ber-Kop Surat (PDF DomPDF dengan dynamic watermark dan indikator live margin HPP), fleksibilitas skema pembayaran (100% CBD/COD/TOP maupun multi-termin kustom), serta konversi otomatis ke Sales Order dan Surat Jalan resmi (*Delivery Order* pengiriman bertahap/partial khusus ekspedisi tanpa nominal harga).
- Program Kemitraan & Portal Terisolasi Freelance Sales: Pendaftaran luar `/gabung-mitra`, screening kelayakan, pengaturan level komisi mandiri (*Single/Multi-Tier Configurable*), generator link referal (`?ref=...`), dan rekap pencairan komisi deal berlampiran bukti transfer bank.
- Full Dynamic Site CMS: Manajemen visual 100% konten Compro, hero banner, sertifikat, link medsos, dan dokumen legalitas/TnC/kebijakan privasi.
- Manajemen pesan masuk kontak umum Compro (`/contact`) dan sistem proteksi anti-spam berlapis (Honeypot, Turnstile, Rate Limit).
- Sistem Notifikasi & Auto-Reminder Multi-Kanal: Peringatan visual di Kanban, email otomatis pengingat masa berlaku penawaran ke prospek, dan email eskalasi ke Direksi.
- Pencadangan Database Terjadwal: Backup dump MySQL terkompresi `.sql.gz` otomatis 1x per bulan.

### 3.2 Out-of-Scope (Bukan Cakupan Sistem) & Justifikasi Bisnis
- **Payment Gateway / Checkout B2C Online:** Pembayaran langsung tidak diterapkan karena transaksi distributor B2B bernilai tinggi (*high-ticket items*) yang mensyaratkan negosiasi termin kredit (*Terms of Payment*) dan verifikasi legalitas PO resmi.
- **Pencatatan Saldo Stok Gudang Otomatis (Real-Time Physical Inventory Deduction):** Sistem tidak memotong kuantiti stok fisik gudang secara otomatis, melainkan mengelola status pemesanan (*Ready Stock vs Indent Principal*) agar operasional tetap gesit dan efisien di lingkungan Hostinger Premium Web Hosting.
- **Pembukuan Akuntansi Ganda (Double-Entry General Ledger):** Pencatatan jurnal debet-kredit, depresiasi aset, dan laporan keuangan komprehensif tetap dikelola oleh software akuntansi internal terpisah.

---

## 4. Matriks Persetujuan Dokumen (Sign-off Matrix)

| Peran Pemangku Kepentingan | Nama / Jabatan | Tanggal Persetujuan | Status |
|:---|:---|:---:|:---:|
| **Executive Sponsor** | Direktur Utama / Direksi | September 2026 | **APPROVED** |
| **Commercial Champion** | Kepala Divisi Sales & Marketing | September 2026 | **APPROVED** |
| **Technical Lead** | Lead Software Architect | September 2026 | **APPROVED** |
| **Operations Lead** | Manajer Operasional & Logistik | September 2026 | **APPROVED** |
