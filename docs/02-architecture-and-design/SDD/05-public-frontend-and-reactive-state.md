# 05. Frontend Publik & State Reaktif (Public Frontend & Reactive State)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-05` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Frontend Architect | Draf Awal | Arsitektur komponen Blade, Tailwind CSS, & Alpine.js state store |
| `1.1.0` | 03 Sep 2026 | UI/UX Lead | Ditinjau | Penambahan state manager untuk keranjang RFQ (`Alpine.store('rfqCart')`) & modal bulk paste |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Optimasi client-side caching & synchronisation state perbandingan produk |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

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

## 4. Alur Pelacakan Resi Ekspedisi Publik (`/lacak-rfq`)

Halaman publik `/lacak-rfq` memfasilitasi pengecekan status pengiriman tanpa login:

```
[ KLIEN INPUT NOMOR DOKUMEN: RFQ / SO / DO ]
                     │
                     ▼
 [ Controller Mengecek Status di Database ]
                     │
                     ▼
  ┌─────────────────────────────────────────────────────────────────────────┐
  │                    TAMPILAN TIMELINE PELACAKAN                          │
  ├─────────────────────────────────────────────────────────────────────────┤
  │ 1. Status Transaksi: Deal / Sales Order Terbit (PBB/SOR/2026/09/0012)          │
  │ 2. Status Operasional: Shipped (Dalam Pengiriman Ekspedisi)             │
  │ 3. Vendor Ekspedisi: Baraka Cargo                                       │
  │ 5. Tombol Aksi: [ Lacak di Website Resmi Ekspedisi -> ]                 │
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
