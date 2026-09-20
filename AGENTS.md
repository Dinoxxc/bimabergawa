# AGENTS.md - Operational Rulebook & Architectural Memory Anchor
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Proyek | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `AGY-PBB-AGENT-RULEBOOK` |
| **Versi Dokumen** | 1.6.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Autonomous Agent & Engineering Operational Guidelines |

---

## 1. Identitas Korporat, Konteks Bisnis, & Ruang Lingkup Sistem

PT. Panca Bima Bergawa (PBB) adalah entitas distributor resmi B2B (*Business-to-Business*) dan mitra pengadaan industri terpadu (*One-Stop Industrial General Supplier & Procurement Partner*) terkemuka di Indonesia yang melayani seluruh kebutuhan operasional pabrik, gedung komersial, dan perusahaan:
- **Elektrikal & Tenaga Listrik:** Distribusi daya listrik, MCB, MCCB, ACB, kontaktor, kapasitor bank, panel listrik, kabel, busbar, dan trafo (Kemitraan resmi: Schneider Electric, ABB, Legrand, Chint).
- **Mekanikal, Pompa, & Pemipaan:** Katup industri (*valves*), pompa sentrifugal, pipa seamless/welded, *flange*, *fitting*, *gasket*, dan *bearing* (Kitz, Grundfos, Torishima, Rucika).
- **Otomasi Pabrik, Kontrol, & Sensor:** Inverter frekuensi (*variable speed drive*), PLC, sensor telemecanique/otomasi, *relay*, instrumentasi digital, dan transmisi data industri (Schneider Electric, Omron, Mitsubishi Electric).
- **Peralatan Keselamatan Kerja, Safety & K3 (HSE):** Sepatu safety, helm proyek, rompi visibilitas tinggi, sarung tangan proteksi, kacamata safety, harness, APAR, dan rambu K3 (3M, Cheetah, Krisbow Safety).
- **Perkakas Teknik & MRO Pabrik (Maintenance, Repair, Operations):** *Hand tools*, *power tools*, bahan kimia industri, *lubricant / grease*, *sealant / adhesive*, baut/fastener, dan peralatan bengkel pemeliharaan mesin pabrik (Bosch, Tekiro, Loctite, WD-40).
- **Pencahayaan Industri & Komersial:** Pencahayaan industri komersial, *high-bay LED*, lampu sorot stadion/proyek, *downlight architectural*, dan armatur tahan ledakan (*explosion-proof*) (Philips Lighting / Signify).
- **Infrastruktur Fasilitas Gedung:** Sistem pengkabelan terstruktur, kabel tray / ladder (Legrand P31), stop kontak industri komersial, box panel industri, HVAC, dan modul otomasi gedung.

PBB melayani baik pengadaan barang katalog resmi maupun pengadaan barang kustom / *ad-hoc sourcing* non-katalog sesuai daftar kebutuhan (*Bill of Quantities / BOQ*) dari kontraktor MEP, pembuat panel (*panel builders*), pengelola gedung, dan bagian pemeliharaan (*maintenance*) pabrik manufaktur di seluruh Indonesia.

### 1.1 Topologi Sistem Multi-Domain (Single Database Multi-Domain Architecture)

Sistem digital PBB beroperasi di bawah arsitektur basis data tunggal (*Single Database Multi-Domain*) yang memisahkan secara ketat antara ranah etalase publik dan ranah operasional internal:

```
                                  [BASIS DATA TERPUSAT: MYSQL 8.0 INNODB]
                                                     │
                       ┌─────────────────────────────┴─────────────────────────────┐
                       ▼                                                           ▼
         [DOMAIN PUBLIK: bimabergawa.com]                            [SUBDOMAIN ERP: app.bimabergawa.com]
         ────────────────────────────────                            ────────────────────────────────────
         • Company Profile Resmi Rasuna Said                         • Otentikasi Passwordless Email OTP (300s)
         • Katalog Produk Multi-Brand (Zero-Price)                   • CRM Inquiries & Kanban SLA Stale Alert
         • RFQ Cart Drawer (Alpine.js Store)                         • Quotation Builder + Live Margin HPP Guard
         • Bulk SKU Quick Paste Parser                               • Sales Order & Approval Limit Direksi
         • Direct WhatsApp Telemetri Track                           • Surat Jalan Logistik (Partial Delivery Order)
         • Upload BOQ Proyek (PDF/XLSX Magic Bytes)                  • Portal Agen Freelance & Ledger Komisi (Multi-Tenant)
                                                                     • Pengaturan Pajak PPN & Komersial Dinamis (system_settings)
```

---

## 2. Core Tech Stack & Target Infrastruktur

