# 01. Desain Sistem, Design Tokens, & Standar Rekayasa Antarmuka
## UI/UX Design & Wireframe Specification (UI-UX)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-UIUX-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | UI System Designer | Draf Awal | Definisi palet warna, tipografi Inter, & token spacing dasar |
| `1.1.0` | 03 Sep 2026 | Senior UI/UX Specialist | Ditinjau | Penambahan token semantik margin HPP & badge status inden/stok |
| `1.2.0` | 05 Sep 2026 | Principal Design Architect | Disetujui | Redesain total ke High-Contrast Monochrome Minimalist (Pure Black `#000000` & White `#FFFFFF`) |
| `1.3.0` | 05 Sep 2026 | Principal UI/UX & Lead Architect | Disetujui | Penegasan Standar Anti-AI Design, sanitasi total emoji ke Icon SVG formal, & pedoman copywriting B2B |

---

## 1. Filosofi Desain: High-Contrast Minimalist & Industrial Editorial

Mengadopsi inspirasi visual minimalis modern berstandar internasional (*Editorial Swiss & High-End Monochrome Minimalist* seperti referensi StudioPress/Authentik), antarmuka PT. Panca Bima Bergawa menitikberatkan pada **kesederhanaan visual yang elegan, tipografi tegas (*bold display typography*), grid proporsional terstruktur, dan kontras monokrom hitam-putih yang kuat**.

### 4 Pilar Desain Utama:
1. **Monochrome High-Contrast Clarity:** Didominasi warna murni Hitam (*Pure Jet Black* `#000000`) dan Putih (*Crisp White* `#FFFFFF`) dengan aksen abu-abu netral terkalibrasi (*Subtle Neutral* `#F4F4F5`). Memberikan kesan korporat yang sangat bersih, jujur, berkelas, dan bebas dari distraksi warna berlebih.
2. **Editorial Display Typography:** Menggunakan kombinasi font Sans-Serif geometris yang tegas (*Bold Grotesk Display*) untuk judul utama yang diakhiri tanda titik tegas (*e.g., "Solusi Industri.", "Katalog Presisi."*), dipadukan dengan tipografi *Serif Italic* untuk kutipan filosofi perusahaan/kepercayaan klien.
3. **Solid Black Block Action CTAs:** Tombol aksi utama (*Call to Action*) menggunakan blok hitam pekat (`#000000`) dengan teks putih tebal, serta tautan teks minimalis bergaris bawah (*minimal underline text with arrows `->`*).
4. **Zero Price Transparency:** Fokus penuh pada presisi nomor seri (*SKU/Part Number*), lembar spesifikasi teknis (*datasheet*), dan jalur pengajuan penawaran instan (*RFQ*) tanpa menampilkan nominal harga di web publik.

---

## 2. Standar Anti-AI Design, Copywriting, & Code Quality (Anti-AI Manifesto)

Untuk menjamin antarmuka web dan ERP terasa otentik, profesional, manusiawi, dan berwibawa di mata pelaku industri B2B, seluruh pengembang dan desainer wajib mematuhi batasan ketat berikut:

### 2.1 Larangan Ciri Visual & Desain Artifisial (Visual Rules)
- **Dilarang Gradien Ungu-Biru & Efek Glow:** Tidak boleh menggunakan kombinasi warna gradien ungu-biru mencolok, border berpendar (*neon glowing effects*), atau kartu transparan bergaya *glassmorphism* berlebihan yang identik dengan template instan AI.
- **Dilarang Animasi Mengambang Tanpa Fungsi:** Menghindari elemen yang melayang terus-menerus (*floating elements*), efek partikel bergerak di latar belakang, atau animasi transisi kompleks yang memperlambat respon antarmuka dan membuang resource komputasi pengguna.
- **Dilarang Ruang Kosong (Whitespace) Janggal:** Jarak antar elemen (*spacing & padding*) harus berbasis sistem skala grid proporsional ($4\text{px}$, $8\text{px}$, $16\text{px}$, $24\text{px}$, $32\text{px}$, $64\text{px}$). Tidak boleh ada margin acak atau ruang kosong mengambang yang tidak rasional.
- **Dilarang Gambar Artifisial/Stock Buatan AI:** Dilarang keras memakai gambar ilustrasi futuristik generic atau foto manusia dengan cacat geometri (jari tangan aneh, latar blur artifisial). Wajib menggunakan foto produk industri nyata berlatar putih bersih atau dokumentasi workshop teknik orisinal.
- **Dilarang Menggunakan Emoji:** Seluruh antarmuka publik maupun ERP dilarang menggunakan karakter emoji (e.g. emotikon ekspresi wajah, simbol warna-warni). Seluruh elemen visual harus menggunakan **Icon SVG formal** (Lucide Icons, Heroicons, atau Tabler Icons) atau label teks badge teknis.

