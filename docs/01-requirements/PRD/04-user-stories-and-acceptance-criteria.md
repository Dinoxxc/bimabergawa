# 04. User Stories & Kriteria Penerimaan (Acceptance Criteria)
## Product Requirement Document (PRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-PRD-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA & Product Specialist | Draf Awal | Penyusunan 12 skenario user stories berformat Gherkin (Given-When-Then) |
| `1.1.0` | 03 Sep 2026 | Lead QA Engineer | Ditinjau | Penambahan skenario test kriteria penerimaan pengiriman DO parsial & pelacakan resi |
| `1.2.0` | 05 Sep 2026 | Engineering Lead | Disetujui | Validasi kriteria penerimaan proteksi kebocoran HPP & scope freelance agent |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Daftar User Stories & Kriteria Penerimaan (Gherkin Syntax)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           13 USER STORIES UTAMA SISTEM                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│ US-01: Pencarian & Filtering Katalog Multi-Brand                                │
│ US-02: Tanya Harga 1 Produk (Direct WA & Modal Pop-up)                          │
│ US-03: Keranjang Penawaran Massal (Bulk RFQ Cart & Guest Submission)            │
│ US-04: Penangkapan Atribusi Pemasaran (UTM & Referral Telemetry)                │
│ US-05: Pembuatan Surat Penawaran Resmi & Render DomPDF                          │
│ US-06: Alur Otorisasi Diskon Khusus (Special Discount Approval)                 │
│ US-07: Penerbitan Sales Order & Cetak Surat Jalan (Delivery Order)              │
│ US-08: Unggah Massal Katalog Produk via Excel/CSV                               │
│ US-09: Publikasi Artikel Wawasan & Interlinking Produk Terkait                  │
│ US-10: Pengiriman Pesan Kontak Umum & Proteksi Anti-Spam                        │
│ US-11: Input Prospek Manual / Offline / External WA di Panel ERP                │
│ US-12: Manajemen Komisi Freelance Sales & Atribusi Kode Referal                 │
│ US-13: Pengelolaan Konten Website & Profil Perusahaan via Panel ERP             │
└─────────────────────────────────────────────────────────────────────────────────┘


```

---

### US-01: Pencarian & Filtering Katalog Multi-Brand
- **Sebagai** staf procurement korporat,
- **Saya ingin** mencari produk berdasarkan nomor part dan menyaring brand serta kategori secara bersamaan,
- **Sehingga** saya dapat menemukan suku cadang industri yang spesifik dalam hitungan detik.

```gherkin
Scenario: Pengunjung mencari produk via Live Search
  Given Pengunjung berada di halaman "/catalog"
  When Pengunjung mengetik "FSHA 50x40" pada kolom pencarian
  Then Sistem menampilkan daftar produk yang cocok secara real-time tanpa reload halaman penuh
  And Menampilkan nama produk, kode part, dan logo brand Ebara

Scenario: Pengunjung menyaring produk berdasarkan Brand dan Kategori
  Given Pengunjung berada di halaman "/catalog"
  When Pengunjung mencentang brand "Schneider Electric" dan kategori "Inverter & Drive"
  Then Sistem hanya menampilkan produk Schneider yang termasuk dalam kategori Inverter
```

---

### US-02: Tanya Harga 1 Produk (Fast Track WA & Modal Pop-up)
- **Sebagai** kontraktor proyek di lapangan,
- **Saya ingin** langsung menanyakan ketersediaan 1 produk mendesak via WhatsApp atau modal form singkat,
- **Sehingga** saya segera mendapat jawaban dari tim sales.

```gherkin
Scenario: Pengunjung mengklik tombol Chat via WhatsApp
  Given Pengunjung membuka halaman detail produk "/catalog/pompa-ebara-50x40"
  When Pengunjung mengklik tombol "Chat Sales via WhatsApp"
  Then Browser membuka tautan WhatsApp dengan pesan terformat:
    "Halo Tim Sales, saya tertarik dengan produk Pompa Ebara 50x40 (Model: FSHA-50). Mohon info penawaran harga..."