| Lapisan Sistem | Teknologi / Framework | Versi Spesifik | Catatan Konfigurasi, Optimasi, & Batasan |
| :--- | :--- | :---: | :--- |
| **Bahasa Pemrograman** | PHP (CLI & FPM) | `8.3.x` | `declare(strict_types=1);`, OPcache JIT aktif, memory limit 512 MB |
| **Backend Framework** | Laravel Framework | `11.x` | Minimalist Skeleton, Service Layer Pattern, FormRequest mandatory |
| **Panel Administrasi** | Filament Admin | `3.x` | Livewire 3, Tailwind CSS, Heroicons SVG, Spatie Permission |
| **Basis Data Relasional** | MySQL Server | `8.0.x` | Engine InnoDB, Default Collation `utf8mb4_unicode_ci`, Strict SQL Mode |
| **Frontend Reaktif** | Blade Views + Alpine.js | `3.x` | Alpine Store, LocalStorage Persistence, Event Dispatching |
| **CSS & Design Tokens** | Tailwind CSS | `3.4.x` | Palet Monokrom Minimalis (Slate/Zinc), Typography Outfit + Inter |
| **Optimasi Gambar** | Intervention Image | `3.x` | Driver GD, Konversi WebP 82%, Max 1600px width/height, EXIF stripped |
| **Ekspor Dokumen** | DomPDF | `2.x` | PDF Standar ISO A4, Bebas Emoji, Kop Rasuna Said, Font Liberation Sans |
| **Target Web Server** | LiteSpeed Web Server | Enterprise | Shared Hosting Hostinger, Batas RAM 512 MB, LiteSpeed Cache |
| **Pengujian Otomatis** | Pest PHP / PHPUnit | `3.x` / `11.x` | Unit Tests, Feature Tests, & Architecture Test Suite |

---

## 3. Dua Belas Batasan Mutlak & Aturan Arsitektur (Hard Constraints)

Setiap AI Agent, sistem otomatis, atau perekayasa perangkat lunak yang berkontribusi pada repositori ini **DILARANG KERAS** melanggar 12 aturan mutlak berikut:

### 3.1 Larangan Penggunaan Karakter Emoji (Zero Emoji Constraint)
- **0% Emoji Unicode:** Dilarang keras menggunakan karakter emoji Unicode (seperti tanda centang hijau, roket, api, jempol, dll.) pada seluruh kode Blade, PHP, controller, mailer, log, komentar kode, dokumen markdown, commit message, maupun tampilan UI antarmuka pengguna.
- **Standar Ikon Grafis:** Wajib 100% menggunakan SVG Icons resmi:
  - Web Publik: Blade Lucide Icons (`<x-lucide-... />`)
  - Panel ERP: Filament Heroicons (`heroicon-o-...` untuk navigasi tabel, `heroicon-m-...` untuk tombol mini)
  - Teks Status: Gunakan text badge formal (contoh: `[Passed]`, `[Pending]`, `[Approved]`, `[Warning]`, `[Not Executed]`).

### 3.2 Desain Anti-AI Manifesto (Monochrome Minimalist Standard)
- Dilarang keras menggunakan gaya desain AI klise:
  - Latar belakang gradien neon ungu-biru (*purple/blue neon glow*).
  - Bayangan tebal bercahaya (*glow shadow*).
  - Animasi mengambang (*floating elements*) non-fungsional yang mengalihkan perhatian.
  - Teks pemasaran AI generik robotik (seperti *"Kesimpulan"*, *"Masa Depan Digital"*, atau *"Solusi Terdepan"*).
- Wajib menerapkan palet warna **Monokrom Minimalis B2B Formal** (Netral Slate/Zinc):
  - Background Utama: `#ffffff` | Background Kartu: `#fafafa` / `#f4f4f5`
  - Border Garis: `#e4e4e7` | Dark Border / Fokus: `#27272a`
  - Teks Heading: `#09090b` | Teks Body: `#18181b` | Teks Muted: `#71717a`
  - Aksen Interaktif / Tombol CTA: `#18181b` (Hover: `#27272a`)
- Tipografi Wajib: **Outfit** untuk Heading/Judul dan **Inter** untuk Teks Bodi/Tabel.

### 3.3 Penegakan Kebijakan Tanpa Harga pada Web Publik (Zero-Price Policy)
- Pada web publik (`bimabergawa.com`), **DILARANG KERAS** menampilkan angka harga (Rp 0, harga nominal, diskon rupiah, coret harga, maupun angka modal HPP).
- Seluruh kartu katalog dan detail SKU hanya menampilkan spesifikasi teknis, ketersediaan stok/status indent, dan tombol aksi `Minta Penawaran / Tambah ke RFQ` serta `Tanya via WhatsApp`.

### 3.4 Otentikasi Tanpa Sandi (Passwordless Email OTP)
- Panel ERP `app.bimabergawa.com` tidak menggunakan mekanisme login kata sandi statis biasa.
- Otentikasi wajib menggunakan OTP 6-digit CSPRNG (`random_int(100000, 999999)`), di-hash SHA-256 pada cache backend dengan masa aktif tepat **300 detik (5 menit)**, tombol kirim ulang terkunci *cooldown 60 detik*, dan batasan percobaan verifikasi *throttle 3 kali per 10 menit*.

