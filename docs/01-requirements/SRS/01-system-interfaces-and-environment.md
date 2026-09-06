# 01. Antarmuka Sistem & Lingkungan Operasional
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Technical Systems Analyst | Draf Awal | Definisi antarmuka web, software, komunikasi, & lingkungan hosting |
| `1.1.0` | 03 Sep 2026 | DevOps Specialist | Ditinjau | Penyesuaian spesifikasi runtime PHP 8.2 & LiteSpeed di Hostinger Premium |
| `1.2.0` | 05 Sep 2026 | Infrastructure Architect | Disetujui | Finalisasi isolasi antarmuka subdomain `app.domain.com` & passwordless OTP |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Antarmuka Pengguna (User Interfaces)

Sistem melayani dua arsitektur antarmuka pengguna yang terisolasi berdasarkan domain:

```
+─────────────────────────────────────────────────────────────────────────────+
|                         ANTARMUKA PENGGUNA TERISOLASI                       |
+──────────────────────────────────────+──────────────────────────────────────+
| 1. Antarmuka Publik (domain.com)     | 2. Antarmuka Internal (app.domain.com)|
+──────────────────────────────────────+──────────────────────────────────────+
| - Teknologi: Blade + Alpine + Tailwind| - Teknologi: Filament Admin (TALL)   |
| - Layout Responsif (Mobile/Desktop)  | - Livewire Reactive Components       |
| - LocalStorage RFQ Cart Tray/Drawer  | - Kanban Board Drag-and-Drop         |
| - Fast Live Search (No page reload)  | - Form Builder & Table Builder       |
| - Modal Pop-up Single Inquiry        | - Role-based View (Super Admin/Sales/|
| - Dynamic Open Graph Social Previews |   Freelance Agent Portal)            |
+──────────────────────────────────────+──────────────────────────────────────+
```

---

## 2. Antarmuka Perangkat Keras & Infrastruktur (Hostinger Premium Web Hosting)

Sistem dioptimalkan secara ketat untuk berjalan secara stabil pada **Paket Hostinger Premium Web Hosting (Shared Environment)** dengan parameter batasan dan solusi rekayasa berikut:

```
+----------------------------------------------------------------------------------------------------+
|                      SPESIFIKASI & MITIGASI HOSTINGER PREMIUM WEB HOSTING                          |
+--------------------------+------------------------------------+------------------------------------+
| Parameter Server         | Batasan Hostinger Premium          | Solusi Rekayasa Sistem (Laravel)   |
+--------------------------+------------------------------------+------------------------------------+
| 1. Memori & CPU          | RAM 512 MB, 1 vCPU Shared          | Gunakan arsitektur Monolith ringan;|
|                          | Max Execution Time 60-120 detik.   | batch chunking 100 baris di Excel. |
+--------------------------+------------------------------------+------------------------------------+
| 2. Background Process    | Ketiadaan Daemon / Supervisor      | Laravel Scheduler Cron hPanel tiap |
|                          | (proses worker persisten di-kill). | menit: queue:work --stop-when-empty|
|                          |                                    | --max-time=50 (non-blocking).      |
+--------------------------+------------------------------------+------------------------------------+
| 3. Inodes & Storage      | Kuota jumlah file terbatas (inodes)| Konversi otomatis WebP (maks 1200px|
|                          | dan kapasitas SSD terbagi.         | hemat disk & inodes hingga 70%).   |
+--------------------------+------------------------------------+------------------------------------+
| 4. Database I/O          | MySQL Max Connections: ~50 koneksi | RFQ Cart dikelola di browser       |
|                          | bersamaan.                         | (localStorage); 0 query I/O cart.  |
+--------------------------+------------------------------------+------------------------------------+
| 5. Web Server Engine     | LiteSpeed Web Server Enterprise    | Optimasi .htaccess rewrite rules & |
|                          | dengan dukungan HTTP/2 & HTTP/3.   | header caching LiteSpeed.          |
+--------------------------+------------------------------------+------------------------------------+
| 6. Pengiriman Email      | SMTP lokal shared IP rentan spam / | Wajib integrasi SMTP transaksional |
|                          | dibatasi kuota per jam.            | pihak ketiga (Resend/Brevo/Mailgun)|
+--------------------------+------------------------------------+------------------------------------+
| 7. Topologi Subdomain    | Document root kedua domain sama:   | Single Codebase, routing domain    |
|                          | /domains/domain.com/public_html/public| dipisah otomatis via Laravel Router.|
+--------------------------+------------------------------------+------------------------------------+
```

### 2.1 Konfigurasi Cron Job Scheduler di hPanel Hostinger
Cron job hPanel diset dengan frekuensi **Every Minute (`* * * * *`)**:
```bash
cd /home/uXXXX/domains/domain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### 2.2 Skrip Build & Aset Terkompilasi
Asset bundling (Tailwind CSS & JavaScript Alpine) dikompilasi secara lokal/CI via Vite (`npm run build`). Berkas hasil kompilasi di direktori `public/build` diunggah ke server tanpa perlu menjalankan Node.js runtime di server hosting.


---

## 3. Antarmuka Perangkat Lunak & Pustaka Dependensi (Software Interfaces)

| Komponen Perangkat Lunak | Versi Minimal | Fungsi / Peran dalam Sistem |
|:---|:---|:---|
| **PHP Runtime** | `^8.2` | Engine eksekusi kode backend Laravel. |
| **Laravel Framework** | `^11.0` | Core Application Framework (Monolith). |
| **MySQL Database** | `8.0` | Relational Database Management System (InnoDB Engine). |
| **Filament Admin Panel** | `^3.2` / `^4.0` | Back-office ERP UI & Resource CRUD Builder. |
| **Spatie Laravel Permission**| `^6.0` | Role-Based Access Control (RBAC) & Authorization. |
| **Barryvdh Laravel DomPDF** | `^2.1` / `^3.0` | Generator dokumen PDF resmi (Quotation & Surat Jalan). |
| **Maatwebsite Laravel Excel**| `^3.1` | Pemrosesan import massal data produk ribuan SKU. |
| **Intervention Image** | `^3.0` | Konversi otomatis ke format WebP dan pembatasan resolusi foto. |

---

## 4. Antarmuka Komunikasi & Protokol Jaringan (Communication Interfaces)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       PROTOKOL KOMUNIKASI SISTEM                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. HTTPS (Port 443): Enkripsi TLS 1.3 untuk seluruh transfer data web & ERP │
│ 2. SMTP Transaksional (Port 587/TLS): Kirim email alert lead & penawaran    │
│    melalui driver pihak ketiga (Resend / Brevo / Mailgun)                   │
│ 3. Google Tag Manager (GTM) dataLayer: Komunikasi event analitik B2B        │
│ 4. WhatsApp Click-to-Chat URI Scheme: Integrasi outbound ke wa.me / api     │
└─────────────────────────────────────────────────────────────────────────────┘
```