Scenario: Pengunjung mengirim form modal pop-up Single RFQ
  Given Pengunjung mengklik tombol "Tanya Produk Ini" pada halaman detail produk
  When Pengunjung mengisi Nama, Perusahaan, Nomor WhatsApp, dan Jumlah Unit, lalu mengklik "Kirim"
  Then Sistem menyimpan data ke tabel "inquiries" dengan type "single_direct"
  And Mengeluarkan notifikasi sukses instan dengan nomor referensi penawaran
```

---

### US-03: Keranjang Penawaran Massal (Bulk RFQ Cart & Guest Mode)
- **Sebagai** manajer pengadaan pabrik,
- **Saya ingin** mengumpulkan 10 item barang berbeda ke dalam keranjang penawaran dan mengirimkan satu form pengajuan,
- **Sehingga** saya menerima penawaran harga kolektif resmi tanpa repot mendaftar akun.

```gherkin
Scenario: Menambah produk ke keranjang RFQ dan submit tanpa login
  Given Pengunjung menjelajah katalog dan mengklik "+ Tambah ke RFQ" pada 3 produk berbeda
  Then Data tersimpan di localStorage "bima_rfq_cart"
  And Indikator badge keranjang menampilkan angka "3"
  When Pengunjung membuka halaman "/rfq", mengisi estimasi kuantiti per item, dan mengirimkan form kontak
  Then Sistem menyimpan 1 record di tabel "inquiries" (type: "bulk_cart") dan 3 record di "inquiry_items"
  And Membersihkan localStorage browser pengunjung
  And Menampilkan nomor unik "INQ-YYYYMM-XXXX"

Scenario: Bot otomatis mengirimkan form RFQ dengan honeypot terisi
  Given Bot crawler otomatis mengirimkan payload POST ke "/rfq/submit" dengan mengisi field tersembunyi "website_url_hp"
  When Request diproses oleh Honeypot Middleware
  Then Sistem secara senyap membatalkan penyimpanan ke database
  And Mengembalikan respons HTTP tanpa membebani antrean tim sales internal
```

---

### US-04: Penangkapan Atribusi Pemasaran (UTM Parameter Capture)
- **Sebagai** digital marketer internal,
- **Saya ingin** setiap prospek yang masuk secara otomatis tercatat sumber iklan asalnya,
- **Sehingga** saya dapat mengevaluasi efektivitas anggaran kampanye promosi.

```gherkin
Scenario: Menangkap parameter UTM iklan saat pengunjung membuka web
  Given Pengunjung mengklik iklan dengan URL "/catalog/pompa-ebara?utm_source=google&utm_campaign=promo-q3"
  When Middleware "CaptureUtmParameters" memproses request
  Then Sistem menyimpan "utm_source = google" dan "utm_campaign = promo-q3" ke dalam session Laravel
  And Saat pengunjung men-submit form penawaran harga, nilai UTM tersebut otomatis tersimpan di record "inquiries"
```

---

### US-05: Pembuatan Surat Penawaran Resmi & Render DomPDF
- **Sebagai** tim sales internal,
- **Saya ingin** mengonversi inquiry menjadi dokumen Quotation PDF ber-Kop Surat resmi lengkap dengan PPN 11% dan termin pembayaran,
- **Sehingga** saya dapat mengirimkannya ke klien dalam waktu < 2 menit.

```gherkin
Scenario: Sales membuat Quotation PDF dari inquiry
  Given Sales membuka inquiry berstatus "contacted" di panel Filament ERP
  When Sales mengklik "Buat Surat Penawaran", memasukkan harga satuan, diskon 5%, dan memilih TOP "30 Hari"
  Then Sistem menghitung Subtotal, Diskon, DPP, PPN 11%, dan Grand Total secara otomatis
  And Merender berkas PDF resmi berkop surat dengan nomor "QUO-YYYYMM-XXXX"
  And Mengubah status inquiry menjadi "quotation_sent"