### 3.5 Live Margin HPP Guard pada Quotation Builder
- Setiap pembuatan penawaran harga wajib menghitung dan mengevaluasi margin kotor secara seketika (*real-time reactive*): `((Harga Jual - Base HPP) / Harga Jual) * 100%`:
  - `Margin >= 15.00%`: Status Aman (Badge Hijau).
  - `8.00% <= Margin < 15.00%`: Peringatan Margin Tipis (Badge Kuning).
  - `Margin < 8.00%` atau Harga Jual di Bawah HPP: Margin Kritis (Badge Merah Menyala). Sistem mengunci tombol terbit dan mewajibkan eskalasi **Approval Direktur**.

### 3.6 Snapshot Pajak PPN Statis Multi-Tarif Dinamis (Kepatuhan UU HPP & UU KUP)
- Sistem wajib mendukung penyesuaian tarif PPN nasional multi-periode yang dinamis dan berulang (misal: 11% -> 12% -> 13% -> 11%).
- Tarif PPN aktif dikelola secara mandiri oleh Super Admin / Direksi Finance melalui tabel `system_settings` di panel ERP (`app.bimabergawa.com`) tanpa ketergantungan pada perubahan teknis file `.env`.
- Setiap rekaman transaksi quotation, sales order, dan invoice wajib mencatat persentase PPN saat transaksi diterbitkan (`vat_rate_applied = $activeVatRate`). Dilarang keras menghitung ulang PPN transaksi historis dari konfigurasi global saat membaca data masa lalu guna menjamin kepatuhan audit pajak 10 tahun UU KUP.

### 3.7 Penomoran Dokumen Bebas Race Condition & Terlindungi (Atomic Concurrency Lock & Obfuscated Salt)
- Penomoran dokumen transaksi wajib melalui `DocumentNumberService` menggunakan transaksi database atomik (`SELECT ... FOR UPDATE`) dengan auto-retry 3x saat terjadi deadlock MySQL, serta menyematkan 4-karakter cryptographic security salt (`SSSS`) setelah 2-digit bulan (`MM`) untuk proteksi anti-tebak/anti-enumerasi pihak luar tanpa merusak urutan numerik internal. Standar format resmi:
  - Inkuiri: `PBB/INQ/YYYY/MMSSSS/XXXX` (Contoh: `PBB/INQ/2026/092F9C/0001`)
  - Penawaran: `PBB/QUO/YYYY/MMSSSS/XXXX` (Revisi: `PBB/QUO/2026/092F9C/0001-R1`)
  - Sales Order: `PBB/SOR/YYYY/MMSSSS/XXXX` (Contoh: `PBB/SOR/2026/092F9C/0001`)
  - Surat Jalan: `PBB/DOR/YYYY/MMSSSS/XXXX` (Parsial: `PBB/DOR/2026/092F9C/0001/01`)
  - Faktur Tagihan (Invoice): `PBB/INV/YYYY/MMSSSS/XXXX` (Contoh: `PBB/INV/2026/092F9C/0001`)
  - Faktur Komisi: `PBB/COM/YYYY/MMSSSS/XXXX` (Contoh: `PBB/COM/2026/092F9C/0001`)

### 3.8 Enkripsi Data Pribadi Sensitif At-Rest (UU PDP No. 27/2022)
- Kolom NIK KTP (`nik_ktp`), NPWP (`npwp_number`), dan nomor rekening perbankan (`bank_account_number`) pada model `AgentProfile` wajib dienkripsi pada level basis data menggunakan Eloquent `$casts = ['encrypted']` (kunci enkripsi `APP_KEY`).

### 3.9 Isolasi Data Multi-Tenant Agen Freelance (Anti-IDOR)
- Mitra Agen Freelance hanya boleh mengakses data inkuiri, penawaran, riwayat komisi, dan catatan publik miliknya sendiri.
- Model `Inquiry`, `Quotation`, dan `CommissionPayout` wajib menerapkan Global Scope `AgentTenantScope` untuk membatasi kueri otomatis (`WHERE freelance_sales_id = auth()->user()->freelance_sales_id`).
- `AgentTenantScope` wajib menyertakan bypass eksplisit untuk seluruh peran staf internal (`super_admin`, `sales_pic`, `finance`, `logistics`) agar staf tidak terkunci saat menguji atau membuka akun agen.
- Model `InternalComment` wajib menerapkan Global Scope `agent_comment_visibility` otomatis sehingga akun agen freelance hanya dapat membaca komentar berstatus `type = 'agent_visible'` (0% kebocoran modal HPP atau instruksi direksi).
- Endpoint pencairan komisi wajib memverifikasi kepemilikan record agen secara eksplisit sebelum melakukan mutasi saldo.

### 3.10 Efisiensi Memori Shared Hosting (Batas RAM 512 MB)
- Import massal katalog Excel wajib menggunakan chunking 100 baris (`WithChunkReading`).
- Dilarang memuat seluruh koleksi produk ke memori (`Product::all()` dilarang; wajib gunakan `Product::cursor()` atau `chunk(100)`).
- File upload dibatasi maksimal 5 MB dengan pembersihan buffer memori secara berkala saat pemrosesan gambar WebP.

