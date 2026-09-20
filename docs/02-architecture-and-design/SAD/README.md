# System Architecture Document (SAD) - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SAD-IDX` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Infrastructure & System Architect | Draf Awal | Blueprint topologi jaringan, arsitektur monolit, & isolasi subdomain |
| `1.1.0` | 03 Sep 2026 | Principal Cloud Architect | Ditinjau | Optimasi Hostinger Premium Web Hosting, LiteSpeed, & cron queue worker |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Harmonisasi Single Database MySQL 8.0 & otomasi backup bulanan `.sql.gz` |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Ringkasan Eksekutif Arsitektur (Executive Architectural Summary)

Dokumen **System Architecture Document (SAD)** ini merupakan cetak biru teknis arsitektur tingkat tinggi yang menjabarkan topologi jaringan, struktur monolit modular, mekanisme isolasi antarmuka, rekayasa efisiensi server, serta alur data operasional untuk sistem **B2B Multi-Brand Product Showcase, Company Profile & Internal Commercial ERP milik PT. Panca Bima Bergawa (PBB)** (Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Jakarta Selatan).

Sistem dibangun menggunakan framework **Laravel 11.x (PHP 8.2+)** dengan pendekatan **Modular Monolith** yang menyatukan dua ekosistem terisolasi dalam satu basis kode (*Single Codebase*):
1. **Public Web Portal (`domain.com`):** Etalase katalog multi-brand B2B **100% tanpa tampilan harga publik**, company profile dinamis, penangkapan prospek ganda (*Dual-Track RFQ*), *Side-by-Side Comparison Tool*, *Bulk SKU Quick-Paste*, pelacakan resi ekspedisi (`/lacak-rfq`), dan pendaftaran kemitraan freelance (`/gabung-mitra`).
2. **Internal Commercial ERP (`app.domain.com`):** Panel operasional back-office berbasis **Filament Admin (TALL Stack)** yang mengelola CRM pipeline, pembuatan *Official Quotation PDF* ber-watermark dengan *Live Gross Profit Margin* dan *Flexible Payment Terms Engine*, penerbitan *Partial Delivery Order* khusus ekspedisi tanpa nominal harga, portal mandiri agen freelance dengan *Customizable Tiered Commission*, serta pencadangan database bulanan otomatis dan eskalasi notifikasi multi-kanal.

---

## 2. Struktur Dokumen Modular SAD

Untuk memastikan setiap aspek arsitektur terdokumentasi secara mendalam, terstruktur, dan mudah ditinjau, modul SAD dipecah ke dalam 5 berkas spesifik:

```
docs/02-architecture-and-design/SAD/
├── README.md                                             # [Dokumen Saat Ini] Ringkasan Eksekutif & Master Index
├── 01-topology-and-infrastructure-environment.md         # Topologi Domain, Hostinger Premium LiteSpeed, & Mitigasi Resource
├── 02-application-structure-and-component-layers.md      # Struktur Direktori Kode, Service Layer, & Komponen Filament
├── 03-security-subdomain-isolation-and-auth.md           # Isolasi Subdomain, Anti-Robots, Passwordless OTP, & Multi-Tenant Scope
├── 04-commercial-state-machine-and-data-pipelines.md     # State Machine RFQ->Quote->SO->DO, Live Margin, & PDF Engine
└── 05-automation-cron-backup-and-notifications.md        # Scheduler Cron 50s, Backup Bulanan (.sql.gz), & Eskalasi Email
```

---

## 3. Matriks Gambaran Topologi & Arsitektur Sistem