```

---

### US-06: Alur Otorisasi Diskon Khusus (Special Discount Approval)
- **Sebagai** direktur operasional (Super Admin),
- **Saya ingin** mengontrol pemberian diskon besar yang melebihi batas kewenangan sales,
- **Sehingga** profitabilitas dan margin perusahaan tetap terlindungi.

```gherkin
Scenario: Sales mengajukan diskon melebihi 10%
  Given Sales menyusun draft penawaran harga dan memasukkan diskon 18%
  When Sales mencoba menerbitkan penawaran
  Then Sistem menandai penawaran membutuhkan "Approval Direksi"
  When Super Admin / Direksi membuka panel dan mengklik "Approve Special Pricing"
  Then Dokumen Quotation dapat digenerate dan dikirimkan ke klien
```

---

### US-07: Penerbitan Sales Order & Cetak Surat Jalan (Delivery Order)
- **Sebagai** tim logistik internal,
- **Saya ingin** mencetak Surat Jalan resmi tanpa harga saat penawaran disetujui klien dan menginput nomor resi ekspedisi,
- **Sehingga** barang dapat dikirim melalui jasa ekspedisi/kargo dengan dokumen sah dan dapat dilacak oleh klien.

```gherkin
Scenario: Konversi Quotation Deal menjadi Sales Order dan Input Resi Ekspedisi
  Given Klien menyetujui penawaran dan mengirimkan PO fisik resmi
  When Sales mengupdate status menjadi "deal" dan menginput nomor PO fisik klien
  Then Sistem otomatis menerbitkan record "sales_orders" dengan nomor "SO-YYYYMM-XXXX"
  And Mengunci data penawaran agar tidak dapat diedit lagi
  When Tim logistik menyerahkan barang ke ekspedisi dan menginput nama ekspedisi "Indah Logistik", nomor resi, serta URL tracking
  Then Klien dapat memantau resi tersebut langsung di halaman "/lacak-rfq"
  When Tim logistik mengklik "Cetak Surat Jalan (DO)"
  Then Sistem merender berkas PDF Surat Jalan memuat daftar barang dan alamat kirim tanpa menampilkan nominal harga
```

---

### US-08: Unggah Massal Katalog Produk via Excel/CSV
- **Sebagai** tim marketing & admin katalog,
- **Saya ingin** mengunggah 1.000 data SKU produk dari file Excel sekaligus,
- **Sehingga** etalase produk selalu diperbarui dengan cepat saat prinsipal merilis pricelist baru.

```gherkin
Scenario: Import massal produk via file Excel
  Given Admin mengunggah file template Excel berisi 1.000 baris produk di Filament ERP
  When Sistem memproses file melalui background chunking batch 100 baris
  Then 1.000 produk berhasil tersimpan beserta relasi Brand, Kategori, dan Spesifikasi
  And Memori server tetap berada di bawah 512MB tanpa error timeout
```

---

### US-09: Publikasi Artikel Wawasan & Interlinking Produk Terkait
- **Sebagai** pembaca artikel di website publik,
- **Saya ingin** melihat rekomendasi produk katalog terkait di bagian bawah artikel yang saya baca,
- **Sehingga** saya dapat langsung meminta penawaran untuk produk yang sedang diulas.

```gherkin
Scenario: Membaca artikel ulasan inverter dan melihat produk terkait
  Given Pembeli membuka artikel "/insights/panduan-memilih-inverter-schneider"
  When Pembeli selesai membaca konten artikel
  Then Di bagian bawah artikel tampil kartu produk "Inverter Schneider ATV320"
  And Pembeli dapat langsung mengklik "+ Tambah ke RFQ" dari halaman artikel tersebut
```

---

### US-10: Pengiriman Pesan Kontak Umum & Proteksi Anti-Spam
- **Sebagai** pengunjung umum non-produk,
- **Saya ingin** mengirimkan pertanyaan umum atau penawaran kerjasama melalui halaman `/contact`,
- **Sehingga** pesan saya sampai ke bagian manajemen perusahaan.

```gherkin
Scenario: Mengirim pesan kontak umum
  Given Pengunjung mengisi form nama, email, perusahan, dan subjek di halaman "/contact"
  When Verifikasi anti-bot (Cloudflare Turnstile) sukses dan pengunjung menekan "Kirim Pesan"
  Then Data tersimpan ke tabel "contact_messages" dengan status "unread"
  And Muncul di inbox CRM panel internal Filament ERP
