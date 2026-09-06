# 02. Wireframe & Tata Letak Web Publik
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | UI/UX Designer | Draf Awal | Wireframe layout Beranda, Katalog, Detail Produk, & Footer |
| `1.1.0` | 03 Sep 2026 | Information Architect | Ditinjau | Penambahan layout Company Profile Rasuna Said & Instagram social proof |
| `1.2.0` | 05 Sep 2026 | Principal Design Architect | Disetujui | Redesain ke layout Editorial Minimalist (Bold headings with period, 3-column value grid) |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Pembersihan total seluruh karakter emoji ke Icon formal dan penyelarasan copy B2B |

---

## 1. Navigasi Global & Header Minimalis (Global Header & Navigation)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [LOGO GEOMETRIS PBB]        Katalog   •   Brand Partner   •   Tentang Kami   •   Wawasan   •   Kontak  │
├───────────────────────────────────────────────────────────────────────────────────┬────────────────────┤
│ [Icon: Search] Cari part number, nomor model, atau spesifikasi...   [ Ctrl + K ]  │ [ CART RFQ (3) ]   │
└───────────────────────────────────────────────────────────────────────────────────┴────────────────────┘
```

- **Gaya Navigasi:** Huruf Sans-Serif bersih, warna hitam pekat (`#000000`) pada latar belakang putih murni (`#FFFFFF`) dengan garis pemisah tipis (*1px Hairline Border* `#E5E7EB`).
- **Cart RFQ Pill:** Tombol sudut tajam minimalis (*micro-radius 4px*) dengan latar belakang hitam pekat dan teks putih tebal.
- **Standar Ikon:** Seluruh visual indikator pencarian menggunakan `<x-lucide-search />` dan tas belanja `<x-lucide-shopping-bag />` murni tanpa emoji.

---

## 2. Wireframe Halaman Beranda (Homepage Layout - `/`)

