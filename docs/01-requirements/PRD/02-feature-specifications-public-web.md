# 02. Spesifikasi Fitur Web Publik & RFQ Showcase
## Product Requirement Document (PRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-PRD-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Product Management Team | Draf Awal | Spesifikasi showcase katalog multi-brand, filter faceted, & RFQ cart |
| `1.1.0` | 03 Sep 2026 | Principal Frontend Architect | Ditinjau | Penambahan fitur perbandingan spesifikasi 4 item & quick-paste BOQ |
| `1.2.0` | 05 Sep 2026 | Product Design Lead | Disetujui | Penegasan aturan mutlak 100% Zero Price & banner suksesor model |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Arsitektur Informasi Platform Publik (`domain.com`)

Platform publik melayani seluruh interaksi calon pembeli korporat, memadukan profil kredibilitas distributor dengan katalog multi-brand interaktif.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                             STRUKTUR NAVIGASI UTAMA                         │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Beranda (/)                4. Wawasan & Berita (/insights)               │
│ 2. Tentang Kami (/about)      5. Kontak Kami (/contact)                     │
│ 3. Katalog Produk (/catalog)  6. Keranjang Penawaran (/rfq)                 │
│    └── Brand Khusus (/brands) └── Detail Produk (/catalog/{slug})           │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Rincian Fungsionalitas per Modul Publik

