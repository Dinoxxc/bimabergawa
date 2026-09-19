# 01. Skema Basis Data & Desain Pengindeksan (Database Schema & Indexing)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-01` |
| **Versi Dokumen** | 1.6.0 |
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
| `1.4.3` | 06 Sep 2026 | Principal System Architect | Disetujui | Penambahan kolom `mentioned_user_ids` (JSON) dan dual-layer visibility (`internal`, `agent_visible`, `system`) pada `internal_comments` |
| `1.5.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan softDeletes pada tabel kritis, berkas PO klien, catatan evaluasi direksi, multi-currency, modul faktur penagihan (`invoices`, `invoice_items`), dan audit log sistem (`activity_logs`) |
| `1.5.1` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan Bagian 4 (5 Evaluasi Arsitektural & Mitigasi Risiko Teknis) serta kolom `paid_amount` pada tabel `invoices` |
| `1.6.0` | 19 Sep 2026 | Principal System Architect | Disetujui | Penambahan tabel `units_of_measure` (Master Satuan Dinamis), kolom `uom`, `project_name`, `company_npwp`, `faktur_pajak_number`, `driver_name`, `vehicle_plate_number`, `moq`, `warranty_period`, serta kustomisasi spesifikasi per item (`spec_group`, `spec_unit`, `is_highlight`, `custom_specifications`) untuk kesiapan B2B General Industrial Supplier |

---

## 1. Standar Konvensi Basis Data MySQL 8.0

- **Storage Engine:** `InnoDB` (Dukungan transaksi ACID atomik dan *Row-Level Locking*).
- **Karakter Set & Collation:** `utf8mb4_unicode_ci` (Mendukung karakter internasional, simbol teknik, dan emoji).
- **Penamaan Tabel:** Jamak huruf kecil (*snake_case plural*, contoh: `products`, `inquiry_items`, `delivery_orders`).
- **Kunci Utama (Primary Key):** `unsignedBigInteger('id')->autoIncrement()`.
- **Kunci Asing (Foreign Keys):** `unsignedBigInteger('nama_singular_id')` dengan *Referential Integrity Constraints*.

---

## 2. Kamus Data & Definisi Skema Tabel (Data Dictionary)

### 2.0 Modul Master Satuan Dinamis (Unit of Measure)

#### Tabel: `units_of_measure`
Tabel master untuk menyimpan seluruh satuan pengukuran unit (UOM) dinamis multi-industri (Elektrikal, Mekanikal, K3, MRO, Kimia, Fasilitas Pabrik):

| Nama Kolom | Tipe Data | Keterangan & Batasan Teknis |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment. |
| `code` | `VARCHAR(20)` | Unique, indeks, kode satuan (cth: `pcs`, `meter`, `batang`, `set`, `roll`, `box`, `pack`, `pail`, `drum`, `liter`, `kg`, `pasang`, `lot`, `can`, `tube`). |
| `name` | `VARCHAR(100)` | Nama lengkap satuan resmi (cth: *Pieces, Meter Lari, Batang (2.4m/3m), Set Lengkap, Roll, Pail (20 Liter), Drum (200 Liter), Pasang / Pair, Lot Proyek*). |
| `description` | `VARCHAR(255)` | Nullable, penjelasan penggunaan satuan. |
| `is_active` | `BOOLEAN` | Default `true`, status aktif satuan di antarmuka ERP. |
| `sort_order` | `INTEGER` | Default `0`, urutan prioritas pada dropdown pilihan. |
| `created_at` | `TIMESTAMP` | Waktu pencatatan. |
| `updated_at` | `TIMESTAMP` | Waktu pembaruan. |
| `deleted_at` | `TIMESTAMP` | Nullable, Soft Deletes. |

---

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
| `currency` | VARCHAR(3) | Default: 'IDR' (Mata Uang Transaksi: IDR, USD, EUR) |
| `meta_description` | VARCHAR(255) | Nullable, SEO fallback description |
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | Standar Timestamps & Soft Deletes (`deleted_at` nullable) |

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
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | Standar Timestamps & Soft Deletes (`deleted_at` nullable) |

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
| `currency` | VARCHAR(3) | Default: 'IDR' (Mata uang penawaran resmi) |
| `director_notes` | TEXT | Nullable, Catatan evaluasi/instruksi persetujuan atau penolakan Direktur |
| `pdf_path` | VARCHAR(255) | Nullable, Path berkas PDF tersimpan |
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | Standar Timestamps & Soft Deletes (`deleted_at` nullable) |

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
| `currency` | VARCHAR(3) | Default: 'IDR' (Mata uang pesanan penjualan) |
| `customer_po_file_path` | VARCHAR(255) | Nullable, Path unggahan berkas fisik scan/PDF PO resmi dari klien |
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | Standar Timestamps & Soft Deletes (`deleted_at` nullable) |

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
| `comment` | TEXT | Isi Catatan Diskusi / Justifikasi Otorisasi / Info Logistik / Pesan ke Agen |
| `type` | VARCHAR(30) | Kategori & Visibilitas: `internal` (hanya staf internal PBB), `agent_visible` (dapat dilihat oleh mitra agen pada inkuiri miliknya & sales pic), `system` (catatan audit/event otomatis sistem) (Default: `internal`) |
| `mentioned_user_ids` | JSON | Nullable, Array ID pengguna yang di-mention (cth: `[2, 5]`) untuk trigger notifikasi instan & filter kueri |
| `attachment_path` | VARCHAR(255) | Nullable, Path Berkas Lampiran Pendukung (Bukti Chat Supplier, PDF Penawaran Prinsipal) |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

---

---

### 2.7 Modul Faktur Penagihan Komersial & Verifikasi Pembayaran

Modul Faktur Penagihan (*Commercial Invoicing*) mengelola siklus penagihan resmi kepada kontraktor/klien berdasarkan Surat Pesanan (*Sales Order*) atau termin pembayaran (*Quotation Payment Terms*), serta pencatatan bukti pelunasan transfer bank.

#### Tabel: `invoices` (Faktur Penagihan Resmi)
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `invoice_number` | VARCHAR(35) | Unique, Format `INV-YYYYMM-XXXX` |
| `sales_order_id` | BIGINT UNSIGNED | Foreign Key ke `sales_orders.id` (ON DELETE RESTRICT) |
| `quotation_id` | BIGINT UNSIGNED | Nullable, Foreign Key ke `quotations.id` (ON DELETE RESTRICT) |
| `payment_term_id` | BIGINT UNSIGNED | Nullable, FK ke `quotation_payment_terms.id` (ON DELETE SET NULL) |
| `customer_name` | VARCHAR(150) | Snapshot Nama PIC Penagihan |
| `company_name` | VARCHAR(200) | Snapshot Nama Badan Usaha / PT / CV Pembeli |
| `invoice_date` | DATE | Tanggal resmi penerbitan faktur tagihan |
| `due_date` | DATE | Tanggal jatuh tempo kredit (Net 14, Net 30, Net 60, CBD) |
| `subtotal` | DECIMAL(15,2) | Nilai tagihan kotor sebelum pajak |
| `vat_rate_applied` | DECIMAL(5,2) | Default: 11.00 (Snapshot persentase PPN saat invoice terbit) |
| `tax_amount` | DECIMAL(15,2) | Nilai nominal PPN |
| `grand_total` | DECIMAL(15,2) | Total Nilai Tagihan Bersih (`subtotal + tax_amount`) |
| `currency` | VARCHAR(3) | Default: 'IDR' (Mata uang faktur) |
| `status` | ENUM | `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled` (Default: `unpaid`, Indexed) |
| `payment_method` | VARCHAR(100) | Nullable, misal: *Bank Transfer BCA, Mandiri, Giro Tempo* |
| `payment_proof_path` | VARCHAR(255) | Nullable, Path unggahan berkas bukti transfer bank |
| `paid_at` | TIMESTAMP | Nullable, Waktu verifikasi penerimaan pembayaran oleh Finance |
| `notes` | TEXT | Nullable, Instruksi rekening tujuan transfer & keterangan faktur |
| `pdf_path` | VARCHAR(255) | Nullable, Path berkas cetak PDF Invoice standar A4 Rasuna Said |
| `created_at`, `updated_at`, `deleted_at` | TIMESTAMP | Standar Timestamps & Soft Deletes (`deleted_at` nullable) |

#### Tabel: `invoice_items`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `invoice_id` | BIGINT UNSIGNED | Foreign Key ke `invoices.id` (ON DELETE CASCADE) |
| `product_id` | BIGINT UNSIGNED | Nullable, Foreign Key ke `products.id` (ON DELETE SET NULL) |
| `item_name` | VARCHAR(255) | Nama barang atau uraian termin (misal: *Termin 1 DP 30% Proyek X*) |
| `model_number` | VARCHAR(150) | Nullable, Part Number / Model Pabrikan |
| `quantity` | INT | Kuantiti unit barang ditagihkan (Default: 1) |
| `unit_price` | DECIMAL(15,2) | Nilai nominal satuan |
| `total_price` | DECIMAL(15,2) | Total nilai item (`quantity * unit_price`) |
| `created_at`, `updated_at` | TIMESTAMP | Standar Laravel Timestamps |

---

### 2.8 Modul Jejak Audit Sistem Otomatis (System Audit Trail)

Menyediakan pencatatan jejak audit (*immutable audit trail*) untuk mematuhi tata kelola enterprise dan merekam setiap mutasi data sensitif secara otomatis:

#### Tabel: `activity_logs`
| Nama Kolom | Tipe Data | Keterangan & Atribut |
|:---|:---|:---|
| `id` | BIGINT UNSIGNED | Primary Key, Auto Increment |
| `log_name` | VARCHAR(100) | Nullable, Kategori log (misal: `quotation`, `sales_order`, `system_settings`, `auth`) |
| `description` | TEXT | Narasi peristiwa mutasi (misal: *Perubahan tarif PPN dari 11% menjadi 12%*) |
| `subject_type` | VARCHAR(255) | Nullable, Nama model target yang berubah (misal: `App\Models\Quotation`) |
| `subject_id` | BIGINT UNSIGNED | Nullable, ID record model target |
| `causer_type` | VARCHAR(255) | Nullable, Kelas pengguna eksekutor (`App\Models\User`) |
| `causer_id` | BIGINT UNSIGNED | Nullable, ID pengguna yang melakukan perubahan |
| `properties` | JSON | Nullable, Snapshot atribut data sebelum dan sesudah (`{"old": {...}, "new": {...}}`) |
| `ip_address` | VARCHAR(45) | Nullable, Alamat IP klien eksekutor |
| `user_agent` | TEXT | Nullable, Header identitas peramban |
| `created_at` | TIMESTAMP | Waktu mutasi terjadi (Indexed) |

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
CREATE UNIQUE INDEX uq_invoices_number ON invoices (invoice_number);

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

-- 7. Index Modul Faktur Penagihan (Invoices)
CREATE INDEX idx_invoices_status_due ON invoices (status, due_date);
CREATE INDEX idx_invoices_sales_order ON invoices (sales_order_id);

-- 8. Index Jejak Audit Sistem (Activity Logs)
CREATE INDEX idx_activity_subject ON activity_logs (subject_type, subject_id);
CREATE INDEX idx_activity_causer ON activity_logs (causer_type, causer_id);
CREATE INDEX idx_activity_created ON activity_logs (created_at);
```

