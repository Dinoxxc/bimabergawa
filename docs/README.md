# Master Dokumentasi Perencanaan Proyek IT
## B2B Multi-Brand Product Showcase, Company Profile & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MASTER-README` |
| **Status Dokumen** | Lengkap, Terstruktur & Terbarui (Tahap Perencanaan / Planning Phase) |
| **Target Infrastruktur** | Hostinger Premium Web Hosting |
| **Core Stack** | Laravel 11.x + Filament Admin + MySQL 8.0 + Blade / Alpine.js / Tailwind CSS |

---

## 1. Struktur Dokumen Perencanaan Standar Industri

Seluruh berkas dokumentasi telah dikelompokkan ke dalam 4 pilar dokumentasi wajib tahap perencanaan proyek IT:

```
docs/
├── README.md                                          # Master Index & Gambaran Umum
├── 01-requirements/                                   # Kebutuhan Produk & Sistem
│   ├── BRD/                                           # Suite Dokumen Kebutuhan Bisnis (01-05)
│   ├── PRD/                                           # Suite Spesifikasi Produk & User Stories (01-04)
│   └── SRS/                                           # Suite Spesifikasi Teknis IEEE 830 (01-05)
├── 02-architecture-and-design/                        # Arsitektur & Desain Sistem
│   ├── SAD/                                           # Suite Arsitektur Sistem & Topologi (01-05)
│   ├── SDD/                                           # Suite Desain Teknis & Skema DB (01-05)
│   └── UI-UX/                                         # Suite Desain Sistem & Wireframe (01-04)
├── 03-integration-and-deployment/                     # Integrasi & Implementasi
│   ├── API-Routing/                                   # Suite Spesifikasi Rute & Payload Data (01-04)
│   ├── CICD-Deployment/                               # Suite Pipeline CI/CD, Server Hostinger & Scheduler (01-04)
│   └── Data-Migration-Seeding/                        # Suite Pipeline Seeder, Master Brand/Kategori, & Import Excel (01-04)
└── 04-quality-and-security/                           # Kualitas & Keamanan
    ├── QA-Testing/                                    # Suite Matriks Uji, Konkurensi, UAT, & Benchmark (01-04)
    └── Security-Compliance/                           # Suite Kebijakan Keamanan, Subdomain, Upload, & UU PDP (01-04)
```

---

## 2. Ringkasan & Tautan Berkas Dokumentasi