### 2.2 Standar Nada Bahasa & Copywriting Manusiawi (Human-Centric Copywriting)
- **Nada Bahasa Solutif & Bernas:** Menghindari kalimat kaku, dingin, atau formal berlebihan seperti terjemahan mesin. Gunakan gaya bahasa B2B Indonesia yang luwes, praktis, ramah, dan berorientasi pada solusi teknis nyata di lapangan pabrik.
- **Struktur Kalimat Bervariasi & Kontekstual:** Menghindari pola kalimat klise yang berulang-ulang dengan sudut pandang orang ketiga pasif. Dilarang menutup section atau halaman dengan judul klise seperti *"Kesimpulan"* atau *"Summary"*.
- **Kaya Tautan Kontekstual (High-Action Utility):** Setiap halaman wajib menyertakan Call-to-Action (CTA) fungsional yang relevan, seperti tautan konsultasi WhatsApp PIC sales langsung, tautan unduh brosur PDF manufaktur resmi, tombol salin SKU, dan tautan pelacakan ekspedisi.

### 2.3 Standar Struktur Kode Bersih & Logika Efisien (Clean Engineering)
- **Komentar Kode Tepat Sasaran:** Komentar di dalam baris kode hanya ditulis untuk menjelaskan *business context* atau *edge case*, bukan mendokumentasikan sintaks dasar yang sudah jelas fungsinya.
- **Logika Sederhana & Langsung (KISS):** Menghindari abstraksi berlapis atau percabangan if-else rumit untuk fungsi yang dapat diselesaikan dengan 2-3 baris logika elegan.

---

## 3. Palet Warna & Token Semantik (Color Palette & Semantic Tokens)

### 3.1 Skema Warna Monokrom & Netral (Monochrome Palette)

```css
:root {
  /* Core High-Contrast Monochrome */
  --color-black-pure:       #000000; /* Tombol CTA Utama, Judul Display H1/H2, Logo, Border Tegas */
  --color-black-charcoal:   #111827; /* Teks Heading H3/H4, Kartu Gelap, Header ERP */
  --color-gray-body:        #374151; /* Teks Paragraf Utama (High Contrast Body) */
  --color-gray-muted:       #6B7280; /* Label Part Number, Metadata, Teks Bantuan */
  --color-gray-subtle:      #9CA3AF; /* Placeholder Teks, Ikon Non-Aktif */
  --color-gray-border:      #E5E7EB; /* Garis Pembatas Halus (Hairline Dividers) */
  --color-gray-surface:     #F4F4F5; /* Background Input Fields, Kartu Netral, Box Widget */
  --color-gray-canvas:      #FAFAFA; /* Background Alternatif Section */
  --color-white-pure:       #FFFFFF; /* Latar Belakang Utama (Canvas) & Kartu Produk */
}
```

| Nama Token CSS | Kode Hex | Peruntukan Antarmuka |
| :--- | :--- | :--- |
| `--color-black-pure` | `#000000` | **Primary Brand / Action** — Judul utama, tombol CTA penuh (*Solid Black*), footer, dan border aksen |
| `--color-black-charcoal` | `#111827` | Subjudul, teks kartu, ikon navigasi aktif |
| `--color-gray-body` | `#374151` | Body copy teks deskripsi produk dan artikel wawasan |
| `--color-gray-muted` | `#6B7280` | Kode part number / SKU mono, label spesifikasi teknis |
| `--color-gray-border` | `#E5E7EB` | Hairline border untuk tabel, divider section, dan kartu |
| `--color-gray-surface` | `#F4F4F5` | Background kotak input formulir, search bar, dan card teaser |
| `--color-white-pure` | `#FFFFFF` | Background halaman web publik dan popup modal |

---

### 3.2 Token Status Semantik & Indikator ERP (Minimalist Status Badges)

Status fungsional tetap memiliki indikator warna presisi, namun disajikan dengan *style flat & border* minimalis tanpa efek neon/glow:

| Status Semantik | Kode Hex Teks | Background Badge | Deskripsi Penerapan |
| :--- | :--- | :--- | :--- |
| **Success (Ready/Won/Lunas)** | `#15803D` | `#F0FDF4` (Border `#DCFCE7`) | Stok Siap Kirim, Margin HPP $>15\%$, PO Approved |
| **Warning (Inden/Stale Lead)** | `#B45309` | `#FFFBEB` (Border `#FEF3C7`) | Inden Pabrik, Alert Stale Lead ($>2\text{ jam}$) |
| **Danger (Discontinued/Lost)** | `#B91C1C` | `#FEF2F2` (Border `#FEE2E2`) | Model EOL, Margin Kritis $<10\%$, Penawaran Batal |
| **Info / Successor** | `#1D4ED8` | `#EFF6FF` (Border `#DBEAFE`) | Suksesor Model Tersedia, Resi Ekspedisi Terbit |

---

## 4. Tipografi & Skala Teks Editorial (Typography System)

