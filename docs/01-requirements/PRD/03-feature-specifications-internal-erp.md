# 03. Spesifikasi Fitur Internal Commercial ERP
## Product Requirement Document (PRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-PRD-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | ERP Product Manager | Draf Awal | Spesifikasi alur Kanban, Quotation Builder, & Sales Order internal |
| `1.1.0` | 03 Sep 2026 | Head of Commercial | Ditinjau | Penambahan kalkulator Live Gross Margin HPP & termin fleksibel |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Spesifikasi isolasi Portal Agen Freelance & unggah bukti transfer bank |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Ikhtisar Panel Internal ERP (`app.domain.com`)

Panel internal dibangun menggunakan **Filament Admin** berbasis TALL Stack untuk menangani seluruh operasional komersial, pengelolaan master katalog, manajemen prospek (*CRM Leads*), penerbitan Surat Penawaran Resmi (*Quotation PDF*), hingga eksekusi Sales Order dan Surat Jalan.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          STRUKTUR MODUL FILAMENT ERP                        │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Dashboard Eksekutif & Sales Analytics                                    │
│ 2. Master Katalog Produk (Brand, Kategori Hierarki, Produk & Spesifikasi)   │
│ 3. Engine Import Massal Katalog via Excel/CSV                               │
│ 4. Manajemen Prospek & RFQ Pipeline (Kanban Board & Atribusi UTM)           │
│ 5. Quotation Builder (Perhitungan Harga, PPN, Diskon, & DomPDF Generator)   │
│ 6. Manajemen Sales Order & Penerbitan Surat Jalan (Delivery Order)          │
│ 7. CMS Artikel Wawasan & Inbox Pesan Kontak Umum                            │
│ 8. Manajemen Pengguna & Kontrol Hak Akses (Spatie RBAC)                     │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Rincian Fungsionalitas Modul ERP

### 2.1 Modul 1: Dashboard Eksekutif & Internal Telemetry Analytics
1. **Summary Metric Cards:** Total Inquiry Baru (Bulan Ini), Nilai Estimasi Pipeline Negosiasi (*Pipeline Deal Value*), Jumlah Penawaran Aktif, dan Tingkat Konversi Closing (%).
2. **Channel Attribution & Traffic Source Breakdown (Doughnut Chart):**
   - Menampilkan visualisasi persentase sumber asal prospek: *Google Ads (Search/Display)*, *Instagram/Facebook Ads*, *Organic Google Search*, *Direct WhatsApp*, *Walk-in / Phone Call*, dan *Freelance Referral*.
3. **Monthly RFQ Trend & Deal Velocity (Line/Bar Chart):**
   - Grafik tren volume permintaan penawaran masuk per minggu/bulan dan estimasi pergerakan nilai deal.
4. **Top 10 Inquired Products & Top Brands Widget (Data Table):**
   - Menampilkan 10 SKU produk dan merk prinsipal paling diminati beserta jumlah kuantiti dan konversi deal.
5. **Freelance Sales Leaderboard & Referral Metrics:**
   - Tabel performa mitra freelance sales: nama agen, jumlah lead yang dirujuk, closing deal, dan total komisi yang dihasilkan.
6. **Ekspor Laporan Penjualan & Prospek ke Excel (`.xlsx`):**
   - Tombol *"Export Data"* untuk mengunduh rekapitulasi data prospek, tren brand terlaris, dan omzet deal bulanan dalam format spreadsheet Excel (`.xlsx`) untuk kebutuhan rekonsiliasi akuntansi.
7. **Quick Actions:** Tombol input prospek manual (dari telepon/walk-in) dan filter rentang tanggal dinamis.


### 2.2 Modul 2: Master Katalog Produk (Product Engine)
1. **Brand Resource:** CRUD data brand/prinsipal, uploader logo WebP, saklar status *Official Distributor*, dan link website prinsipal.
2. **Category Resource:** CRUD kategori bertingkat (*Parent-Child Self-Referencing*), icon selector, dan pengaturan urutan (*sort order*).
3. **Product Resource:**
   - Form multi-tab rapi: *Informasi Utama*, *Spesifikasi Teknis (Repeater Key-Value)*, *Galeri Gambar*, *Brosur PDF*, dan *SEO Meta*.
   - **Hybrid SEO Auto-Fallback:** Kolom input `meta_description` dengan indikator batas 160 karakter; jika dikosongkan, sistem otomatis membuat deskripsi fallback terstandar.
   - **WebP Image Pipeline:** Kompresi otomatis setiap upload foto produk menjadi format WebP dengan batas lebar maksimum 1200px.
   - Filter tabel berdasarkan Brand, Kategori, Status Publish, dan Model Number.