```
+─────────────────────────────────────────────────────────────────────────────────────────────+
|                                  TOPOLOGI SISTEM TERPADU                                    |
+─────────────────────────────────────────────────────────────────────────────────────────────+
                                       [ DNS HOSTINGER ]
                                               │
                       ┌───────────────────────┴───────────────────────┐
                       ▼                                               ▼
       https://domain.com (Web Publik)                https://app.domain.com (Internal ERP)
 ┌───────────────────────────────────────────┐   ┌───────────────────────────────────────────┐
 │       PUBLIC SHOWCASE & RFQ PORTAL        │   │         INTERNAL COMMERCIAL ERP           │
 │                                           │   │                                           │
 │ • Blade Templating + Tailwind CSS         │   │ • Filament Admin v3/v5 (Livewire 3)       │
 │ • Alpine.js Reactive Interactivity        │   │ • Spatie Laravel Permission (RBAC)        │
 │ • LocalStorage RFQ Cart (Zero Server I/O) │   │ • Kanban CRM Pipeline & Stale Lead Alert  │
 │ • Side-by-Side Product Comparison Tool    │   │ • Quotation Builder + Live Margin HPP     │
 │ • Bulk SKU Quick-Paste (BoQ Ingestion)    │   │ • Flexible Payment Terms (100% / Termin)  │
 │ • Deep Telemetry (UTM, Ref Cookie 90 Hari)│   │ • DomPDF Generator + Dynamic Watermark    │
 │ • Multi-Layer Anti-Spam (Turnstile/Trap)  │   │ • Partial Delivery Order (DO) Ekspedisi   │
 │ • Tracking Resi Publik (/lacak-rfq)       │   │ • Portal Freelance (Global Tenant Scope)  │
 │ • Pendaftaran Mitra (/gabung-mitra)       │   │ • Customizable Tiered Commission Setting  │
 └─────────────────────┬─────────────────────┘   └─────────────────────┬─────────────────────┘
                       │                                               │
                       └───────────────────────┬───────────────────────┘
                                               ▼
                       ┌───────────────────────────────────────────────┐
                       │          CORE SERVICES & REPOSITORY           │
                       │                                               │
                       │ • DocumentNumberService (Concurrency Safe)    │
                       │ • PricingAndMarginCalculatorService           │
                       │ • DomPdfService (Watermarked Quotation & DO)  │
                       │ • TelemetryService (UTM & 90-Day Cookie)      │
                       │ • ImageOptimizationService (WebP Pipeline)    │
                       │ • ExcelBulkService (Chunking 100 Rows)        │
                       │ • NotificationEscalationService               │
                       └───────────────────────┬───────────────────────┘
                                               ▼
                       ┌───────────────────────────────────────────────┐
                       │              MYSQL 8.0 DATABASE               │
                       │        (InnoDB Engine, UTF8mb4, Atomik)       │
                       └───────────────────────────────────────────────┘
```

---

## 4. Matriks Keselarasan dengan Requirements Suite (BRD/PRD/SRS)

| Aspek Arsitektur | Kebutuhan Terkait (BRD / PRD / SRS) | Berkas Dokumentasi SAD |
|:---|:---|:---|
| **Topologi & Hosting** | NFR-PERF, Batasan Hostinger Premium | [01-topology-and-infrastructure-environment.md](01-topology-and-infrastructure-environment.md) |
| **Struktur Kode & Layanan** | FR-COMPRO, FR-CAT, FR-BULK | [02-application-structure-and-component-layers.md](02-application-structure-and-component-layers.md) |
| **Keamanan & Isolasi** | NFR-SEC, BR-SEC-01, FR-AUTH, FR-AGENT-01 | [03-security-subdomain-isolation-and-auth.md](03-security-subdomain-isolation-and-auth.md) |
| **Alur Komersial & Dokumen** | BR-PRC-01..04, FR-QUOTE, FR-SO | [04-commercial-state-machine-and-data-pipelines.md](04-commercial-state-machine-and-data-pipelines.md) |
| **Otomasi & Pemeliharaan** | BR-BCK-01, BR-NOT-01, FR-SYS, FR-NOT | [05-automation-cron-backup-and-notifications.md](05-automation-cron-backup-and-notifications.md) |
