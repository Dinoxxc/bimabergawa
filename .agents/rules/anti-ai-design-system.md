# Anti-AI Design System & Visual Token Guidelines
## PT. Panca Bima Bergawa - Workspace Engineering Rules

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `AGY-PBB-RULE-DSYS` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Autonomous Agent & Engineering Guidelines |

---

## 1. Prinsip Anti-AI Manifesto & Filosofi Desain

Sistem visual PT. Panca Bima Bergawa (PBB) dirancang untuk memancarkan wibawa, ketegasan, dan profesionalisme korporat B2B industri alat berat kelistrikan dan otomasi. Kami secara sadar menolak tren desain AI generik yang murah dan tidak fungsional.

### 1.1 Larangan Desain AI Klise (Hard Design Constraints)
1. **Dilarang Gradien Neon Ungu-Biru:** Dilarang keras menggunakan latar belakang atau aksen gradien ungu-biru neon (*purple/blue futuristic glow*) yang lazim dihasilkan oleh template AI generik.
2. **Dilarang Bayangan Bercahaya (Glow Shadow):** Hindari penggunaan `drop-shadow` atau `box-shadow` tebal berpendar. Gunakan garis batas tegas (*1px solid subtle border*) sebagai pemisah elemen visual.
3. **Dilarang Animasi Mengambang Tanpa Tujuan:** Dilarang menyematkan efek *floating*, *pulsing non-stop*, atau *tilt 3D* pada kartu produk yang mengaburkan data spesifikasi teknis.
4. **Dilarang Teks Pemasaran Robotik:** Dilarang menggunakan tajuk klise seperti *"Kesimpulan"*, *"Masa Depan Digital Dimulai Hari Ini"*, atau *"Solusi Terbaik Sepanjang Masa"*. Gunakan bahasa teknis, lugas, faktual, dan presisi.

### 1.2 Penegakan Standar Bebas Emoji (Zero Emoji Standard)
- **0% Emoji Unicode:** Dilarang keras menampilkan karakter emoji Unicode (seperti tanda centang hijau, roket, api, jempol, dll.) pada seluruh kode Blade, CSS, tabel Filament, badge status, maupun pesan konfirmasi.
- **Standar Ikon Grafis:** Wajib 100% menggunakan SVG Icons resmi:
  - Web Publik: Blade Lucide Icons (`<x-lucide-... />`)
  - Panel ERP: Filament Heroicons (`heroicon-o-...` untuk navigasi tabel, `heroicon-m-...` untuk tombol mini)
  - Teks Status: Gunakan text badge formal (contoh: `[Tersedia]`, `[Indent]`, `[Disetujui]`, `[Peringatan]`, `[Dalam Pengiriman]`).

---

## 2. Palet Warna Monokrom Minimalis (Design Tokens)

Sistem menggunakan palet warna monokrom netral (Slate / Zinc) yang dipadukan dengan aksen fungsional kontras tinggi:

| Token Desain | Nilai Hex | Kode Tailwind | Penggunaan & Konteks Antarmuka |
| :--- | :---: | :--- | :--- |
| `surface-bg` | `#ffffff` | `bg-white` | Latar belakang kanvas utama web publik & ERP |
| `surface-subtle` | `#fafafa` | `bg-zinc-50` | Latar belakang kartu produk, section selang-seling |
| `surface-muted` | `#f4f4f5` | `bg-zinc-100` | Latar belakang input disabled, badge netral, skeleton loader |
| `border-light` | `#e4e4e7` | `border-zinc-200` | Garis batas (*border*) tabel, kartu katalog, divider |
| `border-dark` | `#27272a` | `border-zinc-800` | Garis batas elemen interaktif aktif, input fokus, dark card |
| `text-primary` | `#09090b` | `text-zinc-950` | Teks judul utama (H1-H3), nama SKU, nomor dokumen resmi |
| `text-body` | `#18181b` | `text-zinc-900` | Teks paragraf bodi, spesifikasi teknis, data tabel |
| `text-muted` | `#71717a` | `text-zinc-500` | Teks label pendukung, deskripsi singkat, breadcrumbs, timestamp |
| `accent-dark` | `#18181b` | `bg-zinc-900` | Tombol aksi utama (*Primary CTA Button*), header tabel cetak |
| `accent-hover` | `#27272a` | `hover:bg-zinc-800` | State hover tombol aksi utama |
| `status-success`| `#16a34a` | `text-green-600` | Margin HPP aman (>=15%), stok tersedia, status lunas |
| `status-warning`| `#d97706` | `text-amber-600` | Margin HPP tipis (8-15%), SLA warning (>24 jam), status indent |
| `status-danger` | `#dc2626` | `text-red-600` | Margin kritis (<8%), SLA breach (>48 jam), status ditolak/batal |