```

---

### US-11: Input Prospek Manual / Offline / External WA di Panel ERP
- **Sebagai** staf sales internal,
- **Saya ingin** menginputkan prospek yang datang dari telepon kantor, pameran, atau chat WA langsung ke dalam pipeline CRM,
- **Sehingga** seluruh prospek offline tetap terdata terpusat dan dapat diproses menjadi Quotation resmi.

```gherkin
Scenario: Sales menginputkan prospek dari panggilan telepon/pameran
  Given Sales berada di panel CRM Filament ERP
  When Sales mengklik "Tambah Prospek Baru", memilih sumber "walk_in_phone" atau "exhibition_offline"
  And Mengisi data PIC, nomor WhatsApp, serta memilih produk dan kuantiti yang diminta
  Then Sistem menyimpan inquiry baru di kolom Kanban "Inquiry Baru"
  And Sales dapat langsung melanjutkan ke pembuatan Quotation PDF
```

---

### US-12: Manajemen Komisi Freelance Sales & Atribusi Kode Referal
- **Sebagai** manajemen perusahaan dan mitra freelance sales,
- **Saya ingin** setiap transaksi yang dirujuk oleh freelance sales otomatis terkunci hak komisinya saat deal,
- **Sehingga** pencairan komisi penjualan dapat dihitung secara akurat dan transparan.

```gherkin
Scenario: Klien mengajukan RFQ via link referal Freelance Sales
  Given Calon pembeli mengunjungi katalog via link "domain.com/catalog/pompa-ebara?ref=dimas"
  When Calon pembeli mengirimkan form RFQ
  Then Sistem otomatis mencatat ID freelance sales "Mas Dimas" pada inquiry tersebut
  When Tim sales internal membuat Quotation dan disetujui klien (Deal) dengan DPP Rp 50.000.000
  And Diterapkan komisi 5% (Rp 2.500.000)
  Then Sistem mencatat komisi berstatus "payable" yang siap dicairkan ke rekening Mas Dimas
```

---

### US-13: Pengelolaan Konten Website & Profil Perusahaan via Panel ERP
- **Sebagai** staf content marketing dan admin website,
- **Saya ingin** mengubah teks hero banner beranda, foto sertifikat legalitas, alamat kantor, dan link media sosial melalui panel ERP,
- **Sehingga** website publik dapat diperbarui seketika tanpa memerlukan bantuan programmer untuk mengubah kode program.

```gherkin
Scenario: Admin mengubah teks hero banner dan kontak di panel ERP
  Given Admin login ke panel Filament ERP dan membuka menu "Pengaturan Website"
  When Admin mengubah "Hero Title" menjadi "Distributor Resmi Perlengkapan Industri #1 di Indonesia"
  And Mengubah link Instagram dan nomor WhatsApp resmi
  And Mengklik "Simpan Perubahan"
  Then Tampilan halaman beranda publik (domain.com) otomatis menampilkan judul hero dan tautan kontak yang baru secara real-time
```

---

### US-14: Pendaftaran, Screening Kriteria, dan Approval Mitra Freelance
- **Sebagai** manajemen perusahaan (Super Admin / Direksi),
- **Saya ingin** menyaring dan menyetujui calon mitra freelance yang mendaftar dari luar berdasarkan kriteria kelayakan (KTP, pengalaman B2B, kesesuaian sektor industri),
- **Sehingga** perusahaan hanya bermitra dengan sales lepas yang kredibel dan memiliki potensi jaringan klien nyata.

```gherkin
Scenario: Calon mitra freelance mendaftar via web dan disetujui Super Admin
  Given Calon mitra luar mengisi form pendaftaran di "/gabung-mitra"
  And Mengunggah foto KTP, memilih sektor industri "Minyak & Gas, Petrokimia", pengalaman "3-5 tahun", dan mengisi nomor rekening
  When Calon mitra menekan "Kirim Pendaftaran Mitra"
  Then Data tersimpan dengan status "pending_review" dan masuk ke antrean verifikasi ERP
  When Super Admin membuka menu "Verifikasi Calon Mitra", memeriksa dokumen KTP, dan mengklik "Approve & Aktifkan"
  And Menetapkan komisi default "5.00%"
  Then Sistem otomatis membuat akun user baru dengan role "freelance_sales"
  And Men-generate kode referal unik (misal: "REF-DIMAS-88")
  And Mengirimkan email sambutan resmi berisi kode referal dan panduan login via Email OTP

