# 05. Aturan Bisnis Baku, Metrik KPI & Manajemen Risiko
## Business Requirement Document (BRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-BRD-05` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Tim Business Analyst | Draf Awal | Standarisasi aturan penomoran dokumen otomatis (BR-NUM) & KPI |
| `1.1.0` | 03 Sep 2026 | Finance Controller | Ditinjau | Penambahan aturan termin pembayaran bertahap (BR-PAY-TERM) & komisi berjenjang |
| `1.2.0` | 05 Sep 2026 | Commercial Operations Lead | Disetujui | Penetapan toleransi keterlambatan prospek 2 jam (BR-SLA) & matriks risiko |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Aturan Bisnis Baku (Master Business Rules)

```
+----------------------------------------------------------------------------------------------------+
|                                    DAFTAR ATURAN BISNIS BAKU (BR)                                  |
+----------+----------------------------+--------------------------------------------------------+
| Kode BR  | Kategori                   | Pernyataan Aturan Bisnis Baku                          |
+----------+----------------------------+--------------------------------------------------------+
| BR-DOC-01| Penomoran Inquiry          | Nomor Inquiry berformat 'INQ-YYYYMM-XXXX' terurut      |
|          |                            | secara otomatis dan dijamin unik (concurrency-safe).   |
| BR-DOC-02| Penomoran Quotation        | Nomor Penawaran berformat 'QUO-YYYYMM-XXXX' dengan     |
|          |                            | pelacakan versi revisi (Rev 0, 1, 2) & PDF Watermark.  |
| BR-DOC-03| Penomoran Sales Order      | Nomor Sales Order berformat 'SO-YYYYMM-XXXX'.          |
| BR-DOC-04| Penomoran Delivery Order   | Nomor Surat Jalan 'DO-YYYYMM-XXXX' (bisa partial/split)|
|          |                            | khusus ekspedisi, 100% tanpa nominal harga.           |
| BR-PRC-01| Pajak Pertambahan Nilai    | PPN dihitung dari Dasar Pengenaan Pajak (DPP) dengan   |
|          |                            | persentase standar 11.00% (Subtotal - Diskon) * 11%.   |
| BR-PRC-02| Batas Wewenang Diskon & HPP| Diskon sales maks 10% & margin HPP > 15%. Diskon > 10% |
|          |                            | atau margin < 15% wajib approval Direksi.              |
| BR-PRC-03| Skema Pembayaran Fleksibel | Mendukung 100% Penuh (CBD/COD/TOP) & Multi-Termin      |
|          |                            | bertahap (Custom Milestones dengan total 100%).        |
| BR-PRC-04| Kerahasiaan Harga Publik   | 100% Katalog Publik TANPA menampilkan harga/nominal.   |
| BR-VAL-01| Masa Berlaku Penawaran     | Masa berlaku default surat penawaran adalah 14 hari    |
|          |                            | kalender sejak tanggal penerbitan.                     |
| BR-LCK-01| Penguncian Data (Locking)  | Dokumen Quotation yang telah dikonversi ke Sales Order |
|          |                            | berstatus terkunci permanen (tidak dapat diedit).      |
| BR-SEC-01| Privasi Kontak Klien       | Data kontak WhatsApp dan Email klien wajib diisolasi   |
|          |                            | dan tidak boleh dapat diakses oleh publik luar.        |
| BR-AGT-01| Screening Mitra Freelance  | Calon sales freelance luar wajib melalui screening     |
|          |                            | kelayakan (KTP valid, pengalaman B2B, kesesuaian       |
|          |                            | sektor industri) dan disetujui Super Admin.            |
| BR-AGT-02| Level Komisi Mandiri       | Super Admin bebas mengonfigurasi Single/Multi-Tier level|
|          |                            | komisi agen & wajib lampirkan bukti transfer payout.   |
| BR-AGT-03| Cookie Attribution Window  | Parameter ?ref= tersimpan 90 hari di cookie browser.   |
| BR-NOT-01| Auto-Reminder Multi-Kanal  | Peringatan Kanban & email eskalasi direksi untuk lead  |
|          |                            | > 2 jam, serta email pengingat quote ke prospek.       |
| BR-BCK-01| Backup Database Bulanan    | Pencadangan dump database terkompresi .sql.gz otomatis |
|          |                            | 1x per bulan (tanggal 1 pukul 01:00).                  |
| BR-AUD-01| Audit Log Finansial        | Setiap persetujuan diskon direksi, perubahan status    |
|          |                            | komisi (payable/paid), dan revisi harga wajib tercatat |
|          |                            | permanen dalam audit log (user, timestamp, old/new val)|
| BR-SEO-01| Dominasi SEO & Data Schema | Setiap halaman publik (Brand, Kategori, Produk,       |
|          |                            | Artikel) wajib menyematkan JSON-LD Schema terstandar,  |
|          |                            | Canonical URL, dan formula Title dengan Commercial     |
|          |                            | Search Intent ("Distributor Resmi [Brand] [SKU]").     |
+----------+----------------------------+--------------------------------------------------------+
```

