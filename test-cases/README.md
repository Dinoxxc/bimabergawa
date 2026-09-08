# Test Case Suite & Traceability Framework - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-IDX` |
| **Versi Dokumen** | 1.5.2 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Lead | Draf Awal | Definisi kerangka kerja pengujian fungsional dasar |
| `1.1.0` | 03 Sep 2026 | Test Automation Engineer | Ditinjau | Penambahan matriks ketertelusuran dokumen acuan (Traceability) |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan skenario konkurensi race condition & isolasi tenant |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Restrukturisasi modular 5 domain pengujian berstandar Zero Emoji |
| `1.4.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan skenario uji notifikasi drawer, diskusi transaksi linimasa, autocomplete mention `@User`, dan chat isolasi multi-tenant agen |

---

## 1. Kerangka Kerja Pengujian & Standar Matriks

Test Case Suite ini dirancang untuk memastikan seluruh fungsionalitas, alur bisnis, aturan validasi, dan batas keamanan sistem digital PT. Panca Bima Bergawa telah teruji secara komprehensif.

### 1.1 Kategori Kasus Uji (Test Case Categories)

1. **Kasus Positif (Positive Case):** Memverifikasi bahwa sistem bekerja sesuai spesifikasi ketika diberikan masukan valid dan kondisi ideal (*Happy Path*).
2. **Kasus Negatif (Negative Case):** Memverifikasi bahwa sistem menangani kesalahan dengan tepat, menolak data tidak valid, dan menampilkan pesan kesalahan ramah pengguna (*Error Handling*).
3. **Kasus Batas Nilai (Boundary / Edge Case):** Menguji perilaku sistem pada batas nilai minimum, maksimum, nilai ekstrem, atau format input tak lazim.
4. **Kasus Konkurensi (Concurrency Case):** Menguji ketahanan sistem terhadap akses serentak (*race condition*) pada mutasi data kritis seperti penomoran dokumen transaksi.
5. **Kasus Keamanan (Security Case):** Memvalidasi pertahanan sistem terhadap serangan SQL Injection, Cross-Site Scripting (XSS), bypass otorisasi RBAC, eksploitasi unggahan berkas, dan *tenant data leakage*.

---

## 2. Struktur Modular Test Case Suite

Rangkaian pengujian diorganisasikan ke dalam 5 domain fungsional:

```
test-cases/
├── README.md                                          # [Dokumen Ini] Master Index & Kerangka Kerja Pengujian
│
├── 01-public-website/                                 # Domain 1: Public Web & RFQ Cart
│   ├── 01-company-profile-and-navigation.md           # [DOC-PBB-TC-PUB-01] Profil Perusahaan, Hero, Navbar, Brand Grid, Full Dynamic Site CMS, & Legal Pages
│   ├── 02-product-catalog-and-search.md               # [DOC-PBB-TC-PUB-02] Katalog Zero Price, Filter Brand/Kategori, Search SKU
│   ├── 03-rfq-cart-and-inquiry-submission.md          # [DOC-PBB-TC-PUB-03] RFQ Cart Alpine.js, Bulk Parser, Form Inkuiri
│   └── 04-whatsapp-direct-inquiry.md                  # [DOC-PBB-TC-PUB-04] Floating WA Button, Prefilled Text, Telemetri
│
├── 02-internal-erp-crm/                               # Domain 2: Internal Commercial ERP
│   ├── 01-authentication-and-passwordless-otp.md      # [DOC-PBB-TC-ERP-01] Passwordless OTP, Cooldown 60s, Throttle, Sesi
│   ├── 02-crm-inquiry-and-kanban-management.md        # [DOC-PBB-TC-ERP-02] Kanban CRM, SLA Aging Warning, PIC Assignment
│   ├── 03-quotation-builder-and-live-margin.md        # [DOC-PBB-TC-ERP-03] Multi-Item Quotation, Live Margin HPP, PPN 11%, PDF
│   ├── 04-sales-order-and-approval-workflow.md        # [DOC-PBB-TC-ERP-04] Konversi SO, Limit Approval Direksi, Lock Penomoran
│   └── 05-delivery-order-and-fulfillment.md           # [DOC-PBB-TC-ERP-05] Partial DO, Tracking Sisa Qty, Status Fulfilled
│   └── 06-commercial-invoicing-and-audit-trail.md     # [DOC-PBB-TC-ERP-06] Faktur Komersial INV, Tempo Kredit, Verifikasi Bayar, Audit Trail
│
├── 03-agent-commission-portal/                        # Domain 3: Portal Agen Freelance
│   ├── 01-agent-registration-and-profile.md           # [DOC-PBB-TC-AGN-01] Registrasi Agen, Upload KTP/NPWP, Enkripsi Rekening
│   ├── 02-agent-inquiry-and-tenant-isolation.md       # [DOC-PBB-TC-AGN-02] Input Inkuiri Agen, Scope Data Tenant (Global Scope)
│   └── 03-commission-calculation-and-payout.md        # [DOC-PBB-TC-AGN-03] Kalkulasi Komisi Otomatis, Approval Finance/Direksi
│
├── 04-catalog-and-inventory-management/               # Domain 4: Manajemen Master Katalog
│   ├── 01-brand-and-category-taxonomy.md              # [DOC-PBB-TC-CAT-01] Master Brand Prinsipal, Hierarki Kategori, Slug
│   ├── 02-product-master-and-webp-pipeline.md         # [DOC-PBB-TC-CAT-02] CRUD Produk, Konversi WebP Otomatis, EXIF Stripping
│   └── 03-excel-bulk-import-engine.md                 # [DOC-PBB-TC-CAT-03] Import Excel Chunking 100 Baris, Normalisasi SKU
│
├── 05-security-and-non-functional/                    # Domain 5: Keamanan & Non-Fungsional
│   ├── 01-subdomain-isolation-and-anti-scraping.md    # [DOC-PBB-TC-SEC-01] Isolasi Subdomain app, X-Robots-Tag, Rate Limiter
│   ├── 02-concurrency-and-race-condition.md           # [DOC-PBB-TC-SEC-02] Race Condition Penomoran Dokumen (Atomic Lock)
│   ├── 03-file-upload-hardening-and-sanitization.md   # [DOC-PBB-TC-SEC-03] Validasi Magic Byte, UUID Renaming, Larangan PHP
│   └── 04-data-privacy-and-audit-logging.md           # [DOC-PBB-TC-SEC-04] Kepatuhan UU PDP No. 27/2022, Spatie ActivityLog
│
└── 06-integration-and-infrastructure/                 # Domain 6: Integrasi, Pipeline Seeder, & DevOps
    ├── 01-database-seeders-and-migration-rollback.md  # [DOC-PBB-TC-INF-01] Seeder 5 Role Spatie, Brand Awal, Rollback Skema
    ├── 02-cron-scheduler-and-automated-backup.md      # [DOC-PBB-TC-INF-02] Artisan Scheduler, Cron SLA Stale, Backup DB Gzip
    └── 03-cicd-pipeline-and-deployment-rollback.md    # [DOC-PBB-TC-INF-03] GitHub Actions, deploy.sh Zero-Downtime, OPcache