### 2.3 Modul 3: Engine Import Massal Katalog via Excel/CSV
1. **Template Download:** Menyediakan tombol unduh file contoh template Excel (`.xlsx`) resmi.
2. **Batch Chunking Import:** Memproses ribuan data baris produk dalam batch 100 baris per iterasi via `Maatwebsite/Excel` agar tidak membebani memori 512MB shared hosting.
3. **Validasi & Error Reporting:** Menampilkan laporan baris mana yang gagal jika ada duplikasi slug atau salah format kolom.

### 2.4 Modul 4: Manajemen Prospek & RFQ Multi-Channel Pipeline
1. **Multi-Source Ingestion Engine (Online & Offline Channels):**
   - **Form Input Manual Prospek di ERP:** Tombol *"Tambah Prospek Baru"* yang memungkinkan sales menginputkan prospek langsung yang datang dari:
     - `website_rfq` (Single RFQ / Bulk Cart online)
     - `direct_whatsapp` (Pesan WhatsApp langsung ke nomor kantor/pribadi)
     - `walk_in_phone` (Panggilan telepon atau kunjungan kantor langsung)
     - `exhibition_offline` (Pameran dagang / kartu nama)
     - `freelance_referral` (Rujukan dari freelance sales)
   - **Smart Autofill Data Klien (UX Booster):** Saat sales mengetikkan nomor telepon atau nama perusahaan yang pernah bertransaksi sebelumnya, sistem otomatis menampilkan saran autocomplete untuk mengisi nama PIC, email, NPWP, dan alamat pabrik secara instan.
2. **Kanban Pipeline Board:** Papan visual drag-and-drop dengan 6 kolom status:
   $$\text{Inquiry Baru} \longrightarrow \text{Sudah Dihubungi} \longrightarrow \text{Draft Penawaran} \longrightarrow \text{Penawaran Terkirim} \longrightarrow \text{Deal / Won} \mid \text{Batal / Lost}$$
3. **Detail Profil Prospek & Deep Attribution Telemetry:**
   - Data Kontak PIC: Nama, Perusahaan, Nomor WhatsApp (tombol pintas buka WhatsApp Web langsung), Email, dan Lokasi Proyek.
   - Tabel Daftar Barang Permintaan: Foto thumbnail, nama barang, nomor model, dan kuantiti yang diajukan.
   - **Deep Attribution Telemetry:** Menampilkan parameter digital lengkap (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `referrer_url`, `device_type`, `landing_page`, `ip_address`) serta penanda **Kode Referal Freelance Sales** (`ref_code`).
4. **Timeline & Follow-Up Notes:** Kolom riwayat catatan aktivitas bagi tim sales untuk mendokumentasikan progres negosiasi (misal: *"Klien minta revisi diskon 5% untuk pembelian 20 unit"*).

### 2.5 Modul 5: Quotation Builder, Skema Finansial & DomPDF Generator
1. **1-Click Conversion dari RFQ:** Tombol *"Buat Penawaran"* otomatis menyalin seluruh daftar barang dari `inquiry_items` ke form draft penawaran.
2. **Kalkulator Komersial, Live Profit Margin & Skema Pembayaran Fleksibel:**
   - Input harga satuan (*Unit Price*), diskon per item (persen/nominal), dan diskon global (*discount_amount*).
   - Perhitungan otomatis Dasar Pengenaan Pajak (DPP) dan PPN 11% (`tax_amount = DPP * 0.11`).
   - **Internal Live Gross Profit Margin Calculator:** Menghitung estimasi margin laba internal berdasarkan `cost_price` (HPP prinsipal):
     $$\text{Margin \%} = \frac{\text{DPP} - \text{Total HPP}}{\text{DPP}} \times 100\%$$
     Jika margin $< 15\%$ atau diskon $> 10\%$, form mewajibkan *Approval Direksi* sebelum status dapat diubah menjadi *Approved/Sent*.
   - **Engine Skema Pembayaran Fleksibel (*Flexible Payment Terms Engine*):**
     - **Tipe A: Pembayaran Penuh 100% (*Full Payment*):**
       - *100% CBD (Cash Before Delivery):* Bayar lunas di awal sebelum barang diproses/di-indent.
       - *100% COD (Cash On Delivery):* Bayar lunas saat barang tiba di lokasi pembeli.
       - *100% TOP (Terms of Payment):* Tempo kredit (Net 14, Net 30, Net 60 Hari setelah invoice).
     - **Tipe B: Pembayaran Bertahap / Multi-Termin (*Custom Milestones*):**
       - Pilihan template preset (misal: *DP 50% + Pelunasan 50%* atau *DP 30% + 50% Kirim + 20% Retensi*) atau membuat baris termin bebas (*repeater*) dengan validasi akumulasi total wajib $= 100\%$.
   - **Pengaturan Komisi Freelance Sales:** Menautkan komisi agen sesuai tiering aktif atau custom nominal/persen.
   - Pilihan syarat penyerahan (*Delivery Terms*, misal: *"Franco Jakarta"* atau *"Loco Gudang"*).
