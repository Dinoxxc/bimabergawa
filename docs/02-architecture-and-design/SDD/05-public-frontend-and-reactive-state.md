# 05. Frontend Publik & State Reaktif (Public Frontend & Reactive State)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-05` |
| **Versi Dokumen** | 1.6.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Frontend Architect | Draf Awal | Arsitektur komponen Blade, Tailwind CSS, & Alpine.js state store |
| `1.1.0` | 03 Sep 2026 | UI/UX Lead | Ditinjau | Penambahan state manager untuk keranjang RFQ (`Alpine.store('rfqCart')`) & modal bulk paste |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Optimasi client-side caching & synchronisation state perbandingan produk |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.4.0` | 20 Sep 2026 | Security & Frontend Architect | Disetujui | Penambahan alur 4 lapis keamanan pelacakan publik: Two-Key Verification, Temporary Signed URL, data masking, & private PDF download |
| `1.5.0` | 01 Okt 2026 | Security & Frontend Architect | Disetujui | Penambahan Bab 6 Protokol Keamanan & Anti-Abuse Frontend Publik Fase 3 (CSRF, Honeypot antispam, Cloudflare Turnstile, IP Rate Limiter, isolasi state Alpine.js Zero-Price, & mitigasi scraping spek) |
| `1.6.0` | 02 Okt 2026 | Security & Frontend Architect | Disetujui | Penyelarasan urutan roadmap eksekusi: Domain Publik dialihkan menjadi Fase 4 (menggantikan Fase 3) setelah penyelesaian arsitektur Panel ERP |

---

## 1. Arsitektur Komponen Blade & Tailwind CSS

Struktur antarmuka publik (`domain.com`) menggunakan pendekatan komponen Blade yang modular dan efisien:

```
resources/views/
├── layouts/
│   ├── app.blade.php                   # Master Layout (Header, Footer, GTM, Meta SEO)
│   └── error.blade.php                 # Halaman Error Ramah Pengguna (404, 500)
├── components/
│   ├── header.blade.php                # Navigasi, Live Search, Badge RFQ & Compare
│   ├── footer.blade.php                # Info PT. Panca Bima Bergawa (PBB) & Rasuna Said
│   ├── product-card.blade.php          # Kartu Produk (Tanpa Harga, Tombol RFQ/Compare)
│   ├── rfq-floating-drawer.blade.php   # Drawer Keranjang Melayang Reaktif
│   ├── product-compare-modal.blade.php # Modal Pembanding Matriks Spek Berdampingan
│   └── anti-spam-shield.blade.php      # Honeypot, Time-Trap & Turnstile Widget
├── pages/
│   ├── home.blade.php                  # Beranda, Hero, Featured Brands & Products
│   ├── about.blade.php                 # Profil Korporat, Visi Misi, Legalitas
│   ├── catalog/
│   │   ├── index.blade.php             # Showcase Katalog, Faceted Filter, Grid/List
│   │   ├── show.blade.php              # Detail Produk, Galeri WebP, Spek, Datasheet
│   │   ├── brand.blade.php             # Programmatic Brand Landing Page
│   │   └── category.blade.php          # Programmatic Category Landing Page
│   ├── rfq/
│   │   └── review.blade.php            # Halaman Review RFQ & Bulk SKU Quick-Paste
│   ├── order-tracking.blade.php        # Pelacakan Resi Ekspedisi (/lacak-rfq)
│   ├── partner-registration.blade.php  # Pendaftaran Mitra Freelance (/gabung-mitra)
│   └── insights/
│       ├── index.blade.php             # Daftar Artikel & Tab Filter Kategori
│       └── show.blade.php              # Detail Artikel & Produk Terkait
└── pdf/
    ├── quotation.blade.php             # Template DomPDF Surat Penawaran Resmi
    └── delivery_order.blade.php        # Template DomPDF Surat Jalan Ekspedisi (No Price)