Mengadaptasi tata letak bergaya editorial dengan tipografi berani, ruang kosong bernapas proporsional, dan susunan 3-kolom bersih:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [NAVBAR MINIMALIS]                                                                                     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ HERO SECTION (Spacious & Bold):                                                                        │
│                                                                                                        │
│ Selamat datang di                                                                                      │
│ # Panca Bima Bergawa.                                                                                  │
│                                                                                                        │
│ Kami mendistribusikan perlengkapan mesin & otomasi industri multi-brand bergaransi resmi untuk        │
│ manufaktur, kontraktor EPC, dan pengadaan B2B di seluruh Indonesia.                                   │
│                                                                                                        │
│ [ [Tombol Solid] JELAJAHI KATALOG ]    [ <u>PELAJARI LEBIH LANJUT -></u> ]                             │
│                                                                                                        │
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ [ FOTO PRODUK TEKNIK ASLI: Workshop Mesin Industri / Instalasi Pompa & Inverter Nyata ]            │ │
│ │                                                            (Lokasi: Workshop Mitra - Jakarta)      │ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ SECTION 1: "Mulai pengadaan Anda."                                                                     │
│                                                                                                        │
│ Kebutuhan industri Anda, tantangan spesifikasi teknis di lapangan, dan kepastian suplai—mari kita      │
│ wujudkan efisiensi pengadaan terbaik untuk operasional pabrik Anda.                                    │
│                                                                                                        │
│ ┌───────────────────────────┐  ┌───────────────────────────┐  ┌───────────────────────────┐            │
│ │ [Icon: ShieldCheck]       │  │ [Icon: Wrench]            │  │ [Icon: Truck]             │            │
│ │ **100% Produk Orisinal.** │  │ **Dukungan Teknis.**      │  │ **Logistik Terpercaya.**  │            │
│ │ Garansi resmi manufaktur  │  │ Konsultasi spesifikasi    │  │ Pengiriman aman dengan    │            │
│ │ langsung dari pabrikan    │  │ bersama tim insinyur      │  │ resi pelacakan ekspedisi  │            │
│ │ terkemuka dunia.          │  │ berpengalaman kami.       │  │ ke seluruh Indonesia.     │            │
│ └───────────────────────────┘  └───────────────────────────┘  └───────────────────────────┘            │
│                                                                                                        │
│ [ <u>AMBIL LANGKAH PERTAMA HARI INI -></u> ]                                                           │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ SECTION 2: "Katalog presisi & formulir cepat." (Asymmetric Grid Layout)                                │
│                                                                                                        │
│ ┌───────────────────────────────────────────────────────────┐ ┌──────────────────────────────────────┐ │
│ │ **Kategori & Brand Pilihan.**                             │ │ ┌──────────────────────────────────┐ │ │
│ │ Solusi pengadaan komponen berstandar industri:            │ │ │ [LOGO PBB]                       │ │ │
│ │                                                           │ │ │ **Mulai permintaan penawaran.**  │ │ │
│ │ • **Pompa Industri & Sentrifugal**                        │ │ │ Kami membantu pengadaan presisi  │ │ │
│ │   Ebara End Suction, Multistage SS304, & Submersible      │ │ │ untuk perusahaan Anda.           │ │ │
│ │                                                           │ │ │                                  │ │ │
│ │ • **Inverter & Speed Drive (VFD)**                        │ │ │ [ Input Nama PIC / Pemohon     ] │ │ │
│ │   Schneider Altivar, Danfoss VLT, & Mitsubishi Drives     │ │ │ [ Input Email Kantor Perusahaan] │ │ │
│ │                                                           │ │ │ [ Input Nomor WhatsApp PIC     ] │ │ │
│ │ • **Sensor, PLC & Otomasi**                               │ │ │                                  │ │ │
│ │   Omron Proximity, Photoelectric, & Limit Switches        │ │ │ [x] Saya menyetujui privasi & S&K│ │ │
│ │                                                           │ │ │                                  │ │ │
│ │ • **Transmisi & Valve Mekanikal**                         │ │ │ [ [Tombol] AJUKAN PERMINTAAN ]   │ │ │
│ │   Electric Motors, Gearbox, & Gate/Ball Valves            │ │ └──────────────────────────────────┘ │ │
│ └───────────────────────────────────────────────────────────┘ └──────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ KUTIPAN FILOSOFI KORPORAT (Editorial Serif Italic Section):                                            │
│                                                                                                        │
│ **KOMITMEN KAMI**                                                                                      │
│ *"Kunci utama membangun kemitraan industri yang berkelanjutan adalah transparansi spesifikasi,         │
│ kecepatan respon komersial, dan keandalan purna jual yang jujur."*                                     │
│ — **Direksi PT. Panca Bima Bergawa**                                                                   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ MEGA FOOTER EDITORIAL (Clean Multi-Column Layout):                                                     │
│                                                                                                        │
│ [LOGO GEOMETRIS]                                                                                       │
│ PT. PANCA BIMA BERGAWA                                                                                 │
│ Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Kuningan, Setiabudi, Jakarta Selatan 12920 │
│                                                                                                        │
│ **TENTANG**          **PRODUK**             **LAYANAN**            **HUBUNGI KAMI**                    │
│ Tentang Perusahaan   Pompa Industri         Konsultasi Teknis      sales@bimabergawa.com               │
│ Legalitas & Sertif.  Inverter & VFD         Pelacakan Pengiriman   Hotline: +62 21-XXXX-XXXX           │
│ Mitra Agen Freelance Sensor & Otomasi       Permintaan BOQ Massal  WhatsApp B2B Support                │
│ Wawasan Industri     Valve & Transmisi      Unduh Brosur Katalog   Gedung Wirausaha Jaksel             │
│                                                                                                        │
│ © 2026 PT. PANCA BIMA BERGAWA — HAK CIPTA DILINDUNGI      SYARAT & KETENTUAN   |   KEBIJAKAN PRIVASI   │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Wireframe Halaman Katalog & Pencarian (`/catalog`)