Kombinasi font utama:
- **Font Utama (Display & Body):** `Plus Jakarta Sans` / `Inter` (Sans-Serif modern berkarakter kuat).
- **Font Aksen Editorial (Quotes & Philosophy):** `Newsreader` / `Lora` (Serif elegan untuk kutipan komitmen mutu direksi).
- **Font Teknis (SKU & Monospace):** `JetBrains Mono` (Angka dan part number presisi).

```css
:root {
  --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-serif: 'Newsreader', 'Lora', Georgia, serif;
  --font-mono: 'JetBrains Mono', monospace;
}
```

### 4.1 Skala Tipografi (Type Scale)

| Tingkat Hierarki | Ukuran Font | Weight & Style | Format & Karakter |
| :--- | :--- | :--- | :--- |
| **Hero Display H1** | `3.25rem` ($52\text{px}$) | `800` (Extra Bold) | *Sentence case* dengan titik tegas (*e.g., "Selamat datang di **PBB.**"*) |
| **Section Title H2** | `2.25rem` ($36\text{px}$) | `700` (Bold) | *Title Case* tegas (*e.g., "Mulai pengadaan Anda."*) |
| **Sub-heading H3** | `1.5rem` ($24\text{px}$) | `600` (SemiBold) | Judul kartu kategori dan fitur |
| **Card / Item Title H4** | `1.125rem` ($18\text{px}$) | `600` (SemiBold) | Nama model produk dan part number |
| **Body Standard** | `1.0rem` ($16\text{px}$) | `400` (Regular) | Teks paragraf umum, line-height `1.6` |
| **Editorial Quote** | `1.25rem` ($20\text{px}$) | `400` (Italic Serif) | Kutipan *"Kemitraan teknik yang transparan dan dapat diandalkan."* |
| **SKU / Spec Code** | `0.875rem` ($14\text{px}$) | `700` (Mono) | Nomor part number terstandar |
| **Micro Caption / Upper**| `0.75rem` ($12\text{px}$) | `700` (Uppercase Tracking) | Label menu footer `ABOUT`, `SERVICES`, `BRANDS` |

---

## 5. Gaya Komponen & Standar Ikonografi (Icons & Components)

### 5.1 Standar Ikonografi (Iconography Token Standard)

Seluruh ikon antarmuka menggunakan pustaka **Lucide Icons** (untuk Blade Web Publik) dan **Heroicons Outline** (untuk Filament Admin ERP). Format notasi dalam dokumen dan kode:

```blade
{{-- Contoh pemanggilan icon formal --}}
<x-lucide-search class="w-5 h-5 text-neutral-600" />
<x-lucide-shopping-bag class="w-5 h-5 text-black" />
<x-lucide-shield-check class="w-6 h-6 text-black" />
<x-lucide-truck class="w-6 h-6 text-black" />
<x-lucide-wrench class="w-6 h-6 text-black" />
<x-lucide-copy class="w-4 h-4 text-neutral-500" />
```

### 5.2 Tombol Aksi (Buttons & CTA Links)

1. **Primary Solid Black CTA Button:**
   ```css
   .btn-primary {
     background-color: #000000;
     color: #FFFFFF;
     font-weight: 700;
     font-size: 0.875rem;
     letter-spacing: 0.025em;
     padding: 0.875rem 1.75rem;
     border-radius: 4px; /* Micro-radius minimalis */
     border: 1px solid #000000;
     transition: background-color 0.15s ease;
   }
   .btn-primary:hover {
     background-color: #1F2937;
   }
   ```

2. **Text Link with Underline & Arrow (`PELAJARI LEBIH LANJUT ->`):**
   ```css
   .btn-link-editorial {
     color: #000000;
     font-weight: 700;
     font-size: 0.8125rem;
     text-transform: uppercase;
     letter-spacing: 0.05em;
     text-decoration: underline;
     text-underline-offset: 4px;
   }
   .btn-link-editorial:hover {
     opacity: 0.7;
   }
   ```

3. **Input Field Style (Minimal Flat Box):**
   ```css
   .input-minimal {
     background-color: #F4F4F5;
     border: 1px solid transparent;
     border-radius: 4px;
     padding: 0.875rem 1.0rem;
     color: #111827;
     font-size: 0.9375rem;
   }
   .input-minimal:focus {
     background-color: #FFFFFF;
     border-color: #000000;
     outline: none;
   }
   ```

---

## 6. Spacing, Elevasi, & Garis Batas (Whitespace & Elevation)

- **Sistem Grid Proporsional:** Padding vertikal antar section menggunakan skala rasional $80\text{px}$ (`py-20`) atau $96\text{px}$ (`py-24`) untuk menciptakan keterbacaan yang lega tanpa kekosongan ganjil.
- **Hairline Dividers:** Pemisah antar baris dan kolom tabel menggunakan garis tipis 1px (`border-t border-[#E5E7EB]`).
- **Minimal Flat Borders:** Menghindari drop-shadow tebal atau gradasi bayangan warna-warni. Menggunakan garis batas halus (*hairline border*) untuk memperjelas batas setiap kartu produk.