---

## 2. Metrik Kinerja Bisnis (Strategic Business KPIs)

Untuk mengevaluasi keberhasilan adopsi sistem setelah implementasi, ditetapkan metrik KPI kuantitatif dan kualitatif berikut:

```
+─────────────────────────────────────────────────────────────────────────────+
|                         METRIK KEBERHASILAN BISNIS (KPI)                    |
+──────────────────────────+────────────────────────+─────────────────────────+
| Indikator Kinerja (KPI)  | Kondisi Awal (Baseline)| Target Setelah Sistem   |
+──────────────────────────+────────────────────────+─────────────────────────+
| Waktu Respons Lead Masuk | 4 – 12 Jam             | < 15 Menit (Escalated)  |
| Waktu Pembuatan Quote    | 30 – 60 Menit / Berkas | < 3 Menit (1-Click PDF) |
| Sentralisasi Data Prospek| 30% (Tercecer di WA)   | 100% Tercatat di CRM    |
| Transparansi ROI Iklan   | 0% (Tidak Terlacak)    | 100% Lead Ber-UTM Tag   |
| Tingkat Kesalahan Hitung | ~ 5% (Human Error)     | 0% (Otomatisasi Sistem) |
| Pertumbuhan Organik SEO  | Rendah (Hanya 10 Page) | Ribuan SKU Terindeks    |
| Dominasi Halaman 1 Google| 0 Kata Kunci Brand     | Masuk Top 5 SERP untuk  |
| (Commercial Keywords)    |                        | Brand Distributor & Part|
| Inbound RFQ Organik      | < 5 Permintaan/Bulan   | > 50 Permintaan/Bulan   |
+──────────────────────────+────────────────────────+─────────────────────────+
```

---

## 3. Analisis & Manajemen Risiko Bisnis (Risk Management)

| Potensi Risiko Bisnis | Tingkat Dampak | Probabilitas | Strategi Mitigasi Terencana |
|:---|:---:|:---:|:---|
| **Resistensi Adopsi Tim Sales:** Sales enggan beralih dari Excel ke sistem ERP baru. | **Tinggi** | Sedang | Merancang UI ERP Filament yang sangat intuitif, cepat, dan otomatis menghasilkan PDF ber-Kop Surat rapi yang langsung meringankan beban ketik sales. |
| **Beban Server Hostinger Premium Terlampaui:** Lonjakan traffic pengunjung katalog memperlambat website. | **Sedang** | Sedang | Menerapkan kompresi otomatis WebP, penyimpanan keranjang di *localStorage* klien, caching rute/konfigurasi Laravel, dan backup bulanan. |
| **Keterlambatan Konfirmasi Harga Prinsipal:** Fluktuasi kurs mata uang asing menyebabkan harga penawaran lama merugikan perusahaan. | **Tinggi** | Rendah | Memberlakukan klausul masa berlaku penawaran tegas (*Validity Period* 14 hari) dan monitoring Live Margin HPP pada setiap berkas PDF Quotation. |
| **Spam / Bot Menyerang Form RFQ:** Form penawaran publik dibanjiri data palsu oleh bot perayap. | **Sedang** | Rendah | Mengintegrasikan verifikasi anti-bot modern (Cloudflare Turnstile / reCAPTCHA v3), Honeypot, Time-Trap, dan rate-limiting IP. |