Tampilan katalog mengusung gaya monokrom bersih dengan kartu produk berlatar belakang putih dan *hairline border*:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Header: Katalog Produk Industri                                                                        │
│ Pencarian: [ [Icon: Search] Ketik part number, model, atau brand... ]  Tampilan: [ Grid ] [ List Padat]│
├─────────────────────────┬──────────────────────────────────────────────────────────────────────────────┤
│ FILTER BERSIH (Clean):  │ DAFTAR MODEL PRODUK (Menampilkan 12 dari 1.450 SKU — Tanpa Nominal Harga)    │
│                         ├──────────────────────────────────────────────────────────────────────────────┤
│ Brand:                  │ ┌────────────────────────┐ ┌────────────────────────┐ ┌────────────────────┐ │
│ [x] Ebara               │ │ [Foto Produk Asli]     │ │ [Foto Produk Asli]     │ │ [Foto Produk Asli] │ │
│ [ ] Schneider Electric  │ │ **EBARA**              │ │ **SCHNEIDER**          │ │ **DANFOSS**        │ │
│ [ ] Danfoss             │ │ Model: FSHA 65-50-200  │ │ Model: ATV320U55N4B    │ │ Model: FC-051P4K0  │ │
│ [ ] Omron               │ │ Kategori: End Suction  │ │ Kategori: Inverter VFD │ │ Kategori: Micro VFD│ │
│                         │ │ Status: [Ready Stock]  │ │ Status: [Ready Stock]  │ │ Status: [Inden]    │ │
│ Kategori:               │ │ Spek: 50 m3/h, Head 45m│ │ Spek: 5.5 kW, 380V     │ │ Spek: 4.0 kW, IP20 │ │
│ [x] Pompa Industri      │ │                        │ │                        │ │                    │ │
│   [x] End Suction       │ │ [ + TAMBAH KE RFQ ]    │ │ [ + TAMBAH KE RFQ ]    │ │ [ + TAMBAH KE RFQ ]│ │
│   [ ] Multistage        │ │ [ <u>Bandingkan Spek</u> ]│ │ [ <u>Bandingkan Spek</u> ]│ │ [ <u>Bandingkan</u> ] │ │
│                         │ └────────────────────────┘ └────────────────────────┘ └────────────────────┘ │
│ Ketersediaan:           │                                                                              │
│ [x] Ready Stock         │ Halaman:  [ 1 ]   [ 2 ]   [ 3 ]   [ 4 ] ... [ 120 ]                          │
└─────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Wireframe Halaman Detail Produk (`/catalog/{slug}`)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Navigasi: Beranda  /  Katalog  /  Pompa Industri  /  Ebara  /  **FSHA 65-50-200**                      │
├──────────────────────────────────────┬─────────────────────────────────────────────────────────────────┤
│ GALERI FOTO MONOKROM / BERSIH:       │ DETAIL SPESIFIKASI & AKSI PENGADAAN:                            │
│ ┌──────────────────────────────────┐ │                                                                 │
│ │                                  │ │ **EBARA INDUSTRIAL.**                                           │
│ │                                  │ │ # Model FSHA 65-50-200                                          │
│ │        [ FOTO WEBP BERSIH ]      │ │ Part Number: `FSHA-65-50-200-CI`   [ [Icon: Copy] <u>Salin</u> ]│
│ │        (Latar Belakang Putih)    │ │ Ketersediaan: **[Status: Ready Stock Gudang Jakarta]**          │
│ │                                  │ ├─────────────────────────────────────────────────────────────────┤
│ ├──────────────────────────────────┤ │ ESTIMASI KEBUTUHAN UNIT:                                        │
│ │ [Thumb 1] [Thumb 2] [Dimensi]    │ │ Jumlah Unit : [ - ] [ 2 ] [ + ] Unit                            │
│ └──────────────────────────────────┘ │ Catatan Spek : [ Input kebutuhan khusus misal Flange ANSI 150   ]│
│                                      │                                                                 │
│                                      │ [ [Tombol Solid] TAMBAHKAN KE KERANJANG RFQ ]                    │
│                                      │ [ <u>[Icon: Phone] KONSULTASI INSTAN VIA WHATSAPP B2B -></u> ]   │
│                                      │ [ <u>[Icon: Columns] MASUKKAN KE MATRIKS PERBANDINGAN -></u> ]   │
├──────────────────────────────────────┴─────────────────────────────────────────────────────────────────┤
│ TABULASI SPESIFIKASI TEKNIS:                                                                           │
│ [ **Lembar Spesifikasi Lengkap** ]   |   [ Unduh Datasheet Resmi (PDF) ]   |   [ Aksesoris Pendukung ] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TABEL DATA TEKNIS:                                                                                     │
│ • Kapasitas Aliran (Flow Rate) : 50 m3/h @ 2900 RPM        • Suhu Operasional Maks : 100°C             │
│ • Total Head Dinamis           : 45 Meter                  • Material Casing       : Cast Iron FC200   │
│ • Daya Motor Penggerak         : 11 kW (15 HP) 3-Phase     • Standar Flange Pipa   : DIN / ANSI        │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
