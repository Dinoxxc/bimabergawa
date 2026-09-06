# 02. Kebutuhan Fungsional: Showcase Katalog & Company Profile
## Software Requirements Specification (SRS)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SRS-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Functional Analyst | Draf Awal | Spesifikasi teknis FR-COMPRO & FR-CAT (Katalog multi-brand) |
| `1.1.0` | 03 Sep 2026 | Lead Frontend Engineer | Ditinjau | Penambahan FR perbandingan spesifikasi matriks & widget Instagram |
| `1.2.0` | 05 Sep 2026 | Engineering Lead | Disetujui | Validasi penegasan mutlak FR-CAT-03 (Aturan 100% Zero Price) |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Modul Company Profile (`FR-COMPRO`)

```
+----------------------------------------------------------------------------------------------------+
|                                  SPESIFIKASI TEKNIS FR-COMPRO                                      |
+--------------+--------------------------+----------------------------------------------------------+
| ID Kebutuhan | Nama Fungsi              | Logika Pemrosesan Teknis & Output                        |
+--------------+--------------------------+----------------------------------------------------------+
| FR-COMPRO-01 | Hero Slider & Value      | Mengambil data banner aktif, teks value proposition, dan |
|              | Proposition              | link CTA dari database/config, render via Blade layout.  |
| FR-COMPRO-02 | Why Choose Us & Pillars  | Menampilkan 4 pilar komitmen resmi distributor.          |
| FR-COMPRO-03 | Partner Brands Carousel  | Query tabel `brands` (is_active=1) dengan urutan         |
|              |                          | `sort_order`, render carousel logo interaktif.           |
| FR-COMPRO-04 | Category Highlights      | Query `categories` (parent_id is null) untuk kartu visual|
|              |                          | kelompok produk utama.                                   |
| FR-COMPRO-05 | Instagram Social Proof   | Render widget script Behold.so di sisi browser klien     |
|              |                          | secara asinkron (0 beban komputasi CPU host).            |
| FR-COMPRO-06 | General Contact Form     | Menerima POST /contact/send, validasi anti-spam Turnstile|
|              |                          | simpan ke `contact_messages` (status: 'unread').         |
| FR-COMPRO-07 | Full Dynamic Site CMS    | 100% teks hero, profil PT. Panca Bima Bergawa (PBB),     |
|              |                          | alamat kantor Gd. Wirausaha Rasuna Said Kav. C5 Jakarta, |
|              |                          | legalitas, link medsos diserap dari `company_settings`.  |
| FR-COMPRO-08 | Legal & TnC Dynamic Pages| Menyajikan halaman `/syarat-ketentuan`, `/kebijakan-     |
|              |                          | privasi`, `/ketentuan-garansi`, dan `/syarat-ketentuan-  |
|              |                          | mitra` secara dinamis dari database `company_settings`.  |
+--------------+--------------------------+----------------------------------------------------------+
```

---

## 2. Modul Showcase Katalog Produk (`FR-CAT`)

### FR-CAT-01: Live Search Multi-Parameter (Alpine.js)
- **Input:** String teks yang diketik pengguna pada `#catalog-search-input`.
- **Proses:** Alpine.js melakukan filtering lokal pada koleksi data produk atau melakukan *debounced AJAX request* (300ms delay) mencocokkan kolom: `name`, `model_number`, dan `brands.name`.
- **Output:** Menampilkan daftar kartu produk yang sesuai tanpa reload halaman penuh.

### FR-CAT-02: Faceted Multi-Filtering (Brand & Kategori Hierarki)
- **Input:** Array checkbox brand terpilih (`brand[]`) dan kategori terpilih (`category[]`).
- **Proses:** Query SQL Eloquent dengan klausa `whereIn('brand_id', $brands)` dan `whereIn('category_id', $categories)` memanfaatkan Composite Index `(is_published, category_id, brand_id)`.
- **Output:** Memperbarui grid produk secara reaktif.

### FR-CAT-03: Beralih Tampilan (Grid View vs List View)
- **Input:** Klik tombol `#toggle-view-grid` atau `#toggle-view-list`.
- **Proses:** Mengubah state Alpine `viewMode = 'grid' | 'list'` dan menyimpan preferensi di `localStorage.getItem('bima_catalog_view')`.
- **Output:** Mengubah struktur layout dari CSS Grid kartu visual ke tabel baris padat part number (B2B compact).

### FR-CAT-04: Detail Produk & Galeri WebP Interaktif
- **Input:** URL slug `/catalog/{slug}`.
- **Proses:** Eager loading relasi `brand`, `category`, `product_specifications`, dan `product_images`.
- **Output:** Render halaman detail memuat thumbnail gallery, status ketersediaan (*Ready/Indent*), dan tombol unduh PDF datasheet.

### FR-CAT-05: Hybrid SEO Auto-Fallback & Commercial Intent Title Engine
- **Input:** Objek `Product $product`.
- **Proses:**
  ```php
  $metaTitle = $product->meta_title ?? "Distributor {$product->brand->name} {$product->name} {$product->model_number} - Jual & Garansi Resmi | " . config('app.name');
  $metaDesc = $product->meta_description ?? "Cari {$product->name} {$product->model_number} original dari {$product->brand->name}? Hubungi distributor resmi " . config('app.name') . ". Ready stock, spesifikasi lengkap, datasheet PDF, dan penawaran harga B2B terbaik.";
  ```
- **Output:** Injeksi tag `<title>`, `<meta name="description">`, dan tag Open Graph (`og:title`, `og:description`, `og:image`, `og:url`) pada template Blade.