---

## 4. Evaluasi Arsitektural, Mitigasi Risiko Teknis, & Panduan Fase 2

Berdasarkan audit kelayakan komersial menyeluruh pada tahap fondasi awal (*Early Foundation Stage*), berikut 5 evaluasi arsitektural penting dan standar mitigasi yang ditetapkan untuk memitigasi potensi risiko operasional sistem:

### 4.1 Mitigasi Indeks Unik MySQL vs Soft Deletes (Unique Slug Resolution)
- **Analisis Risiko:** MySQL InnoDB memberlakukan kekangan `UNIQUE` pada seluruh baris fisik tanpa memedulikan status `deleted_at IS NOT NULL`. Jika produk dengan slug `mcb-schneider-16a` di-soft delete, pembuatan produk baru dengan nama/slug yang sama akan memicu kesalahan SQL `1062 Duplicate entry`.
- **Standar Mitigasi (Observer Pattern):**
  1. Pada `ProductObserver` / `SluggableService`, sistem mendeteksi keberadaan slug yang bertabrakan dengan entitas terhapus (`withTrashed()`).
  2. Saat model di-soft delete, event `deleting` secara otomatis memperbarui slug menjadi format unik historis:
     ```php
     $product->slug = $product->slug . '-deleted-' . time();
     $product->saveQuietly();
     ```
  3. Dengan demikian, slug utama kembali bebas dan dapat digunakan kembali untuk produk katalog baru tanpa melanggar kekangan basis data.

