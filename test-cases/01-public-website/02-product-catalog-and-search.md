# Test Cases: Katalog Produk Tanpa Harga, Multi-Filter, & Pencarian SKU
## Domain 01: Public Website (bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-PUB-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Engineer | Draf Awal | Pengujian katalog produk & pencarian teks dasar |
| `1.1.0` | 03 Sep 2026 | Test Automation Engineer | Ditinjau | Penambahan uji kebijakan Zero-Price & multi-filter brand/kategori |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan pengujian keamanan injeksi XSS/SQLi pada search bar |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-02` (Kebijakan Zero-Price B2B & Spesifikasi Showcase Produk)
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-CAT-01 s.d. FR-CAT-08)
  - `DOC-PBB-SDD-05` (Arsitektur State Reaktif Alpine.js & Query Scopes)
  - `DOC-PBB-UIUX-02` (Wireframe Katalog, Detail SKU, & Grid Produk Monokrom)
- **Modul Sistem:** Modul Showcase, Filter, & Pencarian Katalog Produk
- **Target Kelas / Controller / Service:**
  - `App\Http\Controllers\CatalogController`
  - `App\Services\CatalogFilterService`
  - `App\Models\Product`, `App\Models\Brand`, `App\Models\Category`
- **Target Method:** `index()`, `show()`, `downloadDatasheet()`, `filterByBrand()`, `filterByCategory()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-PUB-CAT-001`** | Positif | Penegakan Kebijakan Zero-Price pada Grid Katalog | Produk aktif tersedia di database | Akses `GET /katalog` | Tidak ada angka nominal harga (Rp 0 atau angka rupiah). Tombol aksi berlabel "Minta Penawaran" / "+ Tambah ke RFQ". | Not Executed |
| **`TC-PUB-CAT-002`** | Positif | Penegakan Kebijakan Zero-Price pada Halaman Detail SKU | Produk aktif tersedia di database | Akses `GET /katalog/{slug}` | Halaman detail memuat spesifikasi lengkap, datasheet, tombol RFQ & WA, tanpa angka harga jual maupun HPP di seluruh DOM/JavaScript. | Not Executed |
| **`TC-PUB-CAT-003`** | Positif | Pencarian Produk Berdasarkan Nomor SKU Persis (*Exact Match*) | Master produk dengan SKU `SCH-MCB-16A` aktif | Input search query: `SCH-MCB-16A` | Produk dengan SKU `SCH-MCB-16A` muncul tepat di urutan pertama hasil pencarian. | Not Executed |
| **`TC-PUB-CAT-004`** | Positif | Pencarian Produk Berdasarkan Kata Kunci Parsial (*Fuzzy Partial Match*) | Produk "Schneider Circuit Breaker 16A" aktif | Input search query: `circuit breaker` | Semua produk yang memuat kata kunci pada nama, deskripsi, atau nomor seri muncul pada hasil pencarian. | Not Executed |
| **`TC-PUB-CAT-005`** | Positif | Filter Katalog Berdasarkan Brand Tunggal | Master brand aktif di DB | Akses `GET /katalog?brand=schneider-electric` | Hanya produk dari brand Schneider Electric yang ditampilkan, breadcrumb dan filter checkbox brand terpilih secara otomatis. | Not Executed |
| **`TC-PUB-CAT-006`** | Positif | Filter Katalog Berdasarkan Kategori Induk & Subkategori | Kategori hierarkis terisi di DB | Akses `GET /katalog?category=circuit-breakers` | Produk di bawah kategori Circuit Breakers dan seluruh sub-kategorinya ditampilkan dengan benar. | Not Executed |
| **`TC-PUB-CAT-007`** | Positif | Multi-Filter Kombinasi (Brand + Kategori + Rentang Spesifikasi) | Banyak produk dengan berbagai brand/kategori | Query: `?brand=schneider&category=circuit-breakers` | Daftar hanya memuat produk yang memenuhi kedua filter (operasi logika AND), counter total produk terfilter sesuai jumlah riil. | Not Executed |
| **`TC-PUB-CAT-008`** | Positif | Paginasi Katalog Produk (12 Item / Halaman) & Query Preservation | Database memiliki > 25 produk | Akses `GET /katalog?brand=schneider&page=2` | Halaman ke-2 memuat produk item 13 s.d. 24, parameter filter `brand=schneider` tetap bertahan pada tombol tautan paginasi ke halaman 1, 2, 3. | Not Executed |
| **`TC-PUB-CAT-009`** | Positif | Pengurutan Produk (*Sorting*: Terbaru, Abjad A-Z, Populer) | Daftar katalog dimuat | Pilih dropdown sort: `sort=name_asc` | Daftar produk terurut secara abjad nama produk dari A ke Z tanpa me-reset parameter filter yang sedang aktif. | Not Executed |
| **`TC-PUB-CAT-010`** | Positif | Unduh Berkas Datasheet Teknis PDF Resmi | Produk memiliki file `datasheet_path` | Klik tombol "Unduh Datasheet" pada detail produk | File PDF diunduh dengan header `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="datasheet-SCH-MCB-16A.pdf"`. | Not Executed |
| **`TC-PUB-CAT-011`** | Negatif | Pencarian Produk dengan Kata Kunci yang Tidak Ada | Database produk terisi normal | Input search query: `xyznotfoundproduct9999` | Menampilkan antarmuka state kosong: "Produk tidak ditemukan", tombol "Reset Filter", dan daftar rekomendasi brand populer. | Not Executed |
| **`TC-PUB-CAT-012`** | Negatif | Akses Detail Produk dengan Slug Tidak Valid / Produk Draf | Produk berstatus `is_active = false` atau slug acak | Akses `GET /katalog/produk-tidak-aktif-atau-salah` | HTTP 404 Not Found. Produk draf/non-aktif tidak dapat diakses langsung oleh publik. | Not Executed |
| **`TC-PUB-CAT-013`** | Keamanan | Pencegahan SQL Injection pada Input Pencarian & Filter | Form pencarian aktif | Input: `' OR '1'='1' UNION SELECT * FROM users --` | Query diproses secara aman menggunakan Eloquent Parameter Binding, tidak ada data bocor, server mengembalikan 0 hasil pencarian dengan aman. | Not Executed |
| **`TC-PUB-CAT-014`** | Keamanan | Pencegahan Cross-Site Scripting (XSS) pada Query Parameter | Search bar & URL query | Akses: `/katalog?q=<script>alert("XSS")</script>` | Script di-escape menjadi entitas HTML `&lt;script&gt;` di dalam DOM, tidak ada script yang dieksekusi di browser. | Not Executed |
| **`TC-PUB-CAT-015`** | Batas Nilai | Penanganan Produk Tanpa Gambar (*Fallback SVG Placeholder*) | Produk dibuat dengan `image_path = null` | Akses `GET /katalog/{slug}` | Sistem merender placeholder grafis SVG monokrom logo PBB tanpa memunculkan icon gambar rusak (*broken image icon*). | Not Executed |
| **`TC-PUB-CAT-016`** | SEO & Canonical | Verifikasi Tag Canonical URL & Schema.org Product Schema | Halaman detail produk dimuat | Inspeksi tag `<head>` pada `GET /katalog/{slug}` | Memuat `<link rel="canonical" href="https://bimabergawa.com/katalog/{slug}">`, metadata OpenGraph gambar produk WebP, dan JSON-LD `Product`. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-PUB-CAT-001 (Verifikasi Zero-Price Policy)

1. **Langkah 1:** Buka peramban ke alamat `https://bimabergawa.com/katalog`.
2. **Langkah 2:** Buka Developer Tools (`F12`) -> Tab Console.
3. **Langkah 3:** Jalankan perintah JavaScript:
   ```javascript
   document.querySelectorAll('*').forEach(el => {
       if (el.innerText && /Rp\s*\d+/i.test(el.innerText)) console.error('Bocoran Harga Ditemukan:', el);
   });
   ```
4. **Verifikasi Teknis:**
   - Console tidak mengeluarkan error sama sekali.
   - Seluruh kartu produk hanya menampilkan Nama, SKU, Badge Brand, dan Tombol RFQ.

### 3.2 Prosedur Uji TC-PUB-CAT-013 (Uji Penetrasi SQL Injection pada Filter)

1. **Perintah Eksekusi:**
   ```bash
   curl -s -o /dev/null -w "%{http_code}" "https://bimabergawa.com/katalog?brand=schneider%27%20OR%201=1--"
   ```
2. **Verifikasi Teknis:**
   - Server mengembalikan status code `200 OK` (halaman normal tanpa SQL Exception) atau `404 Not Found`.
   - Log aplikasi `storage/logs/laravel.log` tidak mencatat syntax error MySQL.