### FR-CAT-06: Product Specification Side-by-Side Comparison Tool
- **Input:** Array ID produk terpilih (`compare_ids[]`, maks 4 SKU) yang disimpan di Alpine store / `localStorage.getItem('bima_compare_list')`.
- **Proses:** Mengambil data relasi `product_specifications` untuk seluruh produk terpilih, melakukan pivot spesifikasi berdasarkan `spec_key`.
- **Output:** Menampilkan tabel matriks perbandingan teknis berdampingan (Head, Kapasitas, Voltase, Material, Dimensi, Brosur, Ketersediaan) dan tombol aksi `+ Tambah ke RFQ`.

### FR-CAT-07: Sticky Bottom Action Bar & Micro-Interactions (Mobile/Desktop)
- **Komponen Mobile (< 768px):** Bar aksi melayang di bagian bawah viewport browser ponsel dengan 2 tombol utama: `[ Chat WhatsApp Sales ]` dan `[ + Tambah ke RFQ ]` saat posisi scroll melewati tombol utama.
- **Micro-Interactions (Flying Cart):** Animasi mikro JavaScript berbasis CSS Translate yang menduplikasi elemen thumbnail produk bergerak menuju floating RFQ drawer saat pengguna menambahkan barang.

### FR-CAT-08: Programmatic Brand & Category Hub Landing Pages
- **Input:** Rute URL `/brand/{brand:slug}` dan `/category/{category:slug}`.
- **Proses:** Query produk aktif berdasarkan `brand_id` atau `category_id`, memuat deskripsi otorisasi prinsipal, banner resmi, dan brosur katalog gabungan.
- **Output:** Render landing page ber-H1 *"Distributor & Supplier Resmi [Brand] Indonesia"* dengan data terstruktur JSON-LD untuk mendominasi kata kunci brand pencarian Google.


---

## 3. Modul Wawasan & Berita (`FR-INSIGHTS`)

### FR-INSIGHTS-01: Navigasi Tab Kategori Terpadu
- **Input:** Filter tab parameter URL `/insights?category={slug}`.
- **Proses:** Query tabel `articles` berstatus `is_published = true` dan `published_at <= NOW()`.
- **Output:** Menampilkan grid kartu artikel dengan badge warna kategori yang sesuai.

### FR-INSIGHTS-02: Estimasi Waktu Baca (Reading Time Calculation)
- **Input:** Teks kolom `articles.content`.
- **Proses:** Menghitung jumlah kata (`str_word_count(strip_tags($content))`) dibagi rata-rata kecepatan membaca (200 kata/menit).
- **Output:** Menampilkan indikator waktu baca, misal: *" 4 menit baca"*.

### FR-INSIGHTS-03: Related Products Interlinking
- **Input:** Kategori artikel atau tag produk terkait.
- **Proses:** Mengambil 3 produk katalog teratas dari brand/kategori yang relevan dengan topik artikel.
- **Output:** Menampilkan kartu produk lengkap dengan tombol `+ Tambah ke RFQ` di bagian bawah artikel.


---

## 4. Modul Enterprise B2B SEO & Search Engine Dominance (`FR-SEO`)

### FR-SEO-01: Schema.org JSON-LD Structured Data Engine
- **Input:** Objek halaman saat ini (`Product`, `Brand`, `Article`, atau `Compro Home`).
- **Proses:** `SeoStructuredDataService` menghasilkan tag `<script type="application/ld+json">` yang memuat skema resmi:
  1. **Product & Offer Schema:** `name`, `image`, `description`, `sku`, `mpn`, `brand.name`, `offers.availability` (*InStock / PreOrder*), `offers.priceCurrency` ("IDR"), `offers.price` (atau "Request a Quote").
  2. **BreadcrumbList Schema:** `itemListElement` berurutan (`Home` -> `Brand` -> `Kategori` -> `Nama Produk`).
  3. **Organization & LocalBusiness Schema:** `name`, `legalName`, `url`, `logo`, `address`, `contactPoint` (B2B Sales hotline & WhatsApp), `sameAs` (media sosial).
  4. **Article Schema:** `headline`, `image`, `datePublished`, `dateModified`, `author`, `publisher`.
- **Output:** Injeksi markup JSON-LD valid pada `<head>` dokumen Blade yang lolos uji Google Rich Results Test.

### FR-SEO-02: Canonical Tagging & Crawl Budget Management
- **Input:** Request URL dengan parameter filter/sort atau halaman transaksional privat.
- **Proses:**
  - Halaman katalog publik: Menghasilkan `<link rel="canonical" href="...">` yang selalu mengarah ke URL dasar bersih (mencegah duplikasi konten akibat query string `?brand=...&sort=...`).
  - Halaman privat / transaksional (`/lacak-rfq`, `/keranjang-rfq`, `/gabung-mitra/sukses`, subdomain `app.domain.com`): Menginjeksikan `<meta name="robots" content="noindex, follow">` dan HTTP Header `X-Robots-Tag: noindex, nofollow`.
- **Output:** Kuota perayapan (*crawl budget*) bot Google 100% dialokasikan untuk ribuan halaman produk dan artikel.

### FR-SEO-03: Dynamic XML Sitemap & Robots.txt Engine
- **Input:** Rute `/sitemap.xml` dan `/robots.txt`.
- **Proses:** Generator sitemap membaca seluruh record aktif dari `Product` (priority: 0.9), `Brand` (priority: 0.8), `Category` (priority: 0.8), dan `Article` (priority: 0.7) dengan `lastmod` otomatis dari kolom `updated_at`.
- **Output:** Respon XML terkompresi `Content-Type: application/xml` dan `robots.txt` yang mendeklarasikan rute sitemap.
