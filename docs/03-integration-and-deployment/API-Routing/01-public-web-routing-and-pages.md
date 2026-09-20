# 01. Spesifikasi Rute Web Publik & Halaman Frontend
## API & Routing Specification Suite (API-Routing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-API-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Backend Lead | Draf Awal | Rute dasar web publik (Beranda, Katalog, Compro, Kontak) |
| `1.1.0` | 03 Sep 2026 | Web Architect | Ditinjau | Penambahan rute `/compare`, `/lacak-rfq`, dan `/gabung-mitra` |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penegasan isolasi domain root `bimabergawa.com` & cache response |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas spesifik rute publik, SEO slugs, & zero emoji standard |

---

## 1. Konfigurasi Rute Domain Publik (`bimabergawa.com`)

Seluruh rute publik didefinisikan dalam berkas `routes/web.php` dan dilindungi oleh middleware group `web` (Sesi Cookie, CSRF Protection, Encrypted Cookies):

```php
Route::domain(config('app.public_domain', 'bimabergawa.com'))->group(function () {
    // 1. Profil Perusahaan & Konten Statis
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact/send', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')->name('contact.send');

    // 2. Direktori Brand & Showcases
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/brands/{slug}', [BrandController::class, 'show'])->name('brands.show');

    // 3. Showcase Katalog Produk (100% Zero Price)
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('/compare', [CatalogController::class, 'compare'])->name('catalog.compare');

    // 4. Wawasan, Edukasi Industri & Berita
    Route::get('/insights', [ArticleController::class, 'index'])->name('insights.index');
    Route::get('/insights/{slug}', [ArticleController::class, 'show'])->name('insights.show');

    // 5. Permintaan Penawaran (RFQ)
    Route::get('/rfq', [RfqController::class, 'index'])->name('rfq.index');
    Route::post('/rfq/submit', [RfqController::class, 'storeBulk'])
        ->middleware('throttle:10,1')->name('rfq.submit');
    Route::post('/rfq/single', [RfqController::class, 'storeSingle'])
        ->middleware('throttle:10,1')->name('rfq.single');

    // 6. Pelacakan Status Pengadaan & Ekspedisi
    Route::get('/lacak-rfq', [TrackingController::class, 'index'])->name('tracking.index');
    Route::post('/lacak-rfq/search', [TrackingController::class, 'search'])
        ->middleware('throttle:15,1')->name('tracking.search');

    // 7. Pendaftaran Mitra Freelance Sales
    Route::get('/gabung-mitra', [PartnerController::class, 'create'])->name('partner.create');
    Route::post('/gabung-mitra/apply', [PartnerController::class, 'store'])
        ->middleware('throttle:5,1')->name('partner.store');

    // 8. Feed SEO Sitemap XML
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])
        ->middleware('cache.headers:public;max_age=86400;etag')->name('sitemap');
});
```

---

## 2. Matriks Rinci Parameter & Respons Halaman Web

| URI Path | HTTP Verb | Parameter URL / Query String | View Blade yang Dirender | Strategi Cache / Optimasi |
| :--- | :--- | :--- | :--- | :--- |
| `/` | `GET` | - | `pages.home` | Dynamic Blade (Data Featured di-cache 1 jam) |
| `/about` | `GET` | - | `pages.about` | Static Cached View |
| `/contact` | `GET` | - | `pages.contact` | Form Blade dengan Google Maps Embed |
| `/brands` | `GET` | - | `pages.brands.index` | Cache Query `Brand::active()` |
| `/brands/{slug}` | `GET` | `slug` (String, e.g. `ebara`) | `pages.brands.show` | Eager Loading `products_count` |
| `/catalog` | `GET` | `brand`, `category`, `search`, `page` | `pages.catalog.index` | Livewire/Alpine Faceted Filter |
| `/catalog/{slug}` | `GET` | `slug` (String, e.g. `fsha-65-50-200`) | `pages.catalog.show` | Model Route Binding `Product::whereSlug()` |
| `/compare` | `GET` | `items` (Array ID, e.g. `104,210`) | `pages.catalog.compare` | Validasi maksimal 4 produk sejenis |
| `/insights` | `GET` | `category`, `page` | `pages.insights.index` | Paginate 9 artikel per halaman |
| `/insights/{slug}`| `GET` | `slug` (String, e.g. `efisiensi-pompa`) | `pages.insights.show` | SEO JSON-LD Article Schema |
| `/rfq` | `GET` | - | `pages.rfq.index` | Alpine.js reactive state dari `localStorage` |
| `/lacak-rfq` | `GET` | `ref` (Opsional, e.g. `PBB/INQ/2026/09/0012`) | `pages.tracking.index` | Lookup status pesanan & resi pengiriman |
| `/gabung-mitra` | `GET` | `ref` (Kode referral opsional) | `pages.partner.create` | Form upload KTP & rekening bank |

---

## 3. Aturan Struktur URL & SEO Slug Policy

1. **Standardisasi Huruf Kecil & Tanda Hubung (Hyphenated Slugs):**
   - Seluruh URL produk menggunakan format: `/catalog/{brand-slug}-{model-number-slug}` (misal: `/catalog/ebara-fsha-65-50-200`).
   - Seluruh URL artikel menggunakan format: `/insights/{kategori}/{judul-artikel}`.
2. **Kanonikal URL & Penanganan Trailing Slash:**
   - URL canonical wajib disertakan pada tag `<link rel="canonical" href="..." />` di setiap header halaman untuk mencegah penalti duplikasi konten di Google.
3. **Penyusunan Sitemap Otomatis (`/sitemap.xml`):**
   - Menghasilkan daftar URL terkini untuk seluruh:
     - Halaman statis utama (Priority: `1.0`, ChangeFreq: `daily`).
     - Seluruh brand prinsipal aktif (Priority: `0.9`, ChangeFreq: `weekly`).
     - Seluruh SKU katalog produk (Priority: `0.8`, ChangeFreq: `weekly`).
     - Seluruh artikel wawasan industri (Priority: `0.7`, ChangeFreq: `monthly`).
