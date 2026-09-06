# 02. Analisis Masalah Organisasi & Kebutuhan Solusi
## Business Requirement Document (BRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-BRD-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Tim Business Analyst | Draf Awal | Audit kendala operasional eksisting & perumusan pohon masalah |
| `1.1.0` | 03 Sep 2026 | Head of Sales & Warehouse | Ditinjau | Penambahan dampak kebocoran HPP & keterlambatan respon RFQ manual |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Pemetaan solusi ERP terpadu & integrasi monitoring margin laba |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Analisis Masalah Organisasi Eksisting (Current Pain Points)

Berdasarkan audit operasional internal terhadap alur kerja distributor konvensional, ditemukan 5 titik masalah kritis (*critical bottlenecks*) yang melatarbelakangi kebutuhan pengembangan sistem ini:

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        5 MASALAH UTAMA OPERASIONAL DISTRIBUTOR                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│ 1. Lead Leakage (Kebocoran Prospek & Ketiadaan Sentralisasi Database Klien)    │
│ 2. Siklus Pembuatan Penawaran Lambat & Rawan Salah Hitung (Quotation Bottleneck)│
│ 3. Diskoneksi antara Investasi Pemasaran & Konversi Penjualan (Attribution Gap) │
│ 4. Fragmentasi Informasi Katalog Produk & Brosur Teknis Prinsipal              │
│ 5. Kehilangan Riwayat Negosiasi & Duplikasi Penomoran Surat Penawaran Resmi     │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

### 1.1 Masalah 1: Lead Leakage & Desentralisasi Data Prospek
- **Kondisi Eksisting:** Permintaan harga dari calon klien masuk secara terpencar ke nomor WhatsApp pribadi masing-masing tenaga penjual (*sales rep*), email pribadi, atau kartu nama yang didapat saat pameran dagang.
- **Dampak Negatif:**
  - Perusahaan tidak memiliki kepemilikan terpusat atas database pelanggan.
  - Ketika seorang sales mengundurkan diri (*resign*), riwayat prospek dan kontak PIC perusahaan klien seringkali ikut hilang atau dibawa ke kompetitor.
  - Manajemen tidak dapat memantau apakah sebuah prospek ditindaklanjuti secara profesional dalam 1x24 jam atau dibiarkan dingin (*lead neglected*).

### 1.2 Masalah 2: Siklus Pembuatan Penawaran Harga Lambat & Manual
- **Kondisi Eksisting:** Setiap kali menerima daftar permintaan barang dari klien, sales harus membuka dokumen Word atau Excel, menyalin nama barang dan kode model satu per satu, menghitung diskon dan PPN 11% secara manual, lalu menyimpannya sebagai file PDF.
- **Dampak Negatif:**
  - Penyusunan satu berkas penawaran memerlukan waktu 30 hingga 60 menit.
  - Sering terjadi kesalahan ketik (*human error*) pada perkalian harga, persentase pajak, atau salah memasukkan nomor rekening perusahaan.
  - Calon pembeli B2B seringkali beralih ke distributor kompetitor yang mampu merespons penawaran harga dalam hitungan menit.

### 1.3 Masalah 3: Ketiadaan Atribusi Pemasaran (Marketing Attribution Gap)
- **Kondisi Eksisting:** Tim marketing mengalokasikan anggaran untuk iklan Google Search Ads, media sosial (Instagram/LinkedIn Ads), dan SEO. Namun, saat prospek menghubungi sales melalui WhatsApp atau form, sales tidak mencatat dari mana iklan/kampanye asal prospek tersebut.
- **Dampak Negatif:**
  - Manajemen tidak memiliki visibilitas *Return on Ad Spend* (ROAS) dan efektivitas channel pemasaran.
  - Anggaran promosi berisiko dialokasikan ke kanal yang menghasilkan banyak klik namun nol konversi transaksi.