---

## 3. Tipografi & Skala Hirarki Konten

- **Font Judul / Headings (H1, H2, H3, H4):** **`Outfit`**, sans-serif geometris modern, tajam, dan memiliki ketegasan B2B.
- **Font Bodi & Data Numerik:** **`Inter`**, sans-serif netral dengan keterbacaan tinggi untuk tabel spesifikasi SKU dan angka transaksi.

### 3.1 Skala Tipografi
```css
/* Konfigurasi Tailwind Typography */
.font-heading { font-family: 'Outfit', sans-serif; }
.font-body { font-family: 'Inter', sans-serif; }
```

| Tingkat Hirarki | Kelas Tailwind | Ukuran & Weight | Penerapan |
| :--- | :--- | :--- | :--- |
| **Display / Hero H1** | `font-heading text-3xl md:text-5xl font-bold tracking-tight text-zinc-950` | 36px / 48px, 700 | Judul utama landing page & company profile |
| **Section H2** | `font-heading text-2xl md:text-3xl font-semibold tracking-tight text-zinc-950` | 24px / 30px, 600 | Judul kategori produk & section beranda |
| **Card Title H3** | `font-heading text-lg md:text-xl font-medium text-zinc-900` | 18px / 20px, 500 | Nama produk pada kartu katalog & drawer RFQ |
| **Subhead H4** | `font-heading text-base font-medium text-zinc-800` | 16px, 500 | Label grup spesifikasi & filter sidebar |
| **Body Standard** | `font-body text-sm md:text-base text-zinc-700 leading-relaxed` | 14px / 16px, 400 | Deskripsi detail teknis & profil perusahaan |
| **Data Tabular** | `font-body text-xs md:text-sm font-normal text-zinc-900 tabular-nums` | 12px / 14px, 400 | Sel tabel SKU, kuantitas, tanggal, dan nomor SO |

---

## 4. Spesifikasi Komponen UI Blade

### 4.1 Kartu Produk SKU (Zero-Price Enforcement)
Kartu produk web publik dilarang menampilkan nominal rupiah, melainkan fokus pada spesifikasi dan aksi RFQ:
```blade
<div class="group relative flex flex-col justify-between rounded-none border border-zinc-200 bg-white p-5 transition-colors hover:border-zinc-900">
    <div class="space-y-3">
        <div class="flex items-center justify-between text-xs text-zinc-500">
            <span class="font-medium tracking-wide uppercase text-zinc-900">{{ $product->brand->name }}</span>
            <span class="tabular-nums font-mono">{{ $product->sku }}</span>
        </div>
        <div class="aspect-square w-full overflow-hidden bg-zinc-50 flex items-center justify-center border border-zinc-100">
            <img src="{{ $product->image_path_webp }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-4" loading="lazy">
        </div>
        <h3 class="font-heading text-base font-semibold text-zinc-900 line-clamp-2">
            {{ $product->name }}
        </h3>
        <p class="text-xs text-zinc-600 line-clamp-2 leading-relaxed">
            {{ $product->technical_summary }}
        </p>
    </div>
    
    <div class="mt-5 pt-4 border-t border-zinc-100 flex items-center gap-2">
        <button 
            type="button"
            x-data
            @click="$store.rfqCart.addItem({ id: {{ $product->id }}, sku: '{{ $product->sku }}', name: '{{ addslashes($product->name) }}', brand: '{{ $product->brand->name }}' })"
            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-zinc-900 px-3 py-2 text-xs font-medium text-white hover:bg-zinc-800 transition-colors"
        >
            <x-lucide-file-text class="h-3.5 w-3.5" />
            <span>Minta Penawaran</span>
        </button>
        <a 
            href="https://wa.me/6281112345678?text=Halo%20Panca%20Bima%20Bergawa,%20saya%20tertarik%20dengan%20SKU%20{{ $product->sku }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center border border-zinc-300 p-2 text-zinc-700 hover:bg-zinc-50 hover:border-zinc-900 transition-colors"
            title="Tanya via WhatsApp"
        >
            <x-lucide-message-square class="h-3.5 w-3.5" />
        </a>
    </div>
</div>
```