```

---

## 2. Manajemen State Reaktif Klien (Alpine.js Global Stores)

Seluruh interaktivitas keranjang penawaran dan pembanding produk dikelola pada browser klien (*Client-Side LocalStorage*) untuk memastikan **0 query ke database server saat pengunjung menjelajahi katalog**:

```javascript
document.addEventListener('alpine:init', () => {
    // 1. GLOBAL STORE: RFQ CART
    Alpine.store('rfqCart', {
        items: JSON.parse(localStorage.getItem('bima_rfq_cart') || '[]'),

        init() {
            this.$watch('items', (val) => {
                localStorage.setItem('bima_rfq_cart', JSON.stringify(val));
            });
        },

        get count() {
            return this.items.reduce((total, item) => total + (parseInt(item.quantity) || 1), 0);
        },

        addItem(product, qty = 1, customNotes = '') {
            const existing = this.items.find(i => i.id === product.id);
            if (existing) {
                existing.quantity = parseInt(existing.quantity) + parseInt(qty);
            } else {
                this.items.push({
                    id: product.id,
                    name: product.name,
                    model_number: product.model_number,
                    brand_name: product.brand_name,
                    image_url: product.image_url,
                    quantity: parseInt(qty),
                    custom_notes: customNotes
                });
            }
        },

        removeItem(productId) {
            this.items = this.items.filter(i => i.id !== productId);
        },

        clear() {
            this.items = [];
            localStorage.removeItem('bima_rfq_cart');
        }
    });

    // 2. GLOBAL STORE: PRODUCT COMPARE TRAY
    Alpine.store('compareTray', {
        products: JSON.parse(localStorage.getItem('bima_compare_tray') || '[]'),

        init() {
            this.$watch('products', (val) => {
                localStorage.setItem('bima_compare_tray', JSON.stringify(val));
            });
        },

        get count() {
            return this.products.length;
        },

        toggleProduct(product) {
            const index = this.products.findIndex(p => p.id === product.id);
            if (index > -1) {
                this.products.splice(index, 1);
            } else {
                if (this.products.length >= 4) {
                    alert('Maksimal membandingkan 4 produk sekaligus.');
                    return;
                }
                this.products.push(product);
            }
        },

        isInCompare(productId) {
            return this.products.some(p => p.id === productId);
        },

        clear() {
            this.products = [];
            localStorage.removeItem('bima_compare_tray');
        }
    });
});
```

---

## 3. Logika Pemrosesan Bulk SKU Quick-Paste (*Fast BoQ Ingestion*)

Memungkinkan pembeli procurement menempelkan (*copy-paste*) puluhan kode part number dari Excel ke dalam textarea pada halaman `/rfq`:

```javascript
function processBulkSkuPaste(rawText) {
    const lines = rawText.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);
    if (lines.length === 0) return;

    // Kirim array part number ke endpoint matching
    fetch('/api/v1/catalog/match-skus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ sku_list: lines })
    })
    .then(res => res.json())
    .then(data => {
        if (data.matched && data.matched.length > 0) {
            data.matched.forEach(prod => {
                Alpine.store('rfqCart').addItem(prod, prod.requested_qty || 1);
            });
            alert(`Berhasil memasukkan ${data.matched.length} produk ke keranjang penawaran.`);
        }
        if (data.unmatched && data.unmatched.length > 0) {
            alert(`Terdapat ${data.unmatched.length} SKU yang tidak ditemukan: ` + data.unmatched.join(', '));
        }
    });
}
```

---

## 4. Alur Pelacakan Resi Ekspedisi & Verifikasi Dokumen Publik (`/lacak-rfq`)

Halaman publik `/lacak-rfq` memfasilitasi pengecekan status pesanan dan pengiriman tanpa login dengan pengamanan ketat 4 lapis anti-IDOR:

```
                  ┌─────────────────────────────────────────────────────────┐
                  │              KLIEN / PEMBELI PROYEK B2B                 │
                  └────────────────────────────┬────────────────────────────┘
                                               │
                       ┌───────────────────────┴───────────────────────┐
                       ▼                                               ▼
         [ Jalur A: Tautan Resmi Email ]                 [ Jalur B: Form Manual /lacak-rfq ]
         • URL Temporary Signed Route                    • Form mewajibkan 2 parameter:
           HMAC-SHA256 (30 hari aktif)                     1. Nomor Dokumen (PBB/INQ/...)
         • Verifikasi otomatis via server                  2. Kontak PIC (No. WA / Email)
         • Langsung membuka detail transaksi             • Menolak 100% enumerasi nomor
                       │                                               │
                       └───────────────────────┬───────────────────────┘
                                               ▼
                         [ PENGECEKAN DATA & REDAKSI SENSITIF ]
                         • Data sensitif di-mask: Ahm** Fa*** (0812-****-7766)
                         • Menampilkan linimasa: Inkuiri -> Penawaran -> PO -> Kirim
                                               │
                                               ▼
                         [ AKSES UNDUH DOKUMEN FISIK PDF ]
                         • Berkas PDF tersimpan di disk privat (storage/app/private/)
                         • Tombol unduh menghasilkan One-Time Signed Token (15 menit)
                         • Mencegah perayapan search engine & direct public link sharing
