# 01. Skema Basis Data & Desain Pengindeksan (Database Schema & Indexing)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-01` |
| **Versi Dokumen** | 1.4.1 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Database Architect | Draf Awal | Desain skema DDL 14 tabel MySQL 8.0, indexing B-Tree, & Foreign Keys |
| `1.1.0` | 03 Sep 2026 | Senior DBA | Ditinjau | Penambahan tabel `quotation_payment_terms` & kolom pelacakan ekspedisi `delivery_orders` |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Finalisasi skema tabel `commissions` & `commission_payouts` untuk mitra freelance |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.4.0` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan tabel `system_settings` untuk konfigurasi dinamis PPN, margin, & operasional ERP |
| `1.4.1` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan tabel `notifications` untuk arsitektur notifikasi in-app lonceng ERP & email multi-kanal |
| `1.4.2` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan skema tabel `internal_comments` untuk fitur diskusi & catatan koordinasi transaksi berkonteks |

---

## 1. Standar Konvensi Basis Data MySQL 8.0

- **Storage Engine:** `InnoDB` (Dukungan transaksi ACID atomik dan *Row-Level Locking*).
- **Karakter Set & Collation:** `utf8mb4_unicode_ci` (Mendukung karakter internasional, simbol teknik, dan emoji).
- **Penamaan Tabel:** Jamak huruf kecil (*snake_case plural*, contoh: `products`, `inquiry_items`, `delivery_orders`).
- **Kunci Utama (Primary Key):** `unsignedBigInteger('id')->autoIncrement()`.
- **Kunci Asing (Foreign Keys):** `unsignedBigInteger('nama_singular_id')` dengan *Referential Integrity Constraints*.

---

## 2. Kamus Data & Definisi Skema Tabel (Data Dictionary)

### 2.1 Modul Master Produk & Katalog

#### Tabel: `brands`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `name` | VARCHAR(150) | Nama Merk/Prinsipal Resmi (contoh: Ebara, Schneider) |
| `slug` | VARCHAR(180) | Unique, URL Friendly Slug |
| `logo_path` | VARCHAR(255) | Path berkas logo WebP |
| `website_url` | VARCHAR(255) | Nullable, Tautan website resmi prinsipal |
| `description` | TEXT | Nullable, Profil otorisasi brand untuk Programmatic Hub |
| `is_active` | BOOLEAN | Default: true |
| `sort_order` | INT | Default: 0, Urutan tampil di carousel |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `categories`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `parent_id` | BIGINT UNSIGNED | Nullable, Self-referencing FK ke `categories.id` (ON DELETE SET NULL) |
| `name` | VARCHAR(150) | Nama Kategori / Sub-Kategori |
| `slug` | VARCHAR(180) | Unique, URL Friendly Slug |
| `icon_name` | VARCHAR(100) | Nullable, Nama icon Heroicons / SVG |
| `description` | TEXT | Nullable, Narasi pengantar kategori industri |
| `sort_order` | INT | Default: 0 |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `products`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `brand_id` | BIGINT UNSIGNED | Foreign Key ke `brands.id` (ON DELETE RESTRICT) |
| `category_id` | BIGINT UNSIGNED | Foreign Key ke `categories.id` (ON DELETE RESTRICT) |
| `successor_product_id` | BIGINT UNSIGNED | Nullable, Self-referencing FK ke `products.id` (Produk Pengganti) |
| `name` | VARCHAR(255) | Nama Resmi Produk Industri |
| `slug` | VARCHAR(255) | Unique, URL Friendly Slug |
| `model_number` | VARCHAR(150) | Part Number / Tipe Model Pabrikan (Indexed) |
| `cost_price` | DECIMAL(15,2) | HPP Modal Internal Prinsipal (**Tersembunyi dari Publik/Agen**) |
| `description` | LONGTEXT | Deskripsi detail teknis & aplikasi produk |
| `datasheet_path` | VARCHAR(255) | Nullable, Path file brosur PDF resmi |
| `availability_status` | ENUM | `ready_stock`, `indent_principal`, `discontinued` (Default: `ready_stock`) |
| `is_featured` | BOOLEAN | Default: false |
| `is_published` | BOOLEAN | Default: true |
| `meta_description` | VARCHAR(255) | Nullable, SEO fallback description |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `product_specifications`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `product_id` | BIGINT UNSIGNED | Foreign Key ke `products.id` (ON DELETE CASCADE) |
| `spec_key` | VARCHAR(100) | Parameter Teknis (contoh: *Daya Motor, Kapasitas Flow, Voltase*) |
| `spec_value` | VARCHAR(255) | Nilai Parameter (contoh: *5.5 kW / 7.5 HP, 25 m3/h, 380V*) |
| `sort_order` | INT | Default: 0 |

#### Tabel: `product_images`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `product_id` | BIGINT UNSIGNED | Foreign Key ke `products.id` (ON DELETE CASCADE) |
| `image_path` | VARCHAR(255) | Path berkas gambar terkompresi WebP |
| `is_primary` | BOOLEAN | Default: false (Foto Utama) |
| `sort_order` | INT | Default: 0 |

---

### 2.2 Modul Transaksi Komersial, CRM & Pengiriman

#### Tabel: `inquiries` (Leads Ingestion)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `inquiry_number` | VARCHAR(30) | Unique, Format `RFQ-YYYYMM-XXXX` |
| `assigned_user_id` | BIGINT UNSIGNED | Nullable, FK ke `users.id` (Sales PIC terdisposisi) |
| `freelance_sales_id`| BIGINT UNSIGNED | Nullable, FK ke `freelance_sales.id` (Agen Perujuk) |
| `customer_name` | VARCHAR(150) | Nama PIC Pembeli Korporat |
| `company_name` | VARCHAR(200) | Nama Badan Usaha / Pabrik / Kontraktor |
| `phone_wa` | VARCHAR(50) | Nomor WhatsApp (Indexed) |
| `email` | VARCHAR(150) | Nullable, Alamat Email PIC |
| `project_location` | VARCHAR(200) | Nullable, Lokasi Pabrik / Proyek |
| `type` | ENUM | `single_direct`, `bulk_cart`, `manual_offline` |
| `source` | ENUM | `website_rfq`, `direct_whatsapp`, `walk_in_phone`, `exhibition`, `referral` |
| `status` | ENUM | `new`, `contacted`, `quote_draft`, `quote_sent`, `deal`, `lost` |
| `notes` | TEXT | Nullable, Catatan teknis & negosiasi sales |
| `boq_attachment_path`| VARCHAR(255) | Nullable, Path lampiran dokumen tender/BoQ (PDF/Excel) |
| `first_contacted_at`| TIMESTAMP | Nullable, Waktu sales pertama kali mengubah status |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `inquiry_items`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `inquiry_id` | BIGINT UNSIGNED | Foreign Key ke `inquiries.id` (ON DELETE CASCADE) |
| `product_id` | BIGINT UNSIGNED | Nullable, FK ke `products.id` (ON DELETE SET NULL) |
| `item_name` | VARCHAR(255) | Snapshot Nama Barang saat diajukan |
| `model_number` | VARCHAR(150) | Snapshot Part Number / Model Pabrikan |
| `quantity` | INT | Kuantiti unit barang diminta (Min: 1) |
| `custom_notes` | TEXT | Nullable, Catatan kustom spesifikasi / varian |

#### Tabel: `inquiry_telemetries` (Deep Attribution Tracking)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `inquiry_id` | BIGINT UNSIGNED | Unique, Foreign Key ke `inquiries.id` (ON DELETE CASCADE) |
| `utm_source` | VARCHAR(100) | Nullable, misal: google, meta, linkedin |
| `utm_medium` | VARCHAR(100) | Nullable, misal: cpc, banner, organic |
| `utm_campaign` | VARCHAR(150) | Nullable, Nama kampanye iklan |
| `utm_term` | VARCHAR(150) | Nullable, Kata kunci iklan |
| `utm_content` | VARCHAR(150) | Nullable, Variasi materi kreatif |
| `referrer_url` | VARCHAR(255) | Nullable, URL halaman perujuk |
| `landing_page` | VARCHAR(255) | Nullable, URL pertama kali dibuka |
| `device_type` | VARCHAR(50) | mobile, desktop, tablet |
| `ip_address` | VARCHAR(45) | IP Hash / Anonymized Client IP |

#### Tabel: `quotations` (Official Quotation PDF)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `quotation_number` | VARCHAR(30) | Unique, Format `QUO-YYYYMM-XXXX` |
| `revision_number` | INT | Default: 0 (Rev 0, Rev 1, Rev 2) |
| `inquiry_id` | BIGINT UNSIGNED | Foreign Key ke `inquiries.id` (ON DELETE RESTRICT) |
| `user_id` | BIGINT UNSIGNED | Foreign Key ke `users.id` (Sales Pembuat Quote) |
| `freelance_sales_id`| BIGINT UNSIGNED | Nullable, FK ke `freelance_sales.id` |
| `customer_name` | VARCHAR(150) | Snapshot Nama PIC |
| `company_name` | VARCHAR(200) | Snapshot Nama Perusahaan |
| `delivery_terms` | VARCHAR(100) | misal: *Franco Jakarta*, *Loco Gudang* |
| `validity_days` | INT | Default: 14 (Masa berlaku 14 hari) |
| `expires_at` | DATE | Tanggal jatuh tempo penawaran |
| `subtotal` | DECIMAL(15,2) | Total harga kotor barang |
| `discount_amount` | DECIMAL(15,2) | Total potongan diskon global |
| `dpp` | DECIMAL(15,2) | Dasar Pengenaan Pajak (`subtotal - diskon`) |
| `vat_rate_applied` | DECIMAL(5,2) | Default: 11.00 (Snapshot persentase PPN saat dokumen terbit) |
| `tax_amount` | DECIMAL(15,2) | Nilai PPN (`dpp * (vat_rate_applied / 100)`) |
| `grand_total` | DECIMAL(15,2) | Total Nilai Penawaran Resmi (`dpp + tax`) |
| `total_cost` | DECIMAL(15,2) | Total HPP Modal Internal |
| `gross_profit_margin`| DECIMAL(5,2)| Persentase Margin Laba Kotor (%) |
| `is_approved_by_director`| BOOLEAN | Default: false (Approval jika margin < 15% / diskon > 10%) |
| `status` | ENUM | `draft`, `sent`, `approved`, `expired`, `rejected` |
| `pdf_path` | VARCHAR(255) | Nullable, Path berkas PDF tersimpan |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `quotation_payment_terms` (Milestones Engine)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `quotation_id` | BIGINT UNSIGNED | Foreign Key ke `quotations.id` (ON DELETE CASCADE) |
| `phase_name` | VARCHAR(150) | misal: *DP 30% PO*, *50% Sebelum Kirim*, *20% Retensi* |
| `percentage` | DECIMAL(5,2) | Persentase Nilai (Total akumulasi per quote = 100%) |
| `amount` | DECIMAL(15,2) | Nilai nominal rupiah per tahapan |
| `sort_order` | INT | Default: 0 |

#### Tabel: `quotation_items`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `quotation_id` | BIGINT UNSIGNED | Foreign Key ke `quotations.id` (ON DELETE CASCADE) |
| `product_id` | BIGINT UNSIGNED | Nullable, FK ke `products.id` |
| `item_name` | VARCHAR(255) | Nama Produk pada penawaran |
| `model_number` | VARCHAR(150) | Part Number / Model |
| `lead_time` | VARCHAR(100) | misal: *Ready Stock 3 Hari*, *Indent 4-6 Minggu* |
| `quantity` | INT | Jumlah unit |
| `unit_price` | DECIMAL(15,2) | Harga penawaran satuan |
| `cost_price` | DECIMAL(15,2) | HPP Modal Satuan Internal |
| `discount_percent`| DECIMAL(5,2) | Potongan per item (%) |
| `total_price` | DECIMAL(15,2) | `(unit_price * qty) - diskon` |

#### Tabel: `sales_orders`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `order_number` | VARCHAR(30) | Unique, Format `SO-YYYYMM-XXXX` |
| `quotation_id` | BIGINT UNSIGNED | Unique, FK ke `quotations.id` (ON DELETE RESTRICT) |
| `customer_po_number`| VARCHAR(100) | Nomor Purchase Order resmi fisik dari klien |
| `customer_po_date` | DATE | Tanggal penerbitan PO |
| `status` | ENUM | `processing`, `indent_principal`, `ready_to_ship`, `shipped`, `completed`, `cancelled` |
| `vat_rate_applied` | DECIMAL(5,2) | Default: 11.00 (Snapshot PPN transaksi terkunci) |
| `total_amount` | DECIMAL(15,2) | Nilai total pesanan |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `delivery_orders` (Partial Shipment Ekspedisi)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `delivery_number` | VARCHAR(35) | Unique, Format `DO-YYYYMM-XXXX/01` |
| `sales_order_id` | BIGINT UNSIGNED | Foreign Key ke `sales_orders.id` (ON DELETE CASCADE) |
| `courier_name` | VARCHAR(150) | Nama Vendor Ekspedisi (misal: *Baraka, Indah, Dakota*) |
| `tracking_number` | VARCHAR(100) | Nomor Resi / Waybill / AWB Ekspedisi |
| `tracking_url` | VARCHAR(255) | Nullable, URL portal tracking resmi kurir |
| `shipping_address` | TEXT | Alamat Pabrik / Gudang Penerima |
| `recipient_pic_name`| VARCHAR(150)| Nama PIC Penerima Lapangan |
| `recipient_phone` | VARCHAR(50) | Kontak PIC Lapangan |
| `total_packages` | INT | Jumlah Koli / Dus |
| `total_weight_kg` | DECIMAL(8,2) | Nullable, Estimasi Berat Total (Kg) |
| `status` | ENUM | `prepared`, `shipped`, `delivered` |
| `proof_of_delivery_path`| VARCHAR(255)| Nullable, Scan stempel basah tanda terima gudang |
| `shipped_at` | TIMESTAMP | Nullable, Waktu serah terima ke ekspedisi |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

#### Tabel: `delivery_order_items`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `delivery_order_id`| BIGINT UNSIGNED | Foreign Key ke `delivery_orders.id` (ON DELETE CASCADE) |
| `product_id` | BIGINT UNSIGNED | Nullable, FK ke `products.id` |
| `item_name` | VARCHAR(255) | Nama Produk yang dikirim |
| `model_number` | VARCHAR(150) | Part Number |
| `quantity_shipped` | INT | Kuantiti unit yang dikirim pada batch ini |

---

### 2.3 Modul Kemitraan Freelance Sales & Komisi

#### Tabel: `commission_tiers`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `name` | VARCHAR(100) | Nama Tingkat (misal: *Bronze, Silver, Gold, Partner VIP*) |
| `min_turnover_amount`| DECIMAL(15,2)| Ambang Batas Akumulasi Omzet Deal Tahunan (Rp) |
| `commission_rate` | DECIMAL(5,2) | Persentase Komisi Baku (%) |
| `is_active` | BOOLEAN | Default: true |

#### Tabel: `freelance_sales`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `commission_tier_id`| BIGINT UNSIGNED | Foreign Key ke `commission_tiers.id` (ON DELETE RESTRICT) |
| `name` | VARCHAR(150) | Nama Lengkap Mitra |
| `referral_code` | VARCHAR(50) | Unique, Kode Referal Unik (`REF-DIMAS-88`) |
| `nik` | VARCHAR(255) | Encrypted NIK 16 Digit KTP |
| `ktp_file_path` | VARCHAR(255) | Path berkas foto KTP |
| `bank_name` | VARCHAR(100) | Nama Bank Penerima |
| `bank_account_number`| VARCHAR(255)| Encrypted Nomor Rekening Bank |
| `bank_account_name` | VARCHAR(150) | Nama Pemilik Rekening (Sesuai KTP) |
| `industry_focus` | VARCHAR(200) | Sektor Keahlian (F&B, WTP, Otomotif, EPC, dll.) |
| `status` | ENUM | `pending_review`, `approved`, `revision_required`, `rejected`, `suspended` |
| `rejection_reason` | TEXT | Nullable, Alasan penolakan / revisi |
| `approved_at` | TIMESTAMP | Nullable |

#### Tabel: `commission_payouts`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `freelance_sales_id`| BIGINT UNSIGNED | Foreign Key ke `freelance_sales.id` (ON DELETE RESTRICT) |
| `sales_order_id` | BIGINT UNSIGNED | Foreign Key ke `sales_orders.id` (ON DELETE RESTRICT) |
| `amount` | DECIMAL(15,2) | Nominal Hak Komisi (Rp) |
| `status` | ENUM | `pending_payment`, `payable`, `paid` |
| `transfer_proof_path`| VARCHAR(255)| Nullable, Scan slip bukti transfer bank kantor |
| `paid_at` | TIMESTAMP | Nullable, Waktu transfer diselesaikan |

---

### 2.4 Modul Pengaturan Sistem & Konfigurasi Global

#### Tabel: `system_settings`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `key` | VARCHAR(100) | Unique, Kunci identifikasi setting (contoh: `tax_vat_rate`, `whatsapp_hotline`, `critical_margin_threshold`) |
| `value` | LONGTEXT | Nullable, Nilai konfigurasi aktif (tersimpan dalam string/JSON) |
| `group` | VARCHAR(50) | Default: `general`, Kategori: `tax`, `commercial`, `contact`, `company`, `general` (Indexed) |
| `type` | VARCHAR(20) | Default: `string`, Tipe data primitif: `number`, `string`, `boolean`, `text`, `json` |
| `description` | VARCHAR(255) | Nullable, Deskripsi peruntukan pengaturan di panel admin ERP |
| `is_public` | BOOLEAN | Default: false, Bernilai true jika aman dikonsumsi frontend publik |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

---

### 2.5 Modul Notifikasi Terintegrasi & Peringatan Alur Kerja

#### Tabel: `notifications`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | CHAR(36) | Primary Key, UUID Versi 4 |
| `type` | VARCHAR(255) | Nama Kelas Notifikasi (contoh: `App\Notifications\DirectorApprovalRequiredNotification`) |
| `notifiable_type` | VARCHAR(255) | Polymorphic Entity (Default: `App\Models\User`) |
| `notifiable_id` | BIGINT UNSIGNED | ID Pengguna Penerima Notifikasi (Polymorphic ID) |
| `data` | TEXT | Payload JSON Data Notifikasi (judul, ringkasan pesan, icon SVG, tautan aksi dokumen) |
| `read_at` | TIMESTAMP | Nullable, Waktu notifikasi ditandai telah dibaca oleh pengguna |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

---

### 2.6 Modul Diskusi & Catatan Internal Dokumen

#### Tabel: `internal_comments`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `user_id` | BIGINT UNSIGNED | Foreign Key ke `users.id` (Penulis Catatan, ON DELETE CASCADE) |
| `commentable_type` | VARCHAR(255) | Polymorphic Entity (misal: `App\Models\Quotation`, `App\Models\SalesOrder`, `App\Models\Inquiry`, `App\Models\DeliveryOrder`) |
| `commentable_id` | BIGINT UNSIGNED | ID Entitas Transaksi Bersangkutan |
| `comment` | TEXT | Isi Catatan Diskusi / Justifikasi Otorisasi / Info Logistik |
| `type` | ENUM | `general_note`, `approval_justification`, `logistics_update`, `finance_note` (Default: `general_note`) |
| `attachment_path` | VARCHAR(255) | Nullable, Path Berkas Lampiran Pendukung (Bukti Chat Supplier, PDF Penawaran Prinsipal) |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

---

## 3. Strategi Pengindeksan B-Tree Berkinerja Tinggi (Indexing Strategy)

Untuk menjamin performa query katalog instan di Hostinger Premium Web Hosting:

```sql
-- 1. Composite Index untuk Live Search & Faceted Filter Katalog Publik
CREATE INDEX idx_products_catalog_search ON products (is_published, brand_id, category_id, model_number);