### 1.4 Masalah 4: Fragmentasi Katalog & Brosur Teknis Prinsipal
- **Kondisi Eksisting:** Ribuan SKU produk tersebar di dalam puluhan berkas PDF katalog tebal milik masing-masing prinsipal (Ebara, Schneider, Danfoss). Berkas-berkas ini tersimpan di komputer lokal staf dan tidak dapat diakses secara mudah oleh publik.
- **Dampak Negatif:**
  - Calon pembeli korporat kesulitan mencari produk berdasarkan nomor part (*model number*) di Google.
  - Tim sales terus-menerus disibukkan oleh pertanyaan berulang seputar spesifikasi dasar, dimensi, dan ketersediaan lembar data (*datasheet*).

### 1.5 Masalah 5: Ketiadaan Version Control Penawaran
- **Kondisi Eksisting:** Proses negosiasi B2B seringkali melalui beberapa putaran tawar-menawar harga (Revisi 0, Revisi 1, Revisi 2). Penyimpanan file manual di komputer lokal sales menyebabkan dokumen versi lama tertukar dengan versi yang telah disepakati.
- **Dampak Negatif:** Risiko kerugian finansial akibat perusahaan mengirimkan barang dengan dasar harga revisi yang salah.

### 1.6 Masalah 6: Ketiadaan Dominasi Mesin Pencari (Search Engine Blindspot & Inbound Traffic Deficit)
- **Kondisi Eksisting:** Website perusahaan lama hanya berfungsi sebagai profil statis tanpa struktur data mesin pencari (Schema.org / JSON-LD), tanpa landing page spesifik per brand prinsipal, dan tidak memiliki strategi kata kunci komersial (*commercial search intent*).
- **Dampak Negatif:**
  - Saat calon pembeli B2B / manajer pengadaan mencari kata kunci distributor resmi (*"Distributor Resmi Ebara Indonesia"*, *"Supplier Inverter Schneider"*, atau nomor model part tertentu), website perusahaan tidak muncul di Halaman 1 Google.
  - Perusahaan kehilangan ratusan prospek korporat bernilai tinggi (*high-value inbound leads*) yang akhirnya jatuh ke tangan kompetitor atau penjual non-resmi di marketplace.

---

## 2. Pemetaan Kebutuhan Solusi Bisnis (Business Needs Mapping)

```
+----------------------------------------------------------------------------------------------------+
|                                MATRIKS MASALAH VS KEBUTUHAN SISTEM                                 |
+------------------------------+----------------------------------+----------------------------------+
| Masalah Organisasi           | Kebutuhan Solusi Fungsional      | Dampak & Manfaat Bisnis          |
+------------------------------+----------------------------------+----------------------------------+
| Lead Leakage                 | Sentralisasi Inbox RFQ & Kanban  | 100% prospek tersimpan aman di   |
|                              | Pipeline terintegrasi di ERP.    | database terpusat milik korporat.|
+------------------------------+----------------------------------+----------------------------------+
| Pembuatan Quote Lambat       | 1-Click Quotation PDF Builder    | Waktu penerbitan penawaran turun |
|                              | dengan kalkulator PPN/Diskon.    | dari 45 menit menjadi < 3 menit. |
+------------------------------+----------------------------------+----------------------------------+
| Attribution Gap              | Middleware penangkap parameter   | Transparansi data ROI kampanye   |
|                              | UTM otomatis (Source/Campaign).  | iklan hingga tingkat closing deal|
+------------------------------+----------------------------------+----------------------------------+
| Fragmentasi Katalog          | Showcase Katalog Multi-Brand     | Ribuan SKU terindeks di Google;  |
|                              | dengan Live Search & Spek dinamis| pembeli mandiri mengunduh brosur.|
+------------------------------+----------------------------------+----------------------------------+
| Penomoran & Revisi Manual    | Service generator nomor urut unik| Audit trail riwayat tawar-menawar|
|                              | dan pelacakan revisi (Rev 0, 1,2)| rapi dan aman dari manipulasi.   |
+------------------------------+----------------------------------+----------------------------------+
| Search Engine Blindspot      | Mesin Dominasi SEO B2B Terpadu   | Dominasi Halaman 1 Google untuk  |
| (Ketiadaan Traffic Organik)  | (JSON-LD Rich Snippets, Brand    | ribuan SKU & brand distributor,  |
|                              | Hub, Auto-Fallback SEO Intent).  | melipatgandakan inbound RFQ gratis|
+------------------------------+----------------------------------+----------------------------------+
```
