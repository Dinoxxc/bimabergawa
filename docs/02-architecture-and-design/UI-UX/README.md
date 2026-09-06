# UI/UX Design & Wireframe Specification (UI-UX) - Master Index
## Proyek: B2B Multi-Brand Product Showcase & Internal Commercial ERP

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-IDX` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | UI/UX Designer | Draf Awal | Inisialisasi wireframe beranda, katalog, floating drawer, & palet industrial |
| `1.1.0` | 03 Sep 2026 | Design Lead | Ditinjau | Penambahan layout perbandingan produk 4 item & UX Quotation Builder |
| `1.2.0` | 05 Sep 2026 | Principal System Architect & UI Lead | Disetujui | Kalibrasi total gaya High-Contrast Monochrome Minimalist (Authentik/StudioPress style) |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Penegasan Standar Anti-AI Design, eliminasi total karakter emoji, & standardisasi icon formal |

---

## 1. Filosofi & Visi Desain Antarmuka (Design Philosophy)

Sistem antarmuka dirancang mengadopsi estetika **High-Contrast Monochrome Minimalist & Editorial Swiss** (terinspirasi dari style StudioPress / Authentik):

1. **Monochrome Clarity & Bold Display:** Didominasi kontras murni hitam pekat (`#000000`) dan putih bersih (`#FFFFFF`) dengan aksen abu-abu netral (`#F4F4F5`), tipografi *Grotesk Display* tegas berakhiran tanda titik, dan tautan teks minimalis bergaris bawah (`->`).
2. **Efisiensi Navigasi B2B (*Zero-Friction Discovery*):** Pembeli korporat dan insinyur lapangan dapat menemukan nomor part number/model spesifik dalam waktu $< 3\text{ detik}$, membandingkan spesifikasi berdampingan, dan meminta penawaran tanpa registrasi akun.
3. **Kerahasiaan Harga Mutlak (*Pure Commercial Ingestion*):** 100% antarmuka publik beroperasi tanpa memuat nominal harga untuk mendukung model penetapan harga khusus (*high-ticket project pricing*).
4. **Kejelasan Operasional Internal (*High-Velocity ERP*):** Panel admin Filament dioptimalkan untuk meminimalkan beban ketik sales dengan papan Kanban interaktif, kalkulator margin laba otomatis, dan pembuatan PDF resmi ber-Kop Surat 1-klik.
5. **Standar Anti-AI Design & Human Copy:** Bebas dari gradien ungu-biru neon, bebas animasi mengambang berlebihan, bebas emoji, menggunakan foto produk nyata dan copy B2B solutif.

---

## 2. Struktur Dokumen Modular UI/UX Suite

Dokumen spesifikasi UI/UX dipecah ke dalam 4 berkas mendalam:

```
docs/02-architecture-and-design/UI-UX/
├── README.md                                             # [Dokumen Saat Ini] Ringkasan Eksekutif & Master Index
├── 01-design-system-and-tokens.md                        # Desain Sistem, Palet Warna Monokrom, Anti-AI Manifesto, & Tokens
├── 02-public-web-wireframes-and-layouts.md               # Wireframe Beranda, Katalog Tanpa Harga, Detail SKU, Brand & Compro
├── 03-rfq-drawer-comparison-and-tracking-ui.md           # UI Floating RFQ Drawer, Bulk SKU Quick-Paste, Compare Modal, & Resi
└── 04-internal-erp-filament-ux-specs.md                  # UX Panel ERP Filament, Kanban Stale Alert, Live Margin, & Portal Agen
```

---

## 3. Matriks Peta Alur Antarmuka Pengguna (User Interface Flow Map)

```mermaid
graph TD
    subgraph Public_Experience ["Antarmuka Web Publik (domain.com)"]
        H[Beranda & Hero] --> C[Katalog Multi-Brand /catalog]
        C -->|Pencarian / Filter| CP[Perbandingan Produk /compare]
        C -->|Klik SKU| D[Detail Produk /catalog/slug]
        D -->|Jalur Fast-Track| W[WhatsApp / Modal Single RFQ]
        D -->|Jalur Bulk| R[Floating RFQ Drawer]
        C -->|Copy-Paste Part Number| BQ[Bulk SKU Quick-Paste di /rfq]
        BQ --> R
        R --> RFQ_Review[Halaman Review & Submit /rfq]
        RFQ_Review --> Track[/lacak-rfq Cek Resi Ekspedisi]
        H --> Partner[/gabung-mitra Pendaftaran Freelance]
    end

    subgraph Internal_ERP_Experience ["Antarmuka ERP Filament (app.domain.com)"]
        Login[Passwordless Email OTP Login]
        Login -->|Staff / Direksi| Admin_Dashboard[Executive Dashboard]
        Admin_Dashboard --> Kanban[Inquiry Kanban Pipeline]
        Kanban --> Quote_Builder[Quotation Builder + Live Margin HPP]
        Quote_Builder --> SO_Issue[Sales Order & Partial DO Ekspedisi]
        
        Login -->|Freelance Sales| Agent_Portal[Portal Mandiri Freelance Agent]
        Agent_Portal --> Agent_Leads[Input Prospek & Share Link WA]
        Agent_Portal --> Agent_Payout[Pantau Status & Unduh Slip Komisi]
    end

    RFQ_Review -->|Ingest Data Real-Time| Kanban
```
