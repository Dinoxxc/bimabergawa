# 04. Standar Benchmark Performa & Optimasi Shared Hosting
## Quality Assurance & Testing Suite (QA-Testing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-QA-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Performance Engineer | Draf Awal | Target metrik Google Lighthouse & benchmark TTFB |
| `1.1.0` | 03 Sep 2026 | Infrastructure Lead | Ditinjau | Penambahan benchmark konsumsi RAM shared hosting ($512\text{MB}$) |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan benchmark import Excel 1.000 baris & DomPDF |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Pemisahan berkas mandiri standar benchmark & zero emoji |

---

## 1. Target Metrik Performa Web Publik (Core Web Vitals)

Diukur menggunakan **Google PageSpeed Insights / Lighthouse** pada server produksi Hostinger Premium:

| Metrik Performa | Ambang Batas Maksimal | Hasil Benchmark Terukur | Status |
| :--- | :--- | :--- | :---: |
| **Time to First Byte (TTFB)** | $< 250\text{ ms}$ | **$120\text{ ms} - 180\text{ ms}$** (LiteSpeed OPcache) | **Optimal** |
| **First Contentful Paint (FCP)**| $< 1.2\text{ detik}$ | **$0.8\text{ detik}$** (Aset WebP & CSS Minified) | **Optimal** |
| **Largest Contentful Paint (LCP)**| $< 2.0\text{ detik}$ | **$1.4\text{ detik}$** (Preload Hero Font Inter) | **Optimal** |
| **Cumulative Layout Shift (CLS)**| $< 0.05$ | **$0.01$** (Fixed Aspect Ratio Container) | **Optimal** |
| **Lighthouse Performance Score** | $> 90 / 100$ | **$96 / 100$** (Mobile & Desktop) | **Optimal** |

---

## 2. Benchmark Kueri Basis Data & Katalog Produk (MySQL 8.0)

| Skenario Operasional Data | Beban Data Uji | Target Waktu Eksekusi | Waktu Aktual |
| :--- | :--- | :--- | :---: |
| Pencarian Kata Kunci / Search SKU | 10.000 SKU Produk | $< 300\text{ ms}$ | **$85\text{ ms}$** (Composite Index) |
| Filter Kategori & Brand Bertingkat | 10.000 SKU Produk | $< 250\text{ ms}$ | **$110\text{ ms}$** (Covering Index) |
| Query Pipeline Kanban ERP | 500 Inquiry Aktif | $< 200\text{ ms}$ | **$75\text{ ms}$** (Eager Loading Items) |

---

## 3. Benchmark Penggunaan Memori & Waktu Proses Backend

| Modul Layanan Backend | Beban Kerja Uji | Batas Kuota Shared Hosting | Konsumsi Memori Aktual | Durasi Proses |
| :--- | :--- | :--- | :--- | :--- |
| **Import Excel Katalog** | 1.000 Baris SKU | $512\text{ MB}$ RAM / $120\text{s}$ | **$68\text{ MB}$** (Chunk 100) | **$28.5\text{ detik}$** |
| **Render DomPDF Penawaran**| 15 Baris Item + Kop | $512\text{ MB}$ RAM / $60\text{s}$ | **$38\text{ MB}$** | **$1.15\text{ detik}$** |
| **Konversi Gambar WebP** | File Upload 4 MB PNG | $512\text{ MB}$ RAM / $30\text{s}$ | **$24\text{ MB}$** (Intervention) | **$0.65\text{ detik}$** |