### 3.11 Protokol Konfirmasi Deviasi & Sinkronisasi Dokumentasi (Change Management & Documentation-First)
- **Konfirmasi Deviasi Wajib:** Jika terdapat instruksi, arahan, atau kebutuhan baru dari pengguna yang berbeda, bertentangan, atau memodifikasi apa yang telah tertulis dalam berkas spesifikasi `docs/`, `test-cases/`, atau rencana arsitektur awal, AI Agent **WAJIB SECARA EKSPLISIT** memberitahukan kepada pengguna mengenai perbedaan tersebut dan meminta konfirmasi formal sebelum mengeksekusi perubahan.
- **Transparansi Perbedaan (Dampak Deviasi):** Pemberitahuan deviasi wajib memuat:
  1. *Kondisi Dokumen Eksisting:* Spesifikasi yang saat ini tertulis pada `docs/`.
  2. *Usulan Perubahan Baru:* Detail modifikasi yang diminta pengguna.
  3. *Dampak Arsitektur & Bisnis:* Konsekuensi terhadap skema database, integritas relasi, logika margin/SLA, atau kepatuhan hukum.
- **Prinsip Sinkronisasi Mutlak (Documentation-First):** Apapun perubahan yang telah dikonfirmasi dan disetujui pengguna **WAJIB DIDOKUMENTASIKAN TERLEBIH DAHULU** ke berkas dokumentasi terkait pada `docs/` (lengkap dengan pembaruan tabel *Riwayat Revisi Dokumen*), skenario `test-cases/`, dan checklist `AGENTS.md`. Dilarang keras melakukan perubahan kode tersembunyi (*silent code changes*) yang tidak sinkron dengan dokumentasi resmi proyek.
- **Penyelarasan Test Case Bersamaan (Mandatory Test Case Co-Evolution):** Setiap kali terdapat penyesuaian, revisi, atau penambahan kebutuhan fitur pada berkas dokumentasi spesifikasi (`docs/`), AI Agent **WAJIB SECARA PROAKTIF MEMERIKSA DAN MENYELARASKAN** apakah berkas skenario pengujian (`test-cases/`) terkait membutuhkan pembaruan atau penambahan skenario baru. Dilarang menyelesaikan pembaruan dokumentasi tanpa memverifikasi dan menyinkronkan test suite terkait, guna menjamin ketertelusuran persyaratan (*Requirement Traceability Matrix*) selalu berada pada kondisi 100% konsisten.

### 3.12 Protokol Konfirmasi Push, Merge, & Siklus Hidup Branch Bersih (Clean Branch Lifecycle)
- **Larangan Push/Merge Otomatis:** AI Agent dilarang keras melakukan `git push` atau `git merge` ke `staging` maupun `main` tanpa persetujuan eksplisit dari pengguna.
- **Laporan Rinci Wajib Sebelum Push:** Sebelum meminta konfirmasi push dan merge, AI Agent wajib menyajikan laporan rinci yang memuat:
  1. *Penambahan:* Berkas atau fungsi baru yang ditambahkan.
  2. *Perbaikan:* Bug, error, atau anomali yang diperbaiki.
  3. *Perubahan:* Modifikasi pada konfigurasi, kode, atau skema eksisting.
  4. *Hasil Verifikasi / Testing:* Bukti uji coba/linting lokal yang membuktikan kode berfungsi 100%.
- **Penghapusan Working Branch Pasca-Merge:** Setelah pengguna memberikan konfirmasi untuk push dan merge ke `staging`, working branch yang bersangkutan wajib segera dihapus baik di lokal (`git branch -d <nama_branch>`) maupun di remote jika ada (`git push origin --delete <nama_branch>`), guna menjaga repositori selalu bersih dan terbebas dari *stale branches*.

---

## 4. Matriks 5 Peran Spatie RBAC & Hak Akses

| Modul / Tindakan Sistem | Super Admin | Sales PIC | Finance | Logistik | Mitra Agen |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Kelola Inquiries / CRM** | Penuh | Milik Sendiri & Tim | Lihat Saja | Dilarang | Milik Sendiri (Terisolasi) |
| **Buat / Revisi Quotation** | Penuh | Buat & Revisi | Lihat Saja | Dilarang | Dilarang |
| **Lihat Base HPP & Margin** | Ya | Ya | Ya | **Dilarang** | **Dilarang (0% Bocor)** |
| **Approval SO Margin Kritis** | **Wajib Direktur** | Dilarang | Dilarang | Dilarang | Dilarang |
| **Penerbitan Surat Jalan (DO)** | Penuh | Lihat Saja | Lihat Saja | **Penuh (Fisik Saja)** | Dilarang |
| **Verifikasi Invoice & Bayar** | Penuh | Dilarang | **Penuh** | Dilarang | Dilarang |
| **Pencairan Komisi Agen** | Approval Akhir | Dilarang | Verifikasi Struk | Dilarang | Ajukan Mandiri |
| **Import Massal Katalog Excel** | Penuh | Penuh | Dilarang | Dilarang | Dilarang |
| **Manajemen Pengguna & Role** | **Penuh** | Dilarang | Dilarang | Dilarang | Dilarang |
| **Pengaturan Sistem Global** | **Penuh** | Dilarang (HPP) | **Penuh (Komersial)** | Penuh (Gudang) | Dilarang |
| **Profil & Tema (Dark/Light)** | **Penuh (Mandiri)** | **Penuh (Mandiri)** | **Penuh (Mandiri)** | **Penuh (Mandiri)** | **Penuh (Mandiri)** |
| **Diskusi / Chat Transaksi** | **Penuh (Internal & Agen)** | Milik Sendiri & Tim | Catatan Finance | Catatan Logistik | Inkuiri Milik Sendiri (`agent_visible` saja) |