```

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    TAMPILAN TIMELINE PELACAKAN                          │
├─────────────────────────────────────────────────────────────────────────┤
│ 1. Status Transaksi: Deal / Sales Order Terbit (PBB/SOR/2026/09/0012)   │
│ 2. Kontak Pemohon: PT. Petro Perkasa (PIC: Ahm** Fa***)                 │
│ 3. Status Operasional: Shipped (Dalam Pengiriman Ekspedisi)             │
│ 4. Vendor Ekspedisi: Baraka Cargo                                       │
│ 5. Dokumen Resmi: [ Unduh PDF Penawaran (Tautan Terverifikasi) ]        │
│ 6. Tombol Aksi: [ Lacak di Website Resmi Ekspedisi -> ]                 │
│    (Membuka URL https://barakacargo.co.id/tracking?resi=BRK-88129045)   │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Mesin Data Terstruktur SEO (JSON-LD Schemas)

Setiap halaman katalog menyematkan skema JSON-LD Schema.org secara otomatis untuk menjamin dominasi kata kunci komersial di Google:

```blade
{{-- JSON-LD Product Schema (100% Tanpa Harga Nominal) --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "{{ $product->name }}",
  "image": "{{ asset('storage/' . $product->images->first()?->image_path) }}",
  "description": "{{ $product->meta_description ?? strip_tags(Str::limit($product->description, 160)) }}",
  "sku": "{{ $product->model_number }}",
  "mpn": "{{ $product->model_number }}",
  "brand": {
    "@type": "Brand",
    "name": "{{ $product->brand->name }}"
  },
  "offers": {
    "@type": "Offer",
    "url": "{{ url()->current() }}",
    "priceCurrency": "IDR",
    "price": "0",
    "availability": "{{ $product->availability_status === 'ready_stock' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder' }}",
    "seller": {
      "@type": "Organization",
      "name": "PT. Panca Bima Bergawa (PBB)"
    }
  }
}
</script>
```

---

## 6. Protokol Keamanan & Anti-Abuse Frontend Publik (Fase 4 Hardening Matrix)

Untuk melindungi etalase publik (`bimabergawa.com`) dari scraping agresif, spam bot pada form inkuiri, pemalsuan permintaan (*Cross-Site Request Forgery*), dan kebocoran data komersial, implementasi frontend publik pada Fase 4 wajib mengadopsi 6 lapis pertahanan (*Defense-in-Depth*):

### 6.1 Proteksi CSRF Komprehensif (Cross-Site Request Forgery Guard)
- Seluruh formulir Blade (`<form>`) pada domain publik wajib menyertakan token `@csrf`.
- Permintaan AJAX / Fetch API di sisi klien (seperti submit keranjang RFQ dan tracking order) wajib menyertakan header `X-CSRF-TOKEN` yang diambil dari meta tag master layout:
  ```html
  <meta name="csrf-token" content="{{ csrf_token() }}">
  ```
- Kegagalan verifikasi token CSRF akan menghasilkan respon HTTP 419 (Page Expired) dan memicu pembaruan sesi otomatis tanpa mengekspos payload internal.

### 6.2 Mekanisme Dual-Anti-Spam: Honeypot & Time-Trap Shield
Untuk menangkal bot otomatis tanpa mengganggu pengalaman pengguna (*frictionless UX*):
1. **Honeypot Input Trap (`_hp_company`):**
   - Komponen formulir menyertakan input tersembunyi `_hp_company` menggunakan CSS tersembunyi (`opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;`).
   - Pengguna manusia normal tidak akan melihat atau mengisi kolom ini.
   - Jika kolom `_hp_company` terisi oleh bot pengisi form otomatis, backend via `PublicInquiryRequest` akan langsung menolak permintaan dengan status silent drop atau error validasi generik.
2. **Time-Trap Validation:**
   - Formulir mencatat timestamp awal saat halaman dimuat.
   - Pengiriman formulir yang terjadi dalam waktu kurang dari 3 detik sejak render halaman akan diklasifikasikan sebagai bot otomatis dan ditolak.

### 6.3 Verifikasi Bot Adaptif Cloudflare Turnstile CAPTCHA
- Pada formulir inkuiri massal, submit RFQ, dan pendaftaran mitra freelance, sistem mengintegrasikan widget **Cloudflare Turnstile** (non-intrusif, privasi tinggi, bebas tebak gambar).
- Backend memverifikasi token turnstile ke endpoint Cloudflare API (`https://challenges.cloudflare.com/turnstile/v0/siteverify`) sebelum memproses payload bisnis.

### 6.4 Pembatasan Laju Akses Bertingkat (Layered IP Rate Limiting)
Mencegah serangan brute-force, scraping katalog, dan spam telemetri WhatsApp:
- **Pencarian Katalog & Filter SKU:** Dibatasi maksimal 60 request per menit per alamat IP (`throttle:60,1`).
- **Submit RFQ & Pengunggahan BOQ:** Dibatasi maksimal 5 request per 10 menit per IP (`throttle:5,10`).
- **Telemetri Klik Tombol WhatsApp:** Dibatasi maksimal 10 event per menit per IP (`throttle:10,1`) guna mencegah manipulasi metrik lead CRM.
- **Pengecekan Pelacakan Dokumen (`/lacak-rfq`):** Dibatasi maksimal 10 request verifikasi per menit per IP (`throttle:10,1`) untuk mencegah brute-force nomor dokumen transaksi.

### 6.5 Isolasi Mutlak State Keranjang Alpine.js (Zero-Price Policy Store)
- Objek state `Alpine.store('rfqCart')` yang tersimpan pada `localStorage` hanya diperbolehkan menyimpan atribut non-harga:
  ```javascript
  {
    sku: "SEL001000001",
    name: "Schneider Electric MCB Domae 1P 6A",
    brand: "Schneider Electric",
    quantity: 10,
    unit: "PCS",
    notes: "Proyek Gedung A"
  }
  ```
- **Larangan Keras Atribut Finansial:** Dilarang keras menyematkan field harga (`price`, `nominal`, `cost_price`, `subtotal`, `discount`) pada state client, atribut `data-*` HTML, ataupun variabel JavaScript global.
- DOM inspection pada browser klien tidak akan pernah menemukan angka harga nominal atau margin dasar HPP.

### 6.6 Mitigasi Scraping Agresif pada Spesifikasi & Datasheet
- Dokumen teknis PDF / Datasheet produk disajikan menggunakan URL dinamis yang terproteksi referer valid domain `bimabergawa.com`.
- Direktori berkas privat (`storage/app/private/`) tidak dapat diakses langsung oleh web server publik.
- User-Agent dari bot scraper umum (Scrapy, Python-requests, cURL tanpa header) secara otomatis diarahkan ke throttling ketat atau CAPTCHA challenge.