---

## 5. Arsitektur Alpine.js RFQ Store

RFQ Drawer beroperasi reaktif menggunakan store Alpine.js dengan sinkronisasi ke `localStorage`:

```javascript
document.addEventListener('alpine:init', () => {
    Alpine.store('rfqCart', {
        items: JSON.parse(localStorage.getItem('pbb_rfq_items') || '[]'),
        isOpen: false,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        addItem(product) {
            const existing = this.items.find(item => item.sku === product.sku);
            if (existing) {
                existing.quantity += 1;
            } else {
                this.items.push({
                    id: product.id,
                    sku: product.sku,
                    name: product.name,
                    brand: product.brand,
                    quantity: 1,
                    notes: ''
                });
            }
            this.save();
            this.isOpen = true;
        },

        removeItem(sku) {
            this.items = this.items.filter(item => item.sku !== sku);
            this.save();
        },

        updateQuantity(sku, qty) {
            const item = this.items.find(i => i.sku === sku);
            if (item) {
                item.quantity = Math.max(1, parseInt(qty) || 1);
                this.save();
            }
        },

        clear() {
            this.items = [];
            this.save();
        },

        save() {
            localStorage.setItem('pbb_rfq_items', JSON.stringify(this.items));
        },

        get count() {
            return this.items.reduce((total, item) => total + item.quantity, 0);
        }
    });
});
```

---

## 6. Katalog SVG Icons Resmi (Lucide & Heroicons)

| Kategori Produk / Modul | Blade Lucide Icon (Web Publik) | Filament Heroicon (ERP) |
| :--- | :--- | :--- |
| **Kelistrikan & Daya** | `<x-lucide-zap class="h-4 w-4" />` | `heroicon-o-bolt` |
| **Otomasi & Kontrol** | `<x-lucide-cpu class="h-4 w-4" />` | `heroicon-o-cpu-chip` |
| **Pencahayaan Industri** | `<x-lucide-lightbulb class="h-4 w-4" />` | `heroicon-o-light-bulb` |
| **Kabel & Infrastruktur** | `<x-lucide-network class="h-4 w-4" />` | `heroicon-o-square-3-stack-3d` |
| **Keranjang / RFQ** | `<x-lucide-shopping-bag class="h-4 w-4" />` | `heroicon-o-shopping-cart` |
| **Penawaran / Quotation** | `<x-lucide-file-text class="h-4 w-4" />` | `heroicon-o-document-text` |
| **Surat Jalan / Logistik** | `<x-lucide-truck class="h-4 w-4" />` | `heroicon-o-truck` |
| **Komisi & Keuangan** | `<x-lucide-wallet class="h-4 w-4" />` | `heroicon-o-banknotes` |
| **Keamanan & Sertifikat** | `<x-lucide-shield-check class="h-4 w-4" />` | `heroicon-o-shield-check` |