---

## 5. Peta Arsitektur Repositori & Rujukan Berkas

```
e:\Project\bimabergawa/
├── AGENTS.md                                          # [Dokumen Ini] Operational Context & Memory Anchor
├── README.md                                          # Panduan Induk Repositori & Developer Onboarding
├── .agents/
│   └── rules/                                         # Aturan Modular Agent
│       ├── laravel-filament-standards.md              # Standar Arsitektur Laravel 11 & Filament 3
│       ├── anti-ai-design-system.md                   # Standar Desain Monokrom Minimalis & Anti-AI
│       ├── database-and-business-logic.md             # Skema Database, Sequence Lock, & Logika Bisnis
│       └── security-and-compliance.md                 # Standar Keamanan, Enkripsi PDP, & Anti-IDOR
├── docs/                                              # Dokumentasi Lengkap Proyek (61 Berkas Markdown, v1.3.0)
│   ├── README.md                                      # Master Index Dokumentasi
│   ├── 01-requirements/                               # BRD (01-05), PRD (01-05), SRS (01-05)
│   ├── 02-architecture-and-design/                    # SAD (01-05), SDD (01-05), UI-UX (01-04)
│   ├── 03-integration-and-deployment/                 # API-Routing (01-04), CICD (01-04), Migration (01-04)
│   └── 04-quality-and-security/                       # QA-Testing (01-04), Security-Compliance (01-04)
├── test-cases/                                        # Test Case Suite Lengkap (23 Berkas, 374+ Skenario)
│   ├── README.md                                      # Master Index Test Case & Traceability
│   ├── 01-public-website/                             # Test Case Web Publik (01-04, 63 Skenario)
│   ├── 02-internal-erp-crm/                           # Test Case Internal ERP (01-05, 81 Skenario)
│   ├── 03-agent-commission-portal/                    # Test Case Portal Agen (01-03, 48 Skenario)
│   ├── 04-catalog-and-inventory-management/           # Test Case Manajemen Katalog (01-03, 48 Skenario)
│   ├── 05-security-and-non-functional/                # Test Case Keamanan & NFR (01-04, 64 Skenario)
│   └── 06-integration-and-infrastructure/             # Test Case Seeder, Cron, & CI/CD (01-03, 48 Skenario)
├── app/
│   ├── Filament/                                      # Panel ERP (Resources) & Panel Agen (Agent)
│   ├── Http/Controllers/                              # Public Web & Redirection Controllers
│   ├── Http/Requests/                                 # Form Request Validation Classes
│   ├── Models/                                        # Eloquent Models, Scopes, & Casts
│   └── Services/                                      # Business Logic & Core Service Layer
│       ├── DocumentNumberService.php                  # Atomic Sequence Numbering
│       ├── QuotationCalculationService.php            # Live Margin & Snapshot PPN
│       ├── ProductImageProcessingService.php          # WebP Conversion & EXIF Stripping
│       ├── SkuParserService.php                       # Bulk SKU Regex Parser
│       ├── CommissionPayoutService.php                # Agent Commission Ledger & Payouts
│       ├── BulkSkuNormalizationService.php            # SKU Normalization & Cleaning
│       ├── DeliveryOrderFulfillmentService.php        # Partial DO & Remaining Qty Tracking
│       └── SecureFileUploadService.php                # MIME Magic Bytes & Private Storage
├── routes/
│   ├── web.php                                        # Rute Domain Publik (bimabergawa.com)
│   └── erp.php                                        # Rute Subdomain Internal (app.bimabergawa.com)
└── tests/                                             # Automated Test Suite (Pest / PHPUnit)
```

---

## 6. Standar Penulisan Kode & Panduan Eksekusi (Engineering Guidelines)

1. **Strict Typing:** Seluruh berkas PHP wajib diawali `declare(strict_types=1);` dengan *type hint* eksplisit pada seluruh parameter dan *return types*.
2. **Service Layer Pattern:** Controller dan Filament Page dilarang memuat kalkulasi bisnis langsung. Delegasikan seluruh logika ke `App\Services\...`.
3. **Form Request Validation:** Seluruh validasi input formulir wajib menggunakan kelas mandiri pada `App\Http\Requests\...`.
4. **Inspeksi Magic Bytes:** Validasi unggahan berkas wajib menggunakan `Illuminate\Validation\Rules\File` dan verifikasi biner kernel `finfo`.
5. **Kebersihan Format Kode:** Jalankan `./vendor/bin/pint` sebelum melakukan commit untuk memastikan format standar PSR-12.
6. **Perintah Terminal Eksekusi:**
   - Dilarang mengajukan perintah `cd` beruntun pada shell; jalankan perintah dengan working directory eksplisit atau menggunakan parameter `--prefix` / `-C`.
   - Jalankan test suite: `php artisan test` atau `./vendor/bin/pest`.
   - Jalankan migrasi & seeding: `php artisan migrate:fresh --seed`.