### 4.2 Strategi Master Pelanggan Denormalized vs Customer Master Directory
- **Analisis Trade-Off:** Data kontak pembeli pada `inquiries`, `quotations`, dan `invoices` disimpan secara denormalized (`customer_name`, `company_name`, `phone_wa`, `email`). Pendekatan ini sengaja diadopsi agar alur pengajuan penawaran RFQ di web publik bersifat *frictionless* (calon klien B2B tidak dibebani kewajiban pendaftaran akun/password).
- **Rencana Skalabilitas ERP:** Untuk mengantisipasi kebutuhan kontraktor repeat order (misal: analisis Lifetime Value, batas pagu kredit / *Credit Limit*, dan NPWP penagihan tetap), layer Eloquent pada Fase 1.2 dirancang fleksibel sehingga model `Customer` dapat diperkenalkan di masa mendatang dengan relasi opsional (`customer_id nullable`) tanpa merombak skema tabel transaksional yang sudah berjalan.

### 4.3 Standar Pembayaran Parsial pada Faktur Tagihan (`paid_amount` & `remaining_balance`)
- **Analisis Kebutuhan Finansial:** Dalam transaksi B2B bernilai puluhan/ratusan juta rupiah, klien kerap melakukan pembayaran bertahap (misal: transfer awal sebagian dana karena batasan limit harian RTGS bank).
- **Implementasi Skema:** Tabel `invoices` dilengkapi kolom fisik `paid_amount DECIMAL(15,2) DEFAULT 0.00`.
  - Status `partially_paid` berlaku jika `paid_amount > 0` dan `paid_amount < grand_total`.
  - Status `paid` berlaku otomatis saat `paid_amount >= grand_total`.
  - Nilai sisa piutang dihitung secara reaktif melalui Eloquent Accessor:
    ```php
    public function getRemainingBalanceAttribute(): float
    {
        return (float) max(0, $this->grand_total - $this->paid_amount);
    }
    ```