### 2.1 Modul 1: Halaman Beranda (`/`)
1. **Hero Slider Dinamis:** Banner visual resolusi tinggi dengan teks *value proposition* dan tombol CTA cepat (*"Jelajahi Katalog"* & *"Hubungi Sales"*).
2. **Pilar Nilai Keunggulan (Why Choose Us):** 4 kartu komitmen layanan: *100% Produk Original*, *Pengiriman Seluruh Indonesia*, *Dukungan Teknis Profesional*, dan *Garansi Ketersediaan Stok/Indent Cepat*.
3. **Featured Brands Carousel:** Slider barisan logo prinsipal resmi (Ebara, Schneider, Danfoss, Omron, dll.) yang dapat diklik langsung untuk memfilter produk brand terkait.
4. **Sorotan Kategori Unggulan (Category Highlights):** Grid kartu visual kelompok produk utama (Pompa Industri, Inverter & Drive, Sensor & Otomasi, Katup & Pipa).
5. **Featured Products Showcase:** Kartu preview 4–8 produk terlaris dengan badge *Featured*, model number, dan tombol `+ Tambah ke RFQ`.
6. **Wawasan & Berita Terkini (Insights Preview):** Cuplikan 3 konten terbaru dengan badge warna kategori pembeda (Biru: *Berita*, Hijau: *Tips Teknis*, Oranye: *Update Produk*).
7. **Social Proof (Instagram Feed Widget):** Integrasi widget pihak ketiga ([Behold.so](https://behold.so)) untuk menampilkan feed Instagram aktivitas kantor/gudang secara asinkron (0 beban CPU host).

### 2.2 Modul 2: Showcase Katalog Multi-Brand (`/catalog`)
> **PENTING - KEBIJAKAN PRIVASI HARGA:** Seluruh katalog publik beroperasi dengan **100% TANPA MENAMPILKAN HARGA (Rp 0 / Tanpa Nominal)**. Halaman hanya memuat spesifikasi teknis dan aksi minta penawaran resmi (RFQ).

1. **Faceted Multi-Filtering (Sidebar / Drawer Mobile):**
   - Filter Brand Prinsipal (multi-centang).
   - Filter Kategori & Sub-Kategori (struktur pohon hierarki).
   - Opsi pengurutan (*Terbaru*, *Nama A–Z*, *Nama Z–A*).
2. **Live Search Bar (Alpine.js):** Pencarian instan mencakup Nama Produk, Nomor Part/Model, dan Nama Brand tanpa me-reload seluruh halaman.
3. **Beralih Tampilan (Grid vs List View):**
   - **Grid View:** Kartu visual berfoto tajam WebP untuk eksplorasi visual.
   - **List View (B2B Compact):** Baris tabel padat SKU/Part Number untuk memudahkan pembeli procurement memindai banyak produk secara cepat.
4. **Fitur Pembanding Produk Berdampingan (*Side-by-Side Comparison Tool*):**
   - Tombol `+ Bandingkan` pada kartu produk (maksimal 4 produk dalam kategori yang sama).
   - Modal/Halaman perbandingan menampilkan matriks perbandingan parameter spesifikasi teknis secara sejajar tanpa memuat nominal harga.
5. **Paginasi Ringan & Canonical Tagging:** Navigasi halaman cepat yang dioptimalkan untuk ribuan SKU dengan tag `rel="canonical"` dinamis untuk mencegah penalti duplikasi konten dari Google.
6. **Programmatic Brand Hubs (`/brand/{brand-slug}`):** Landing page khusus per prinsipal (misal: `/brand/ebara`, `/brand/schneider`) dengan H1 *"Distributor & Supplier Resmi [Brand] Indonesia"*, deskripsi otorisasi brand, tombol download e-katalog gabungan, dan grid produk khusus brand tersebut untuk mendominasi kata kunci brand di Google.
7. **Programmatic Category Hubs (`/category/{category-slug}`):** Landing page terklaster per kelompok industri (misal: `/category/pompa-industri`, `/category/inverter-vfd`) dengan pengantar teknis dan filter otomatis.

### 2.3 Modul 3: Halaman Detail Produk (`/catalog/{slug}`)
1. **Galeri Media Interaktif:** Foto utama tajam berformat WebP dengan thumbnail gallery dan fitur zoom preview (disertai dimensi `width` dan `height` eksplisit untuk menjamin CLS = 0).
2. **Identitas Lengkap:** Nama produk, kode model/SKU prinsipal, logo brand terkait, dan status ketersediaan (*Ready Stock / Indent Principal*), tanpa memuat nominal harga.
3. **Tabel Spesifikasi Teknis Dinamis:** Tabel rapi parameter teknis yang diserap dari relasi `product_specifications`.
4. **Unduh Berkas Brosur / Datasheet Resmi:** Tombol download dokumen PDF asli dari prinsipal.
5. **Indikator Produk Suksesor (*Successor Model Indicator*):** Jika SKU merupakan model lama yang telah discontinued, halaman menampilkan badge rujukan ke model pengganti baru (`successor_product_id`).
6. **Dynamic Open Graph & Commercial Intent Meta Tags:** Tag meta Open Graph otomatis yang menghasilkan pratinjau gambar, judul, dan deskripsi rapi saat dibagikan ke WhatsApp atau LinkedIn, menggunakan formula judul komersial *"Distributor [Brand] [Nama Produk] [Model Number] - Jual & Garansi Resmi | [Nama Perusahaan]"*.
7. **Structured Data JSON-LD:** Otomatis menyematkan skema `Product`, `Offer` (dengan ketersediaan *InStock/PreOrder* tanpa harga), dan `BreadcrumbList` dari Schema.org.
8. **Rekomendasi Produk Terkait & Brand Hub Interlink:** 4 produk sejenis dari kategori atau brand yang sama serta tautan kembali ke Brand Hub untuk memperkuat *Topical Authority*.

### 2.4 Modul 4: Mekanisme Penangkapan Prospek Ganda (Dual-Track RFQ Engine)

```
                                  [ HALAMAN PRODUK ]
                                          │
                  ┌───────────────────────┴───────────────────────┐
                  ▼                                               ▼
         [ JALUR A: FAST TRACK ]                      [ JALUR B: BULK PROCUREMENT ]
                  │                                               │
        ┌─────────┴─────────┐                                     │
        ▼                   ▼                                     ▼
 [ Chat WhatsApp ]    [ Modal Tanya 1 Produk ]           [ + Tambah ke RFQ Cart ]
 (Teks Otomatis SKU)  (Form Pop-up Singkat)                       │
        │                   │                                     ▼
        ▼                   ▼                        [ Floating Drawer Melayang ]
 [ Chat Langsung ]    [ Ingest ke CRM: ]             (Disimpan di localStorage)
                      [ single_direct  ]                          │
                                                                  ▼
                                                      [ Halaman Review /rfq ]
                                                      (Input Qty, Catatan Spek,
                                                       & Bulk SKU Quick-Paste)
                                                                  │
                                                                  ▼
                                                      [ Submit Penawaran Resmi ]
                                                      (Guest Mode -> Ingest ke CRM:
                                                       bulk_cart + inquiry_items)
```

1. **Jalur A1 - Direct WhatsApp Click-to-Chat:** Tautan instan WhatsApp dengan pesan terformat otomatis:
   > *"Halo Tim Sales PT. Panca Bima Bergawa (PBB), saya tertarik dengan produk [Nama Produk] (Model: [SKU]). Mohon info ketersediaan dan penawaran harganya. Link: [URL Produk]"*
2. **Jalur A2 - Modal Tanya 1 Produk (Single Fast Track):** Pop-up form singkat (Nama PIC, Perusahaan, No. WA, Email, Qty, Catatan) yang langsung mengirim data ke database `inquiries` (`type = 'single_direct'`).
3. **Jalur B - Keranjang Penawaran (RFQ Cart) & Bulk Quick-Paste:**
   - Disimpan di *localStorage* browser (`bima_rfq_cart`).
   - Floating drawer dengan indikator jumlah item reaktif (`$store.rfqCart.count`).
   - **Fitur Bulk SKU Quick-Paste (Fast BoQ Ingestion):** Kotak input textarea di `/rfq` untuk copy-paste puluhan part number sekaligus; sistem otomatis mencocokkan SKU ke database dan memasukkan item ke keranjang.
   - **Unduh Bundle Datasheet ZIP:** Tombol 1-klik untuk mengunduh kompilasi PDF datasheet dari seluruh barang yang ada di keranjang penawaran.
   - Halaman `/rfq` memuat tabel ringkasan barang terpilih, input kuantiti unit, catatan spesifikasi kustom, dan form pengajuan penawaran resmi (*Guest Model* tanpa perlu registrasi).
   - Setelah sukses dikirim, `localStorage` otomatis dibersihkan dan nomor registrasi `PBB/INQ/YYYY/MM/XXXX` diterbitkan.
4. **Sistem Proteksi Anti-Spam Berlapis (Multi-Layer Form Shield):**
   - Seluruh formulir penawaran (Single Modal & Bulk RFQ) dilindungi oleh:
     - **Invisible Honeypot Field:** Field jebakan yang jika terisi oleh bot perayap otomatis membatalkan penyimpanan tanpa menimbulkan error visual.
     - **Submission Time-Trap:** Memblokir pengiriman formulir yang terjadi dalam waktu < 2 detik sejak form terbuka (indikasi kuat bot otomatis).
     - **Cloudflare Turnstile:** Widget keamanan non-intrusif tanpa captcha gambar yang merepotkan pembeli manusia.
     - **IP Throttle Middleware:** Batas maksimum 5 submit per menit per IP untuk mencegah flood/DDoS lead palsu.
5. **Unggah Dokumen BoQ & Spesifikasi Proyek (Bill of Quantities Attachment):**
   - Form RFQ menyediakan kolom opsional bagi pembeli korporat/kontraktor untuk melampirkan berkas spreadsheet atau PDF rincian tender/BoQ (Format: `.pdf`, `.xlsx`, `.xls`, `.jpg`, `.png`, Maks 5 MB).
   - Berkas tersimpan aman dan langsung dapat diunduh oleh staf sales pada panel detail prospek ERP.

### 2.5 Modul 5: Modul Terpadu Wawasan & Berita (`/insights`)
1. **Navigasi Tab Kategori:** `Semua` | `Berita Perusahaan` | `Update Brand & Produk` | `Panduan & Tips Teknis`.
2. **Halaman Baca Detail Artikel:** Format teks kaya (Rich Text), estimasi waktu membaca (*estimated reading time*), tombol bagikan ke media sosial (WhatsApp, LinkedIn, Twitter/X), dan **kartu produk terkait (*Related Products Interlink*)** di bagian bawah artikel.

### 2.6 Modul 6: Kontak & Lokasi (`/contact`)
1. **Informasi Alamat & Kantor Resmi:**
   - **Kantor Pusat:** PT. Panca Bima Bergawa (PBB), Gd. Wirausaha lt.1 Unit 104, Jalan Haji R. Rasuna Said Kavling C5, RT.3/RW.1, Karet, Kecamatan Setiabudi, Daerah Khusus Ibukota Jakarta 12920.
   - Gudang operasional & pusat logistik.
2. **Peta Interaktif:** Google Maps embed responsif lokasi Gd. Wirausaha Rasuna Said.
3. **Daftar Direct WhatsApp Sales:** Kontak langsung per wilayah atau per divisi produk.
4. **Formulir Pesan Umum:** Form kontak non-produk dengan proteksi anti-spam (Cloudflare Turnstile / reCAPTCHA v3) yang terhubung ke tabel `contact_messages` di ERP.

### 2.7 Modul 7: Mesin Pelacakan Atribusi Mendalam & Kode Referal (Deep Telemetry Engine)
Sistem melacak seluruh jejak rujukan secara otomatis tanpa membebani server:
1. **Penangkapan Parameter Digital Lengkap:**
   - Parameter UTM: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`.
   - Data Teknis: `referrer_url`, `landing_page`, `device_type` (Mobile/Desktop/Tablet), `ip_address`.
2. **Kode Referal Freelance Sales (`?ref=KODE`):**
   - Setiap freelance sales memiliki link referal unik (contoh: `domain.com/catalog?ref=dimas` atau `domain.com/catalog/pompa-ebara?ref=AG007`).
   - Middleware menangkap parameter `ref` dan menyimpannya di cookie browser selama **90 hari (*90-day attribution window*)**.
   - Saat pengunjung melakukan submit form RFQ (Single maupun Bulk Cart), sistem otomatis menautkan inquiry tersebut ke Freelance Sales yang bersangkutan sehingga hak komisi terkunci secara otomatis.

### 2.8 Modul 8: Integrasi Ekosistem Sosial Media Perusahaan (Social Media Hub)
Website publik terhubung erat dengan seluruh saluran media sosial resmi perusahaan untuk membangun kredibilitas dan mempermudah interaksi calon klien:
1. **Tautan Resmi di Header & Footer (Global Nav):**
   - Ikon interaktif terhubung langsung ke akun resmi: **LinkedIn Korporat**, **Instagram Bisnis**, **YouTube Channel**, **TikTok**, **Facebook Page**, dan **WhatsApp Business**.
2. **Live Social Proof Widget (Instagram Feed):**
   - Integrasi feed Instagram resmi di halaman beranda via widget ringan pihak ketiga ([Behold.so](https://behold.so)) untuk menampilkan aktivitas harian gudang, unboxing produk, dan dokumentasi pengiriman tanpa membebani kuota memori hosting.
3. **Tombol Bagikan Cepat (Dynamic Social Share Bar):**
   - Pada setiap halaman detail produk (`/catalog/{slug}`) dan artikel (`/insights/{slug}`), disediakan tombol 1-klik untuk membagikan tautan ke **WhatsApp**, **LinkedIn**, **Twitter/X**, dan **Facebook**.
   - Setiap share otomatis menghasilkan kartu pratinjau tajam (*Rich Open Graph Preview*) memuat gambar produk/artikel, judul resmi, dan nomor model.
4. **Floating WhatsApp Quick Button:**
   - Tombol mengambang di pojok kanan bawah layar untuk akses cepat chat langsung ke Customer Service / Sales Representative.
5. **Manajemen Akun Medsos via Panel ERP:**
   - Link akun media sosial dan nomor WhatsApp perusahaan dapat dikelola dan diubah secara dinamis oleh tim admin melalui menu pengaturan di panel Filament ERP tanpa perlu mengubah kode sumber (*hardcode*).

### 2.9 Modul 9: Enterprise B2B SEO Dominance Engine & Google Telemetry (GTM, GA4, GSC)
Sistem mengintegrasikan arsitektur SEO tingkat enterprise dan pelacakan telemetri Google untuk memastikan dominasi di Halaman 1 Google Search:
1. **Mesin Data Terstruktur Schema.org (JSON-LD Rich Snippets):**
   - **`Product` & `Offer` Schema:** Menandai nama SKU, MPN (Model Number), Brand prinsipal, spesifikasi teknis, dan ketersediaan (*InStock / PreOrder*) agar muncul sebagai rich product cards di SERP.
   - **`BreadcrumbList` Schema:** Menghasilkan jalur hierarki navigasi terstruktur (`Home > Brand > Kategori > Nama Produk`) di hasil pencarian Google.
   - **`Organization` & `LocalBusiness` Schema:** Menandai identitas PT, alamat resmi, nomor telepon PIC B2B, email resmi, dan tautan media sosial untuk memicu *Google Knowledge Panel*.
   - **`FAQPage` Schema:** Menampilkan *accordion FAQ* B2B langsung di hasil pencarian Google (misal: *"Berapa lama garansi resmi?"*, *"Apakah melampirkan Surat Keaslian Barang / COO?"*).
   - **`Article` Schema:** Menandai seluruh artikel wawasan industri (`/insights/{slug}`) dengan atribut author, publisher, datePublished, dan headline untuk mendominasi Google Discover.
2. **Formula Judul & Deskripsi Berbasis Commercial Search Intent:**
   - **Pola Title Tag Produk:** `Distributor [Brand] [Nama Produk] [Model Number] - Jual & Garansi Resmi | [Nama Perusahaan]`
   - **Pola Meta Description:** `Cari [Nama Produk] [Model Number] original dari [Brand]? Hubungi distributor resmi [Nama Perusahaan]. Ready stock, spesifikasi lengkap, datasheet PDF, dan penawaran harga B2B terbaik.`
   - **Pola Title Tag Brand Hub:** `Distributor & Supplier Resmi [Brand] Indonesia | [Nama Perusahaan]`
3. **Canonical Tagging & Manajemen Crawl Budget:**
   - Tag `rel="canonical"` disematkan di setiap halaman katalog untuk mencegah masalah *duplicate content* akibat parameter filter atau paginasi.
   - Header & Meta Tag `<meta name="robots" content="noindex, follow">` otomatis dipasang pada halaman privat/transaksional (`/lacak-rfq`, `/keranjang-rfq`, `/gabung-mitra/konfirmasi`, dan subdomain ERP `app.domain.com`) agar 100% *crawl budget* Google difokuskan ke halaman produk dan artikel.
4. **Google Tag Manager (GTM Container):**
   - Skrip GTM Container (`GTM-XXXXXXX`) disematkan di `<head>` dan `<noscript>` di `<body>` template Blade publik.
   - Pemicu (*Event Triggers*) pada JavaScript `dataLayer` untuk peristiwa B2B:
     - `view_item` (saat membuka detail produk)
     - `add_to_rfq_cart` (saat memasukkan barang ke keranjang)
     - `submit_single_rfq` & `submit_bulk_rfq` (konversi lead utama)
     - `click_whatsapp_sales` (klik tombol kontak WA)
5. **Google Analytics 4 (GA4):**
   - Terkoneksi melalui GTM (Measurement ID `G-XXXXXXXXXX`) untuk melacak demografi pengunjung, waktu di halaman, rasio pentalan (*bounce rate*), dan corong konversi penawaran (*conversion funnel*) tanpa membebani CPU server.
6. **Google Search Console (GSC) & Otomasi Sitemap XML:**
   - **Verifikasi GSC:** Kolom kode verifikasi Google Search Console (`google-site-verification`) dapat diinput langsung di panel pengaturan ERP.
   - **Dynamic XML Sitemap (`/sitemap.xml`):** Sistem otomatis men-generate peta situs XML yang memuat seluruh URL produk aktif, direktori brand hub (`/brand/{slug}`), kategori hub (`/category/{slug}`), dan artikel wawasan dengan atribut `lastmod` dan `priority` yang diperbarui secara *real-time*.
   - **Dynamic Robots.txt (`/robots.txt`):** Memberikan izin perayapan bagi bot mesin pencari ke rute publik dan mendeklarasikan lokasi file `sitemap.xml`.

### 2.10 Modul 10: Portal Pendaftaran Mitra Freelance Sales (`/gabung-mitra` atau `/freelance-registration`)
Halaman khusus bagi profesional teknik, kontraktor, dan konsultan industri luar untuk mendaftar sebagai mitra penjualan resmi:
1. **Landing Page Edukasi Program Kemitraan:**
   - Penjelasan skema komisi penjualan transparan per transaksi *deal*.
   - Portofolio brand prinsipal resmi yang dapat dipasarkan (Ebara, Schneider, Danfoss, Omron, dll.).
   - Syarat & Ketentuan kemitraan distributor.
2. **Formulir Pendaftaran & Kriteria Penyaringan (Screening Form):**
   - **Identitas Personal:** Nama Lengkap, Nomor WhatsApp, Alamat Email, Nomor NIK/KTP, dan Unggahan Foto KTP.
   - **Kriteria Kualifikasi & Jaringan Industri (Filter Perusahaan):**
     - Sektor Industri Utama yang Dikuasai (Pabrik F&B, Minyak & Gas, Pengolahan Air/WTP, HVAC/Gedung, Otomotif, dll.).
     - Pengalaman di Bidang Penjualan/Teknik Industri (Pilihan: < 1 tahun, 1–3 tahun, 3–5 tahun, > 5 tahun).
     - Profesi / Pekerjaan Saat Ini (Konsultan ME, Kontraktor EPC, Teknisi Pabrik, Freelance Sales).
     - Wilayah Cakupan Jaringan Pemasaran (Jabodetabek, Jawa Barat, Jawa Timur, Sumatera, Kalimantan, dll.).
     - Estimasi potensi jaringan klien industri yang dimiliki.
   - **Informasi Pencairan Komisi:** Nama Bank, Nomor Rekening, Nama Pemilik Rekening.
3. **Status Pengajuan:**
   - Setelah submit, calon mitra menerima nomor registrasi dan pemberitahuan bahwa data sedang dalam proses verifikasi tim manajemen (1x24 jam kerja).

### 2.11 Modul 11: Halaman Ketentuan Hukum & Kepatuhan Regulasi (Legal & Compliance Pages)
Website menyediakan halaman-halaman statis-dinamis resmi untuk transparansi tata kelola B2B dan kepatuhan hukum:
1. **Syarat & Ketentuan Layanan (`/syarat-ketentuan` atau `/terms-and-conditions`):**
   - Ketentuan permintaan penawaran harga (RFQ), sifat harga yang fluktuatif mengikuti kurs/prinsipal, masa berlaku penawaran (14 hari), dan hak cipta katalog produk.
2. **Kebijakan Privasi Data (`/kebijakan-privasi` atau `/privacy-policy`):**
   - Klausul kepatuhan UU Pelindungan Data Pribadi (UU PDP No. 27/2022) terkait pengumpulan data kontak PIC, email, nomor telepon, dan data dokumen KTP mitra freelance.
3. **Ketentuan Garansi & Purna Jual (`/ketentuan-garansi` atau `/warranty-policy`):**
   - Batasan garansi resmi pabrikan/prinsipal (misal: *Factory Defect* 12 bulan), syarat klaim garansi, dan pengecualian akibat kesalahan instalasi/kelalaian teknis.
4. **Ketentuan Kemitraan Freelance Sales (`/syarat-ketentuan-mitra`):**
   - Klausul hak dan kewajiban sales lepas, jadwal pembayaran komisi (setelah klien lunas), serta kode etik representasi brand.
5. **Integrasi Footer & Checkbox Persetujuan Form:**
   - Seluruh halaman legal tertaut rapi di *Footer* website.
   - Form RFQ dan Form Registrasi Mitra menyematkan klausul persetujuan: *"Dengan mengirimkan form ini, Anda menyetujui [Syarat & Ketentuan] dan [Kebijakan Privasi] kami."*

### 2.12 Modul 12: Fitur Produktivitas & Pengalaman Pengguna Pengadaan (B2B UX Enhancements)
1. **Pelacak Status Penawaran Mandiri (Self-Service RFQ Tracker - `/lacak-rfq`):**
   - Fasilitas bagi pembeli korporat (Guest) untuk memantau progres penawaran tanpa harus login.
   - **Input:** Nomor RFQ (contoh: `PBB/INQ/2026/09/0001`) dan Nomor WhatsApp pemohon (untuk otentikasi ringan).
   - **Output:** Tampilan visual timeline tahapan: *Inquiry Diterima -> Sedang Dihitung Tim Sales -> Penawaran Resmi Terbit (Tombol Unduh PDF) -> PO Disetujui (Deal) -> Pesanan Diproses / Indent -> Barang Dikirim (Nama Ekspedisi, Nomor Resi Resmi & Tautan Langsung Cek Resi Ekspedisi)*.
2. **Alat Pembanding Spesifikasi Teknis Side-by-Side (`/compare` / Floating Tray):**
   - Memungkinkan pembeli dan engineer membandingkan hingga 4 produk SKU sejenis dalam kategori yang sama.
   - Menampilkan tabel matriks komparasi atribut teknis dinamis (Voltase, Kapasitas, Head Pompa, Daya Motor, Material Bodi, Dimensi, Berat), status stok, dan tombol cepat `+ Tambah ke RFQ`.
      - `[ Chat Sales via WA ]` & `[ + Tambah ke RFQ ]`
4. **Indikator Interaktif Keranjang RFQ (Toast Feedback & Counter):**
   - Indikator badge angka pada keranjang bertambah secara instan disertai notifikasi Toast sukses (*"Produk berhasil ditambahkan ke daftar permintaan penawaran"*).