7. **Protokol Deviasi & Validasi Rencana:** Selalu validasi instruksi pengguna terhadap blueprint `docs/`. Jika ada perbedaan, hentikan tindakan sementara, laporkan deviasi untuk konfirmasi pengguna, dan sinkronisasikan seluruh dokumentasi terkait sebelum mulai mengedit kode.
8. **Enforce Morph Map Polimorfik:** Wajib mendaftarkan alias pendek pada `Relation::enforceMorphMap()` di `AppServiceProvider` untuk mereduksi konsumsi memori index RAM hingga 75% di server shared hosting 512 MB.
9. **Isolasi Berkas Privat (UU PDP No. 27/2022):** Seluruh dokumen identitas agen (KTP), slip transfer bank, dan berkas fisik Purchase Order klien wajib disimpan di disk `private` (`storage/app/private/`) dan hanya diakses via Temporary Signed URL.
10. **Resolusi Slug Soft Deletes Deterministik:** Tangani benturan indeks unik MySQL saat entitas di-soft delete dengan memotong slug maks 220 karakter dan memperbarui slug lama via `ProductObserver::deleting` (`-deleted-{timestamp}`), serta normalisasi kembali slug bersih saat dipulihkan via `ProductObserver::restoring` menggunakan candidate while-loop deterministik guna mencegah tabrakan duplicate key error.
11. **SOP Tata Kelola Evolusi Fitur Pasca-Rilis:** Wajib mematuhi protokol `DOC-PBB-DEP-03` Bagian 4: penambahan fitur wajib melalui migrasi aditif non-destruktif (`nullable foreign keys`), modifikasi alur bisnis diprioritaskan via `system_settings`, dan penonaktifan fitur menggunakan feature flags / RBAC tanpa merusak data audit historis.
12. **Integritas Rantai Transaksi Historis (Full-Chain withTrashed):** Seluruh relasi `BelongsTo` yang merujuk pada entitas induk SoftDeletable (`Product`, `Inquiry`, `Quotation`, `SalesOrder`, `DeliveryOrder`, `Invoice`, `FreelanceSales`) wajib memanggil `->withTrashed()` untuk mencegah crash *null pointer exception* saat memuat dokumen audit pajak 10 tahun UU KUP.

---

## 7. Standar Git Branching & Conventional Taxonomy

Seluruh agen dan pengembang wajib mematuhi arsitektur branching Git Flow berikut:

### 7.1 Aturan Dasar Branching
1. **Branch `main` (Production):** Terkunci, hanya menerima merge dari branch `release/*` atau `hotfix/*`. Dilarang melakukan commit langsung ke `main`.
2. **Branch `staging` (Sandbox & Pre-Production):** Tempat penggabungan (*integration*) seluruh sub-bagian fitur yang telah selesai diuji.
3. **Branch Pengerjaan (Working Branches):** Wajib dicabangkan dari `staging` (kecuali `hotfix/*` dari `main`), dan menggunakan prefix resmi:
   - `chore/*` : Konfigurasi repositori, lingkungan, CI/CD, linter, dan skrip deploy.
   - `feature/core-*` : Migrasi database, model relasional, dan service layer inti.
   - `feature/web-*` : Pengembangan antarmuka dan fitur domain publik `bimabergawa.com`.
   - `feature/erp-*` : Pengembangan modul operasional subdomain internal `app.bimabergawa.com`.
   - `bugfix/*` : Perbaikan bug yang ditemukan selama integrasi di `staging`.
   - `hotfix/*` : Perbaikan darurat langsung dari `main` untuk isu produksi.
   - `test/*` : Penulisan berkas pengujian otomatis (Pest PHP).
   - `release/*` : Persiapan rilis versi stabil menuju `main`.

### 7.2 Protokol Konfirmasi Push, Merge, & Penghapusan Working Branch
Setiap kali pengerjaan pada working branch selesai, ikuti prosedur ketat berikut:
1. **Penyelesaian Lokal & Pengujian:** Kerjakan fitur pada working branch, jalankan verifikasi lokal hingga lulus 100%.
2. **Pengajuan Laporan ke Pengguna:** Tampilkan laporan terstruktur: apa yang **ditambahkan**, **diperbaiki**, atau **diubah** beserta bukti hasil pengujian, lalu minta konfirmasi pengguna secara eksplisit sebelum melakukan push/merge.
3. **Eksekusi Pasca-Konfirmasi Pengguna:**
   - Pindah ke branch `staging`: `git checkout staging`.
   - Lakukan merge: `git merge <nama_branch>`.
   - Push branch `staging` ke remote: `git push origin staging`.
   - Hapus working branch lokal: `git branch -d <nama_branch>`.
   - Hapus working branch remote jika pernah di-push: `git push origin --delete <nama_branch>`.
   - Perbarui checklist status pada `AGENTS.md` menjadi `[x]`.