3. **Version Control & Dynamic PDF Watermarking:**
   - Riwayat penomoran revisi penawaran (Rev 0, Rev 1, Rev 2) jika terjadi beberapa putaran negosiasi.
   - **Dynamic Watermark DomPDF:** Watermark diagonal otomatis: **"DRAFT - NOT OFFICIAL"** (saat draft), dokumen bersih ber-Kop Surat resmi (saat *Approved/Sent*), dan **"EXPIRED / VOID"** (saat kedaluwarsa).
4. **DomPDF Official Quotation Generator:** Merender berkas PDF resmi ber-Kop Surat perusahaan lengkap dengan nomor `QUO-YYYYMM-XXXX`, masa berlaku (14 hari), rincian spesifikasi barang, rekening bank resmi, skema termin pembayaran resmi, dan tanda tangan digital sales PIC.
5. **1-Click Kirim Penawaran via WhatsApp (UX Booster):** Tombol aksi *"Kirim Quotation ke WhatsApp Klien"* yang otomatis membuka WhatsApp Web dengan teks pengantar resmi terformat + tautan dokumen PDF penawaran resmi.
6. **Badge Peringatan Masa Berlaku Penawaran & Auto-Email Prospek:**
   - `[Badge: Hijau]` *(Sisa > 5 Hari)*, `[Badge: Kuning]` *(Sisa 1–3 Hari)*, `[Badge: Merah]` *(Kedaluwarsa)*.
   - Otomatis mengirim email pengingat santun ke prospek pada H-3 sebelum penawaran berakhir.


### 2.6 Modul 6: Manajemen Sales Order, Pengiriman Ekspedisi (Partial DO) & Rekap Komisi
1. **Konversi Deal ke Sales Order:** Saat status inquiry berubah menjadi `deal`, sales memasukkan nomor PO fisik klien (`customer_po_number`) dan sistem otomatis menerbitkan dokumen **Sales Order (`SO-YYYYMM-XXXX`)** serta mengunci record quotation (*read-only*).
2. **Pencatatan Status Komisi Freelance:** Menghasilkan record hak komisi bagi Freelance Sales dengan status pemantauan: `pending_payment` -> `payable` (saat klien lunas) -> `paid`.
3. **Pelacakan Status Pengiriman Bertahap & Ekspedisi Transparan (Partial DO):**
   - Mendukung pengiriman bertahap (*Partial Shipment*): 1 Sales Order dapat menerbitkan beberapa Surat Jalan (`DO-YYYYMM-XXXX/01`, `DO-YYYYMM-XXXX/02`) dan memantau sisa kuantiti barang yang belum terkirim.
   - Integrasi Logistik Pihak Ketiga: Input **Nama Jasa Ekspedisi** (`courier_name`), **Nomor Resi / Waybill / AWB** (`tracking_number`), dan **Tautan URL Cek Resi Resmi Ekspedisi** (`tracking_url`).
   - Informasi ekspedisi otomatis tersaji secara publik pada halaman `/lacak-rfq` klien.
4. **Cetak Surat Jalan Resmi Khusus Ekspedisi (Delivery Order PDF):**
   - Generator PDF Surat Jalan pengantar barang resmi yang memuat: Nama Ekspedisi, Nomor Resi/AWB, Estimasi Koli & Berat, Rincian Fisik Barang & Part Number, Alamat Gudang Tujuan & PIC Penerima, Instruksi Penanganan (*Handle with care*), serta Kolom Stempel/Tanda Terima Gudang.
   - **100% TANPA memuat kolom harga atau nominal rupiah** (aman dibawa pihak ekspedisi).