Scenario: Calon mitra freelance ditolak karena tidak memenuhi kriteria
  Given Calon mitra mendaftar dengan foto KTP buram atau di luar sektor industri yang dilayani
  When Super Admin mengklik "Reject Application" dan memasukkan alasan "KTP tidak terbaca dan sektor industri tidak sesuai"
  Then Status pendaftaran berubah menjadi "rejected"
  And Sistem mengirimkan email pemberitahuan penolakan secara santun kepada pelamar
```

---

### US-15: Pengelolaan Syarat & Ketentuan (TnC) dan Kebijakan Legal via Panel ERP
- **Sebagai** tim manajemen legal / administrator website,
- **Saya ingin** mengedit teks Syarat & Ketentuan (TnC), Kebijakan Privasi, Ketentuan Garansi, dan Syarat Kemitraan melalui editor panel ERP,
- **Sehingga** website selalu mematuhi regulasi hukum dagang & privasi terbaru tanpa perlu meminta perubahan kode program (*hardcode*).

```gherkin
Scenario: Admin memperbarui Syarat & Ketentuan transaksi di panel ERP
  Given Admin login ke panel Filament ERP dan membuka tab "Pengaturan Dokumen Legal & Kebijakan"
  When Admin mengedit teks pada kolom "Syarat & Ketentuan (Terms & Conditions)" menggunakan Rich Text Editor
  And Mengubah klausul masa berlaku penawaran harga menjadi 14 hari kerja
  And Mengklik "Simpan Perubahan Legalitas"
  Then Halaman publik "/syarat-ketentuan" langsung menampilkan klausul teks terbaru
  And Menampilkan label tanggal pembaruan otomatis (misal: "Terakhir Diperbarui: 5 September 2026")
```

---

### US-16: Pelacakan Status Penawaran Mandiri oleh Pembeli Guest (`/lacak-rfq`)
- **Sebagai** staf procurement / pembeli korporat (Guest),
- **Saya ingin** memantau status pengerjaan penawaran dan status pengiriman barang dengan memasukkan nomor RFQ dan nomor WhatsApp saya,
- **Sehingga** saya mendapatkan kepastian progres transaksi secara mandiri tanpa harus menelepon berulang kali.

```gherkin
Scenario: Pembeli melacak status RFQ yang penawarannya sudah terbit
  Given Pembeli membuka halaman "/lacak-rfq"
  When Pembeli menginputkan nomor RFQ "INQ-202609-0001" dan nomor WhatsApp "081234567890"
  And Mengklik "Lacak Status Penawaran"
  Then Sistem memvalidasi data dan menampilkan riwayat status
  And Menampilkan tahapan aktif "Penawaran Resmi Siap" beserta tombol "Unduh Surat Penawaran Resmi (PDF)"

Scenario: Pembeli melacak pesanan yang sedang dikirim via ekspedisi
  Given Pembeli membuka halaman "/lacak-rfq" untuk pesanan berstatus "shipped"
  When Pembeli memasukkan nomor RFQ dan nomor WhatsApp
  Then Sistem menampilkan nama ekspedisi "JNE Trucking", nomor resi "JTR123456789"
  And Menyediakan tombol "Lacak di Website Resmi Ekspedisi" yang langsung membuka tautan tracking ekspedisi