---

## 8. Master Development Checklist & Traceability Matrix

Gunakan checklist ini untuk melacak status pengerjaan setiap sub-bagian. Perbarui status checklist (`[ ]` belum dimulai, `[/]` sedang dikerjakan, `[x]` selesai dan ter-merge ke staging) secara berkala:

### Fase 0: Setup Git & Konfigurasi Baseline
- [x] `chore/00-git-init-and-baseline` : Inisialisasi Git, setup `.gitignore`, initial commit `main`, buat `staging`, dan link remote GitHub.
- [x] `chore/01-environment-and-multidomain-config` : Konfigurasi `.env.example`, `.env`, multi-domain routing, dan koneksi MySQL 8.0.

### Fase 1: Core Database & Model Layer
- [x] `feature/core-01-database-migrations` : Skema tabel migrasi MySQL 8.0 InnoDB (28 tabel domain: `brands`, `categories`, `products`, `product_specifications`, `product_images`, `inquiries`, `inquiry_items`, `inquiry_telemetries`, `quotations`, `quotation_payment_terms`, `quotation_items`, `sales_orders`, `delivery_orders`, `delivery_order_items`, `invoices`, `invoice_items`, `commission_tiers`, `freelance_sales`, `commission_payouts`, `document_sequences`, `article_categories`, `articles`, `system_settings`, `notifications`, `internal_comments`, `activity_logs`) dilengkapi Soft Deletes dan multi-currency. *(Rujukan: test-cases/06-integration-and-infrastructure/01-database-seeders-and-migration-rollback.md)*
- [x] `feature/core-02-eloquent-models-and-scopes` : 28 Model Eloquent domain, relasi ORM historis full-chain (`withTrashed`), `latestOfMany`, enkripsi data pribadi UU PDP `$casts = ['encrypted']`, Global Scope `AgentTenantScope` (dengan bypass staf internal & isolasi `CommissionPayout`), proteksi komentar internal `agent_comment_visibility`, `ProductObserver` resolusi slug deterministik deleting/restoring, dan `Relation::enforceMorphMap()`. *(Rujukan: test-cases/05-security-and-non-functional/04-data-privacy-and-audit-logging.md, test-cases/03-agent-commission-portal/02-agent-inquiry-and-tenant-isolation.md, test-cases/02-internal-erp-crm/06-commercial-invoicing-and-audit-trail.md)*
- [x] `feature/core-03-seeders-and-factories` : Seeder 5 Spatie Roles & Permissions, 18 Prinsipal Global + taksonomi multi-divisi industri (Elektrikal, Mekanikal, Otomasi, Safety K3, MRO), Master Satuan Dinamis (`units_of_measure`), kustomisasi spesifikasi per item, 28 Model Factories, dan integritas data pengadaan pabrik. *(Rujukan: test-cases/06-integration-and-infrastructure/01-database-seeders-and-migration-rollback.md)*
- [x] `feature/core-03b-service-foundation-and-b2b-contracts` : Fondasi kontrak Service Layer (Typed DTO, Domain Events, Custom Exceptions), migrasi aditif non-destruktif (valas hedging, idempotency key, category spec template, fulltext product index), dan preset termin pembayaran B2B. *(Rujukan: docs/02-architecture-and-design/SDD/03-core-services-and-business-logic.md)*

### Fase 2: Core Service Layer (Logika Bisnis Terisolasi)
- [x] `feature/core-04-service-document-number` : `DocumentNumberService` format resmi `PBB/{TYPE3}/{YYYY}/{MMSSSS}/{XXXX}` ber-salt kriptografis, Atomic Lock `SELECT ... FOR UPDATE` + 3x retry saat deadlock (`INQ`, `QUO`, `SOR`, `DOR`, `INV`, `COM`), integrasi `DocumentNumberRequestDTO`, emisi domain event `DocumentNumberGenerated`, format revisi penawaran `-R{n}`, partial DO, dan proteksi overflow > 9999. *(Rujukan: test-cases/05-security-and-non-functional/02-concurrency-and-race-condition.md)*
- [ ] `feature/core-05-service-quotation-margin` : `PricingAndMarginCalculatorService` Live Margin HPP Guard formula + snapshot PPN dinamis via `SystemSettingService`. *(Rujukan: test-cases/02-internal-erp-crm/02-quotation-margin.md)*
- [ ] `feature/core-06-service-sku-parser` : `SkuParserService` Regex textarea RFQ parser & `BulkSkuNormalizationService`. *(Rujukan: test-cases/01-public-website/03-rfq-cart-drawer.md)*
- [ ] `feature/core-07-service-secure-upload` : `SecureFileUploadService` biner kernel `finfo` magic bytes & UUID v4 storage privat. *(Rujukan: test-cases/05-security-and-non-functional/03-file-upload-security.md)*
- [ ] `feature/core-08-service-fulfillment-and-commission` : `DeliveryOrderFulfillmentService` pelacakan `remaining_qty` & `CommissionPayoutService` ledger mutasi komisi. *(Rujukan: test-cases/02-internal-erp-crm/04-delivery-order.md)*