### 4.4 Isolasi Disk Penyimpanan Privat Dokumen Sensitif UU PDP
- **Analisis Keamanan Data:** Berkas identitas pribadi (KTP agen freelance), bukti transfer bank (`payment_proof_path`), dan surat Purchase Order fisik klien (`customer_po_file_path`) tergolong data konfidensial yang wajib dilindungi sesuai UU PDP No. 27/2022.
- **Standar Arsitektur Storage:**
  1. Dilarang keras menyimpan dokumen sensitif pada disk `public` (`storage/app/public`) yang terhubung dengan tautan simbolik web publik.
  2. Seluruh dokumen sensitif wajib disimpan pada disk terenkripsi `private` (`storage/app/private/`).
  3. Pengunduhan berkas hanya dapat diakses melalui controller privat dengan otentikasi sesi staf internal, verifikasi peran Spatie RBAC, dan URL bertanda tangan sementara (*Temporary Signed URL* berumur maksimal 300 detik).

### 4.5 Efisiensi Memori RAM Shared Hosting 512 MB melalui Enforced Morph Map
- **Analisis Kinerja Server:** Tabel audit `activity_logs`, `internal_comments`, dan `notifications` menggunakan relasi polimorfik (`subject_type`, `notifiable_type`, `commentable_type`). Penyimpanan nama kelas lengkap (*Fully Qualified Class Name* / FQCN sepanjang 255 karakter) membebani alokasi buffer pool memori InnoDB RAM 512 MB pada server shared hosting.
- **Standar Implementasi:**
  Mendaftarkan pemetaan alias pendek (*Enforce Morph Map*) pada `App\Providers\AppServiceProvider`:
  ```php
  use Illuminate\Database\Eloquent\Relations\Relation;

  Relation::enforceMorphMap([
      'user' => \App\Models\User::class,
      'product' => \App\Models\Product::class,
      'inquiry' => \App\Models\Inquiry::class,
      'quotation' => \App\Models\Quotation::class,
      'sales_order' => \App\Models\SalesOrder::class,
      'delivery_order' => \App\Models\DeliveryOrder::class,
      'invoice' => \App\Models\Invoice::class,
      'setting' => \App\Models\SystemSetting::class,
  ]);
  ```
  Langkah ini mereduksi ukuran penyimpanan indeks string komposit hingga 75% dan mencegah kegagalan *Out of Memory* pada server LiteSpeed.