-- 2. Index Pencarian Nomor Part Number
CREATE INDEX idx_products_model ON products (model_number);

-- 3. Unique Index Transaksional & Konfigurasi
CREATE UNIQUE INDEX uq_inquiries_number ON inquiries (inquiry_number);
CREATE UNIQUE INDEX uq_quotations_number ON quotations (quotation_number);
CREATE UNIQUE INDEX uq_sales_orders_number ON sales_orders (order_number);
CREATE UNIQUE INDEX uq_delivery_orders_number ON delivery_orders (delivery_number);
CREATE UNIQUE INDEX uq_freelance_referral ON freelance_sales (referral_code);
CREATE UNIQUE INDEX uq_system_settings_key ON system_settings (`key`);

-- 4. Index Telemetri & Pelacakan Prospek
CREATE INDEX idx_inquiries_phone ON inquiries (phone_wa);
CREATE INDEX idx_inquiries_status ON inquiries (status, created_at);
CREATE INDEX idx_quotations_expires ON quotations (status, expires_at);
CREATE INDEX idx_system_settings_group ON system_settings (`group`);

-- 5. Index Notifikasi In-App ERP
CREATE INDEX idx_notifications_notifiable ON notifications (notifiable_type, notifiable_id);
CREATE INDEX idx_notifications_read_at ON notifications (read_at);

-- 6. Index Diskusi & Catatan Internal Dokumen
CREATE INDEX idx_internal_comments_commentable ON internal_comments (commentable_type, commentable_id);
CREATE INDEX idx_internal_comments_user ON internal_comments (user_id);
```