### Fase 3: Domain Publik (`bimabergawa.com`)
- [ ] `feature/web-01-design-system-and-layout` : Master Layout Blade & Tailwind Design Tokens (Monokrom Minimalis), Font Outfit + Inter, Blade Lucide Icons, 0% emoji. *(Rujukan: test-cases/01-public-website/01-landing-and-navigation.md)*
- [ ] `feature/web-02-catalog-showcase-zero-price` : Showroom produk multi-brand, filter kategori, pencarian SKU, penegakan Zero-Price Policy (0% nominal rupiah). *(Rujukan: test-cases/01-public-website/02-product-catalog.md)*
- [ ] `feature/web-03-rfq-cart-drawer` : Alpine.js RFQ Store, drawer sliding reaktif, localStorage persistence, quick-paste textarea integration. *(Rujukan: test-cases/01-public-website/03-rfq-cart-drawer.md)*
- [ ] `feature/web-04-inquiry-boq-submission` : Form request validation submit inkuiri publik, upload berkas BOQ privat (maks 5 MB), WhatsApp telemetri track. *(Rujukan: test-cases/01-public-website/04-inquiry-and-boq.md)*
- [ ] `feature/web-05-company-profile-pages` : Profil resmi kantor Gd. Wirausaha Rasuna Said, sertifikasi kemitraan resmi, kontak, dan SEO meta. *(Rujukan: test-cases/01-public-website/01-landing-and-navigation.md)*

### Fase 4: Subdomain Internal ERP (`app.bimabergawa.com`)
- [ ] `feature/erp-01-passwordless-otp-auth` : Otentikasi Passwordless Email OTP (CSPRNG 6-digit, 300s TTL, SHA-256 cache, 60s cooldown, 3-attempt throttle). *(Rujukan: test-cases/02-internal-erp-crm/01-inquiries-crm.md)*
- [ ] `feature/erp-02-crm-inquiries-kanban` : Filament Inquiry Resource, Kanban SLA Stale Alert (>24h warning, >48h critical), assignment PIC. *(Rujukan: test-cases/02-internal-erp-crm/01-inquiries-crm.md)*
- [ ] `feature/erp-03-quotation-builder-margin-guard` : Filament Quotation Builder, kalkulasi reactive Live Margin HPP Guard, tombol eskalasi Approval Direktur. *(Rujukan: test-cases/02-internal-erp-crm/02-quotation-margin.md)*
- [ ] `feature/erp-04-sales-orders-management` : Konversi Quotation ke Sales Order, validasi approval limit Direksi $\ge$ Rp 100 Juta. *(Rujukan: test-cases/02-internal-erp-crm/03-sales-order.md)*
- [ ] `feature/erp-05-delivery-orders-logistics` : Modul logistik Surat Jalan (DO), validasi pengiriman parsial, dan update status SO ke `completed`. *(Rujukan: test-cases/02-internal-erp-crm/04-delivery-order.md)*
- [ ] `feature/erp-06-agent-commission-portal` : Panel Agen `/agent`, registrasi agen, inkuiri terisolasi (`AgentTenantScope`), buku besar komisi & form pencairan. *(Rujukan: test-cases/03-agent-commission-portal/)*
- [ ] `feature/erp-07-excel-import-dompdf-export` : Import katalog Excel chunking 100 baris (RAM 512 MB), ekspor PDF standar ISO A4 kop Rasuna Said. *(Rujukan: test-cases/04-catalog-and-inventory-management/)*
- [ ] `feature/erp-08-system-settings-management` : Halaman Filament `ManageSystemSettings` untuk pengaturan tarif PPN aktif dinamis, ambang batas margin approval, nomor hotline WA, dan profil kantor. *(Rujukan: docs/02-architecture-and-design/SDD/04-filament-resources-and-ui-architecture.md)*

### Fase 5: Testing, QA Verification, & CI/CD
- [ ] `test/01-pest-unit-and-feature-tests` : Eksekusi suite Pest (Unit Service, Feature Auth/Form, Architecture Test strict typing). *(Rujukan: test-cases/05-security-and-non-functional/)*
- [ ] `chore/02-cicd-github-actions` : Workflow GitHub Actions (Laravel Pint, PHPStan static analysis, Pest test run). *(Rujukan: test-cases/06-integration-and-infrastructure/03-cicd-and-deployment.md)*
- [ ] `chore/03-deployment-scripts` : Skrip `deploy.sh` & `rollback.sh` optimasi LiteSpeed shared hosting. *(Rujukan: docs/03-integration-and-deployment/CICD-Deployment/)*

### Fase 6: Final Staging Release & Go-Live
- [ ] `release/v1.0.0` : Feature freeze di `staging`, final smoke testing, merge ke `main` dan `staging`, Git Tag `v1.0.0`, dan deployment produksi.