```

---

## 3. Matriks Ketertelusuran Antar Dokumen (Requirement Traceability Matrix)

| ID Dokumen Test Case | Modul Sistem | Dokumen Acuan Spesifikasi | Target Kelas / Controller / Service |
| :--- | :--- | :--- | :--- |
| `DOC-PBB-TC-PUB-01` | Profil Perusahaan & Navigasi | `DOC-PBB-PRD-02`, `DOC-PBB-SRS-02`, `DOC-PBB-UIUX-02` | `App\Http\Controllers\HomeController` |
| `DOC-PBB-TC-PUB-02` | Katalog & Pencarian SKU | `DOC-PBB-PRD-02`, `DOC-PBB-SRS-02`, `DOC-PBB-SDD-05` | `App\Http\Controllers\CatalogController` |
| `DOC-PBB-TC-PUB-03` | RFQ Cart & Form Inkuiri | `DOC-PBB-PRD-02`, `DOC-PBB-SRS-03`, `DOC-PBB-SDD-05` | `App\Http\Controllers\PublicInquiryController`, `App\Services\SkuParserService` |
| `DOC-PBB-TC-PUB-04` | Direct WhatsApp Inkuiri | `DOC-PBB-PRD-02`, `DOC-PBB-SRS-02`, `DOC-PBB-API-01` | `App\Http\Controllers\WhatsAppRedirectController` |
| `DOC-PBB-TC-ERP-01` | Otentikasi & Passwordless OTP | `DOC-PBB-SRS-04`, `DOC-PBB-SDD-03`, `DOC-PBB-SEC-01` | `App\Services\OtpAuthenticationService` |
| `DOC-PBB-TC-ERP-02` | CRM Inquiries, Linimasa & SLA | `DOC-PBB-PRD-03`, `DOC-PBB-SRS-04`, `DOC-PBB-SDD-01`, `DOC-PBB-SDD-04` | `App\Filament\Resources\InquiryResource`, `App\Livewire\InternalCommentsTimeline` |
| `DOC-PBB-TC-ERP-03` | Quotation & Live Margin HPP | `DOC-PBB-PRD-03`, `DOC-PBB-SRS-04`, `DOC-PBB-SDD-03` | `App\Services\QuotationCalculationService`, `App\Services\PdfExportService` |
| `DOC-PBB-TC-ERP-04` | Sales Order & Approval Limit | `DOC-PBB-PRD-03`, `DOC-PBB-SRS-04`, `DOC-PBB-SDD-03` | `App\Filament\Resources\SalesOrderResource`, `App\Services\DocumentNumberService` |
| `DOC-PBB-TC-ERP-05` | Delivery Order & Partial DO | `DOC-PBB-PRD-03`, `DOC-PBB-SRS-04`, `DOC-PBB-SDD-03` | `App\Filament\Resources\DeliveryOrderResource` |
| `DOC-PBB-TC-AGN-01` | Registrasi & Profil Agen | `DOC-PBB-PRD-01`, `DOC-PBB-SRS-04`, `DOC-PBB-SEC-04` | `App\Models\AgentProfile`, `App\Services\SecureFileUploadService` |
| `DOC-PBB-TC-AGN-02` | Inkuiri, Chat Agen & Isolasi | `DOC-PBB-SRS-04`, `DOC-PBB-SAD-03`, `DOC-PBB-SDD-01`, `DOC-PBB-SDD-04` | `App\Models\Scopes\AgentTenantScope`, `App\Filament\Agent\Resources\AgentInquiryResource`, `App\Livewire\InternalCommentsTimeline` |
| `DOC-PBB-TC-AGN-03` | Kalkulasi & Pencairan Komisi | `DOC-PBB-PRD-03`, `DOC-PBB-SRS-04`, `DOC-PBB-SDD-03` | `App\Services\CommissionPayoutService`, `App\Models\CommissionLedger` |
| `DOC-PBB-TC-CAT-01` | Taksonomi Brand & Kategori | `DOC-PBB-SRS-02`, `DOC-PBB-SDD-04`, `DOC-PBB-MIG-02` | `App\Filament\Resources\BrandResource`, `App\Filament\Resources\CategoryResource` |
| `DOC-PBB-TC-CAT-02` | Master Produk & Pipeline WebP | `DOC-PBB-SRS-02`, `DOC-PBB-SDD-03`, `DOC-PBB-SEC-03` | `App\Services\ProductImageProcessingService`, `App\Filament\Resources\ProductResource` |
| `DOC-PBB-TC-CAT-03` | Engine Import Massal Excel | `DOC-PBB-SRS-02`, `DOC-PBB-SDD-03`, `DOC-PBB-MIG-03` | `App\Imports\ProductsBulkImport`, `App\Services\BulkSkuNormalizationService` |
| `DOC-PBB-TC-SEC-01` | Isolasi Subdomain & Anti-Bot | `DOC-PBB-SAD-01`, `DOC-PBB-API-03`, `DOC-PBB-SEC-02` | `App\Http\Middleware\SubdomainAccessControl` |
| `DOC-PBB-TC-SEC-02` | Konkurensi & Race Condition | `DOC-PBB-QA-02`, `DOC-PBB-SDD-03`, `DOC-PBB-API-03` | `App\Services\DocumentNumberService` |
| `DOC-PBB-TC-SEC-03` | Sanitasi & Validasi Unggahan | `DOC-PBB-QA-01`, `DOC-PBB-SEC-03` | `App\Services\SecureFileUploadService` |
| `DOC-PBB-TC-SEC-04` | Privasi UU PDP & Audit Log | `DOC-PBB-BRD-04`, `DOC-PBB-SEC-04` | `spatie/laravel-activitylog`, `App\Models\ActivityLog` |
| `DOC-PBB-TC-INF-01` | Seeder & Rollback Migrasi | `DOC-PBB-MIG-01`, `DOC-PBB-MIG-02`, `DOC-PBB-SDD-01` | `Database\Seeders\DatabaseSeeder`, `php artisan migrate:rollback` |
| `DOC-PBB-TC-INF-02` | Scheduler, Cron & Backup | `DOC-PBB-SAD-05`, `DOC-PBB-API-04`, `DOC-PBB-DEP-04` | `App\Console\Commands\CheckSlaStaleInquiries`, `pbb:monthly-backup` |
| `DOC-PBB-TC-INF-03` | CI/CD Pipeline & Rollback | `DOC-PBB-DEP-01`, `DOC-PBB-DEP-02`, `DOC-PBB-DEP-03` | `.github/workflows/deploy.yml`, `deploy.sh`, `rollback.sh` |