5. **Ekspor Laporan & Upload Bukti Transfer Komisi:**
   - Super Admin wajib mengunggah foto/scan bukti transfer bank saat menandai komisi menjadi `paid` (dapat dilihat langsung oleh agen terkait).
   - Fasilitas bagi bagian akuntansi untuk mengunduh rekapitulasi daftar komisi (*Payable* & *Paid*) dalam format Excel (`.xlsx`).

### 2.7 Modul 7: Portal Khusus Freelance Sales & Konfigurasi Level Komisi Mandiri

#### A. Panel Verifikasi Mitra & Pengaturan Level Komisi (Super Admin)
1. **Inbox & Filter Kriteria Calon Mitra:** Verifikasi Foto KTP, NIK 16 digit, kesesuaian Rekening Bank, sektor industri, dan pengalaman B2B pelamar dari form `/gabung-mitra`.
2. **Aksi Verifikasi & Approval:**
   - **Approve:** Mengaktifkan akun mitra, generate Kode Referal Unik (`REF-NAMA-XX`), dan kirim email sambutan.
   - **Reject / Request Revision:** Email profesional penolakan atau instruksi revisi KTP/data rekening.
3. **Konfigurasi Mandiri Level Komisi (*Customizable Tiered Commission Setting*):**
   - Super Admin dapat mengatur skema komisi secara fleksibel: **Single Level (Flat Rate)** atau **Multi-Level Tiered ($N$-Tingkat)**.
   - Setiap level dapat dikonfigurasi: *Nama Tier (Bronze/Silver/Gold), Target Akumulasi Omzet Closing (Rp), dan Persentase Komisi (%) / Nominal Tetap (Rp)*.
   - Dilengkapi fitur *Override Komisi Khusus* per transaksi penawaran.

#### B. Dashboard Khusus Freelance Sales & Isolasi Data Mandiri
Sistem menyediakan antarmuka khusus (*Freelance Agent Portal*) di dalam panel Filament ERP dengan aturan isolasi data ketat:
1. **Dashboard Khusus Freelance Sales:**
   - Ringkasan Metrik Pribadi: Total Prospek Saya, Penawaran Berjalan, Deal Sukses, Total Komisi (Pending vs Siap Cair), dan Tier Level Kemitraan aktif.
   - **Alat Bantu Promosi & WhatsApp Share Copywriting (UX Booster):** Fitur mencari produk katalog dan tombol 1-klik *"Bagikan ke WhatsApp"* yang langsung menyusun template teks rekomendasi produk ber-referal (`domain.com/catalog/pompa-ebara?ref=REF-DIMAS-88`) siap kirim ke kontraktor.
   - **Simulator Estimasi Komisi Visual (UX Booster):** Widget kalkulator interaktif untuk menghitung potensi komisi (Nilai Proyek × % Komisi) secara langsung di dashboard.
   - **Riwayat Pencairan Komisi & Bukti Transfer:** Mitra dapat melihat riwayat komisi dan mengunduh slip bukti transfer bank resmi dari kantor.
2. **Input Prospek Mandiri:**
   - Freelance sales dapat menginputkan data prospek yang dia temui langsung di lapangan (otomatis terikat ke akun miliknya tanpa bisa diubah ke agen lain).
3. **Pelacakan Status Progres Pesanan Real-Time:**
   - Memantau tahapan transaksi secara transparan: *Inquiry Diterima -> Penawaran Resmi Terbit (bisa lihat nomor Quotation) -> PO Disetujui (Deal) -> Barang Dikirim -> Status Komisi*.
4. **Isolasi Data Mutlak (Strict Data Isolation & Privacy):**
   - **Prinsip Zero-Cross Access:** Freelance Sales A **sama sekali tidak dapat melihat** data prospek, penawaran, nominal omzet, maupun komisi milik Freelance Sales B atau milik tim sales internal kantor.
   - Diimplementasikan melalui Laravel Eloquent Global Scope & Filament Resource Authorization Policy (`where('freelance_sales_id', auth()->user()->freelance_sales_id)`).

### 2.8 Modul 8: CMS Artikel & Inbox Pesan Kontak
1. **Article Resource:** Editor teks kaya (Rich Text), uploader cover image WebP, kontrol SEO meta, pengaturan kategori tab, dan penjadwalan rilis (*Publish Scheduler*).
2. **Contact Message Inbox:** Menampung seluruh pesan dari formulir kontak umum Compro dengan filter status (*Belum Dibaca*, *Sudah Dibaca*, *Sudah Dibalas*).

