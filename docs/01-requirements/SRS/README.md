# Software Requirements Specification (SRS) - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-IDX` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Systems Analyst & QA Lead | Draf Awal | Spesifikasi teknis standar IEEE 830 untuk antarmuka, FR, & NFR |
| `1.1.0` | 03 Sep 2026 | Lead Software Engineer | Ditinjau | Penyesuaian FR komersial (Term of Payment fleksibel & partial DO) |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Harmonisasi Hostinger Premium Web Hosting, Single MySQL DB, & Standarisasi Dokumen |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Pendahuluan & Lingkup Teknis (Technical Scope)

Dokumen **Software Requirements Specification (SRS)** ini merupakan spesifikasi teknis formal yang mentranslasikan seluruh kebutuhan bisnis dari **BRD** dan spesifikasi produk dari **PRD** menjadi aturan teknis (*technical rules*), batasan komputasi (*constraints*), pemetaan input-proses-output (*I/O mapping*), serta parameter pengujian (*testing parameters*) bagi tim software engineer dan QA.

### 1.1 Batasan Ekosistem & Infrastruktur (Hostinger Premium Web Hosting)
Sistem dirancang dengan arsitektur **Monolith Efisien (Laravel 11.x + Filament + MySQL 8.0)** yang dioptimalkan untuk lingkungan Hostinger Premium Web Hosting:
- **Alokasi Memori PHP:** 512 MB.
- **Batas Waktu Eksekusi Skrip:** 60–120 detik.
- **Background Task:** Eksekusi antrean via Laravel Scheduler Cron Job hPanel per menit (`queue:work --stop-when-empty --max-time=50`).
- **Penyimpanan Berkas (Inodes):** Diefisienkan via konversi otomatis seluruh upload foto ke format WebP (lebar maks 1200px, reduksi ukuran ~70%).
- **I/O Database:** Diefisienkan dengan pengelolaan keranjang penawaran di sisi browser klien (*localStorage*) dan backup database berkala 1x per bulan.

---

## 2. Struktur Dokumen Modular SRS

Dokumen SRS ini dipecah ke dalam 5 berkas teknis mendalam:

```
docs/01-requirements/SRS/
├── README.md                                         <-- [Dokumen Saat Ini] Lingkup Teknis & Standar IEEE
├── 01-system-interfaces-and-environment.md           <-- Antarmuka Pengguna, Hardware, Software, & Protokol Jaringan
├── 02-functional-requirements-catalog-and-compro.md  <-- Spesifikasi Fungsional Compro, Katalog, & Insights (FR-CAT)
├── 03-functional-requirements-rfq-and-telemetry.md   <-- Spesifikasi Fungsional Dual RFQ, Atribusi UTM, & Referal (FR-RFQ)
├── 04-functional-requirements-erp-commercial.md      <-- Spesifikasi Fungsional ERP Pipeline, DomPDF, & Komisi (FR-ERP)
└── 05-non-functional-requirements-and-constraints.md <-- NFR: Performa, Keamanan, Reliabilitas, & Enkripsi Data
```

---

## 3. Matriks Konvensi Penomoran Kebutuhan (Requirement ID Mapping)

| Prefix Kode | Modul Fungsional | Deskripsi Cakupan Teknis |
|:---|:---|:---|
| `FR-COMPRO` | Company Profile & Legal | Komponen Beranda, Profil Legalitas, Dokumen TnC & Privasi, Form Kontak, Hub Medsos. |
| `FR-CAT`    | Showcase Katalog B2B    | Hierarki Brand-Kategori, Live Search, Faceted Filter, Comparison Tool, 100% Tanpa Harga Publik. |
| `FR-INSIGHTS`| Wawasan & Berita (CMS) | Tab filter kategori, estimasi waktu baca, social sharing, related products. |
| `FR-SEO`     | B2B SEO Dominance Engine| JSON-LD Structured Data (Product/Breadcrumbs/Org/FAQ), Canonical Tagging, XML Sitemap. |
| `FR-RFQ`    | Dual-Track Ingestion    | WhatsApp Click-to-Chat, Single RFQ Modal, Bulk Cart, Bulk SKU Quick-Paste, Anti-Spam Shield. |
| `FR-TEL`    | Deep Telemetry Engine   | Penangkap parameter UTM, data perangkat, IP logging, dan cookie window 90 hari referal agen. |
| `FR-AUTH`   | Autentikasi Panel ERP   | Sistem Passwordless Email OTP Login 6-digit untuk staf internal dan mitra freelance sales. |
| `FR-ERP`    | CRM Pipeline & Leads    | Input prospek multi-sumber, Kanban board, smart client autofill, dan Spatie Activitylog. |
| `FR-QUOTE`  | Quotation & DomPDF      | Kalkulator DPP & PPN 11%, Margin HPP, Skema Pembayaran Fleksibel (100% / Multi-Termin), Dynamic PDF Watermark. |
| `FR-SO`     | Sales & Delivery Order  | Penerbitan SO, status locking, Partial Delivery Order PDF tanpa harga, integrasi tracking resi ekspedisi. |
| `FR-AGENT`  | Portal Freelance Sales  | Screening pendaftaran mitra, konfigurasi level komisi mandiri, isolasi data Global Scope, dan upload bukti transfer. |
| `FR-BULK`   | Import & Export Excel   | Batch chunking import 100 baris, streaming export Excel untuk laporan penjualan dan komisi mitra. |
| `FR-SYS`    | System Maintenance      | Pencadangan otomatis database MySQL terkompresi `.sql.gz` 1x per bulan. |
| `FR-NOT`    | Multi-Channel Reminder  | Peringatan visual Kanban, auto-email pengingat masa berlaku penawaran, dan email eskalasi ke Direksi. |
| `NFR-PERF`  | Kinerja & Kecepatan     | TTFB < 800ms, DomPDF < 2s, Bulk Import 1.000 SKU < 60s, RAM < 512MB di Hostinger Premium. |
| `NFR-SEC`   | Keamanan & Anti-Spam    | Bcrypt OTP, Honeypot, Time-trap, Turnstile, CSRF, Header noindex, Immutable Audit Log. |