### 2.1 Dokumen Kebutuhan Produk & Sistem (Requirements)
- [BRD (Business Requirement Document Suite)](file:///e:/Project/bimabergawa/docs/01-requirements/BRD/README.md): Kumpulan dokumen mendalam mencakup konteks bisnis distributor multi-brand, analisis pemangku kepentingan & RACI, audit masalah organisasi eksisting, kepatuhan hukum/pajak (UU HPP PPN 11%, UU PDP), alur proses As-Is vs To-Be, hingga aturan penomoran dan KPI strategis.
- [PRD (Product Requirement Document Suite)](file:///e:/Project/bimabergawa/docs/01-requirements/PRD/README.md): Kumpulan dokumen mendalam mencakup 6 persona pengguna (termasuk Freelance Sales), spesifikasi rinci fitur web publik & RFQ Cart, spesifikasi ERP Filament, 12 agile user stories (Gherkin), dan roadmap rilis MVP.
- [SRS (Software Requirements Specification Suite)](file:///e:/Project/bimabergawa/docs/01-requirements/SRS/README.md): Spesifikasi teknis standar IEEE 830 mencakup antarmuka sistem terisolasi, kebutuhan fungsional mendalam (FR-COMPRO, FR-CAT, FR-RFQ, FR-TEL, FR-ERP, FR-QUOTE, FR-SO, FR-AGENT), dan kebutuhan non-fungsional (NFR-PERF, NFR-SEC, NFR-REL).

---

### 2.2 Dokumen Arsitektur & Desain Sistem (Architecture & Design)
- [SAD (System Architecture Document Suite)](file:///e:/Project/bimabergawa/docs/02-architecture-and-design/SAD/README.md): Kumpulan dokumen cetak biru arsitektur mencakup topologi multi-domain `domain.com` vs `app.domain.com`, mitigasi Hostinger Premium Web Hosting, struktur lapisan komponen, keamanan RBAC & isolasi tenant, alur state machine komersial, serta otomasi backup bulanan & notifikasi.
- [SDD (System Technical Design Document Suite)](file:///e:/Project/bimabergawa/docs/02-architecture-and-design/SDD/README.md): Kumpulan dokumen spesifikasi teknis mendalam mencakup skema basis data MySQL 8.0 & kamus data, definisi model Eloquent & relasi, logika service layer (concurrency lock, live margin HPP, DomPDF, WebP), form schema Filament Admin, serta arsitektur state reaktif Alpine.js di web publik.
- [UI/UX (Design System & Wireframe Specification Suite)](file:///e:/Project/bimabergawa/docs/02-architecture-and-design/UI-UX/README.md): Kumpulan dokumen spesifikasi visual & pengalaman pengguna mencakup Design Tokens & Palet Monokrom Minimalis, Anti-AI Manifesto, Wireframe Web Publik (Beranda, Katalog tanpa harga, Detail SKU, Profil Kantor Rasuna Said), UI RFQ Drawer & Parser SKU Massal, serta UX Panel ERP Filament (Kanban Stale Alert, Quotation Builder Live Margin, Partial DO, & Portal Agen Freelance).

---

### 2.3 Dokumen Integrasi & Implementasi (Integration & Deployment)
- [API-Routing Suite](file:///e:/Project/bimabergawa/docs/03-integration-and-deployment/API-Routing/README.md): Kumpulan dokumen spesifikasi rute & API mendalam mencakup Rute Web Publik & Slugs, Skema JSON Payloads & Validasi, Rute Subdomain ERP Filament & Spatie RBAC, serta Artisan CLI Scheduler & HTTP Cron Handlers.
- [CICD-Deployment Suite](file:///e:/Project/bimabergawa/docs/03-integration-and-deployment/CICD-Deployment/README.md): Kumpulan dokumen otomasi deployment mencakup Pipeline GitHub Actions, Konfigurasi Server Hostinger PHP 8.3 & LiteSpeed `.htaccess`, Skrip `deploy.sh` & Prosedur Rollback, serta Setup Cron hPanel & Log Rotation.
- [Data-Migration-Seeding Suite](file:///e:/Project/bimabergawa/docs/03-integration-and-deployment/Data-Migration-Seeding/README.md): Kumpulan dokumen inisialisasi data master mencakup Pipeline Seeder & Spatie RBAC, Master Brand Prinsipal & Kategori Hierarkis, Engine Import Massal Excel (Chunking 100 baris), serta Manajemen Media WebP & Pemulihan Database.

---

### 2.4 Dokumen Kualitas & Keamanan (QA & Security)
- [QA-Testing Suite](file:///e:/Project/bimabergawa/docs/04-quality-and-security/QA-Testing/README.md): Kumpulan dokumen penjaminan mutu mencakup Matriks Uji Unit & Feature Pest/PHPUnit, Uji Konkurensi & Beban Race Condition Penomoran Dokumen, 12 Skenario UAT Bisnis End-to-End, serta Benchmark Performa Shared Hosting LiteSpeed.
- [Security-Compliance Suite](file:///e:/Project/bimabergawa/docs/04-quality-and-security/Security-Compliance/README.md): Kumpulan dokumen keamanan siber & kepatuhan regulasi mencakup Kebijakan Otentikasi Passwordless OTP & Spatie RBAC 5 Peran, Isolasi Subdomain `app.bimabergawa.com` & Anti-Scraping, Pengerasan Unggahan Berkas & Validasi MIME Magic Byte, serta Kepatuhan Privasi Data UU PDP No. 27/2022, UU HPP Perpajakan & Jejak Audit Spatie ActivityLog.