```

---

### US-17: Pembandingan Spesifikasi Produk Side-by-Side oleh Engineer/Kontraktor
- **Sebagai** engineer proyek / kontraktor ME,
- **Saya ingin** mencentang 2 hingga 4 produk pompa/inverter sejenis dan melihat tabel perbandingan parameternya berdampingan,
- **Sehingga** saya dapat memilih part number yang paling tepat sesuai spesifikasi teknis proyek sebelum meminta penawaran.

```gherkin
Scenario: Engineer membandingkan 2 model pompa sentrifugal Ebara
  Given Engineer membuka katalog dan mengklik tombol "Bandingkan" pada Pompa Ebara Model A dan Model B
  When Engineer membuka tray pembanding atau halaman "/compare"
  Then Sistem menampilkan tabel matriks perbandingan berdampingan: Head, Kapasitas Debit, Daya Motor, Voltase, dan Material
  And Engineer dapat mengklik "+ Tambah ke RFQ" pada produk yang sesuai langsung dari tabel pembanding
```

---

### US-18: Ekspor Rekapitulasi Penjualan & Pembayaran Komisi ke Excel
- **Sebagai** staf akuntansi / manajemen keuangan internal,
- **Saya ingin** mengekspor rekap transaksi penjualan bulanan dan daftar pembayaran komisi freelance ke dalam berkas Excel (`.xlsx`),
- **Sehingga** proses pencairan dana komisi mitra dan rekonsiliasi keuangan dapat dijalankan secara efisien dan akurat.

```gherkin
Scenario: Bagian akuntansi mengekspor rekap komisi freelance yang siap bayar
  Given Staf akuntansi membuka menu "Rekap Komisi Freelance" di Filament ERP
  When Staf mengklik tombol "Export to Excel (.xlsx)" dengan filter status "Payable"
  Then Sistem men-generate berkas Excel memuat Nama Mitra, NIK, Nama Bank, Nomor Rekening, Atas Nama, Nomor SO, dan Nominal Komisi
  And Mengunduh file secara instan tanpa membebani memori server
```

---

### US-19: Pencatatan Log Audit Finansial & Otorisasi Otomatis
- **Sebagai** Super Admin / Direktur Utama,
- **Saya ingin** seluruh aktivitas perubahan harga, persetujuan diskon, dan pencairan komisi tercatat dalam log yang tidak dapat diubah,
- **Sehingga** tata kelola keuangan perusahaan terhindar dari manipulasi dan memiliki rekam jejak audit yang valid.

```gherkin
Scenario: Sistem mencatat log audit saat status komisi diubah menjadi Paid
  Given Super Admin membuka detail Sales Order dan mengonfirmasi transfer komisi freelance (status diubah menjadi "paid")
  When Perubahan data tersimpan di database
  Then Spatie Activitylog otomatis mencatat record baru di tabel "activity_log"
  And Menyimpan informasi: User ID Super Admin, IP Address, Timestamp, event "commission_paid", serta perubahan nilai status "payable" ke "paid"
```

---

### US-20: Dominasi Halaman 1 Google Search & Injeksi Schema.org Rich Snippets
- **Sebagai** tim Digital Marketing & Manajemen Perusahaan,
- **Saya ingin** setiap halaman produk, brand, kategori, dan artikel secara otomatis menyematkan data terstruktur Schema.org (JSON-LD), Canonical tag, dan template Title dengan search intent komersial,
- **Sehingga** website perusahaan mendominasi Halaman 1 Google untuk kata kunci distributor resmi dan part number, memicu Rich Snippets, serta menghasilkan aliran prospek B2B organik secara berkelanjutan.

```gherkin
Scenario: Bot Google merayapi halaman detail produk
  Given Bot Google / Pengguna mengakses URL "domain.com/catalog/ebara-100x80-fsja"
  When Halaman di-render oleh server
  Then Tag <title> memuat format: "Distributor Ebara 100x80 FSJA - Jual & Garansi Resmi | [Nama Perusahaan]"
  And Tag <link rel="canonical"> mengarah tepat ke URL kanonikal produk
  And Terdapat skrip <script type="application/ld+json"> memuat Schema "Product", "Offer", dan "BreadcrumbList"
  And Gambar produk memiliki atribut width, height, dan alt spesifik untuk Core Web Vitals (CLS = 0)
```