### 2.9 Modul 9: Manajemen Konten Website & Pengaturan Umum (Full Dynamic Site CMS)
Seluruh teks, gambar, dan elemen tampilan pada website publik dapat dikonfigurasi secara visual via panel Filament ERP tanpa menyentuh kode program:
1. **Pengaturan Beranda (Homepage CMS):**
   - Teks Judul Utama Hero (*Hero Title*), Tagline Subtitle, Gambar Banner/Background (WebP).
   - Teks & Tautan Tombol Call-to-Action (CTA).
   - Pengaturan 4 Kartu Pilar Keunggulan (*Why Choose Us*: Judul, Deskripsi Singkat, dan Icon).
2. **Pengaturan Halaman Profil (About Us CMS):**
   - Teks Narasi Sejarah & Profil Korporat.
   - Pernyataan Visi, Misi, dan Budaya Kerja Perusahaan.
   - Galeri Dokumen Legalitas & Sertifikat Distributor Resmi (Uploader Gambar/PDF, Nama Dokumen, Nomor Sertifikat).
3. **Pengaturan Kontak & Lokasi (Contact CMS):**
   - Alamat Kantor Pusat, Kantor Cabang, dan Gudang Operasional.
   - Jam Operasional Kantor (Hari Kerja & Jam Buka/Tutup).
   - Kode Iframe Google Maps Embed responsif.
   - Daftar Nomor Telepon Kantor, Email Resmi per Divisi, dan Nomor WhatsApp Direct Sales.
4. **Pengaturan Saluran Sosial Media (Social Media Hub CMS):**
   - Tautan URL akun resmi: Instagram, LinkedIn, YouTube, TikTok, Facebook, WhatsApp Business.
5. **Pengaturan SEO & Skrip Pelacakan (Marketing & SEO Settings):**
   - Global Meta Title & Meta Description default.
   - Google Tag Manager Container ID (`GTM-XXXXXXX`).
   - Google Analytics 4 Measurement ID (`G-XXXXXXXXXX`).
   - Kode Verifikasi Google Search Console (`google-site-verification`).
6. **Pengaturan Dokumen Legalitas & Kebijakan (Legal & Terms CMS):**
   - **Editor Syarat & Ketentuan (Terms & Conditions):** Rich text editor untuk klausul transaksi B2B, batas tanggung jawab, dan validitas harga.
   - **Editor Kebijakan Privasi (Privacy Policy):** Rich text editor klausul perlindungan data pengguna (UU PDP).
   - **Editor Ketentuan Garansi (Warranty Policy):** Rich text editor ketentuan klaim garansi & purna jual produk prinsipal.
   - **Editor Ketentuan Kemitraan Freelance (Partner Agreement TnC):** Rich text editor syarat komisi dan tata tertib agen freelance.
   - Tanggal pembaruan terakhir (*Last Updated Date*) yang otomatis terbit di halaman publik saat disimpan.

### 2.10 Modul 10: Manajemen Pengguna, Audit Trail & Matriks Hak Akses (RBAC)
1. **Matriks 4 Peran Pengguna (`spatie/laravel-permission`):**
   - **Super Admin / Direksi:** Akses penuh seluruh modul, data keuangan, approval diskon khusus, audit log finansial, pengaturan CMS website, dan rekap seluruh komisi agen.
   - **Sales Representative (Internal):** Akses prospek internal/terdisposisi, input manual, buat Quotation PDF, terbitkan SO/DO; akses katalog bersifat *View Only*.
   - **Freelance Sales (Portal Terisolasi):** Hanya dapat mengakses dashboard pribadinya: input prospek miliknya, generate link share referal produk, pantau status pesanan & komisi miliknya sendiri. Tidak dapat mengakses database master atau prospek sales lain.
   - **Content Marketing:** Akses penuh Master Produk, Import Excel, CMS Artikel, Pengaturan Konten Web (CMS Beranda & Compro), dan metrik traffic UTM; tidak dapat melihat nominal penawaran harga.
2. **Log Audit Aktivitas Finansial & Otorisasi (`spatie/laravel-activitylog`):**
   - Sistem otomatis mencatat log aktivitas tidak dapat diubah (*immutable log*) setiap kali terjadi:
     - Persetujuan/Penolakan diskon khusus oleh direksi.
     - Perubahan status pembayaran komisi freelance (*Pending -> Payable -> Paid*).
     - Penerbitan versi revisi surat penawaran harga (Quotation Rev 0, Rev 1, Rev 2).
     - Perubahan role atau status aktif akun pengguna.
   - Log memuat: *Nama Pengguna, Waktu (Timestamp), Alamat IP, Aksi, serta Perubahan Nilai (Old vs New Values)*.




