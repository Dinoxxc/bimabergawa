# Test Cases: Engine Import Massal Excel, Chunking 100 Baris, & Normalisasi SKU
## Domain 04: Katalog & Manajemen Inventaris (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-CAT-03` |
| **Versi Dokumen** | 1.6.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Data QA Tester | Draf Awal | Pengujian import file spreadsheet produk dasar |
| `1.1.0` | 03 Sep 2026 | Performance & Data Specialist | Ditinjau | Penambahan uji chunking 100 baris & manajemen memori RAM 512 MB |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan normalisasi kode SKU & pelaporan baris error |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 20 Sep 2026 | Principal QA Architect | Disetujui | Penambahan TC-CAT-IMP-017 untuk verifikasi auto-generation SKU internal PBB (PBB-DIV-BRD-XXXX) saat import katalog massal |
| `1.5.0` | 20 Sep 2026 | Principal QA Architect | Disetujui | Penambahan TC-CAT-IMP-018 (proteksi immutabilitas & normalisasi SKU) dan TC-CAT-IMP-019 (pewarisan hierarkis kode divisi kategori) |
| `1.6.0` | 23 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan format SKU 12 karakter [TIPE(1)][DIV(2)][BRAND(3)][URUTAN(6)] tanpa tanda hubung (contoh: SEL001000001) pada TC-CAT-IMP-017 s.d. TC-CAT-IMP-019 |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-CAT-05 s.d. FR-CAT-08)
  - `DOC-PBB-SDD-03` (Service Layer Import Massal Excel & Chunk Processing)
  - `DOC-PBB-MIG-03` (Spesifikasi Engine Import Massal Katalog Excel 100 Baris)
  - `DOC-PBB-QA-04` (Benchmark Performa & Batasan Memori Shared Hosting 512 MB)
- **Modul Sistem:** Modul Engine Import Massal Katalog Excel
- **Target Kelas / Model / Service:**
  - `App\Imports\ProductsBulkImport`
  - `App\Services\BulkSkuNormalizationService`
  - `App\Filament\Resources\ProductResource\Pages\ListProducts`
- **Target Method:** `import()`, `chunk()`, `normalizeSku()`, `validateRows()`, `rollbackChunk()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-CAT-IMP-001`** | Positif | Unduh Berkas Templat Resmi Import Excel Katalog | Panel Produk dibuka | Klik tombol "Unduh Templat Excel (.xlsx)" | Sistem mengunduh berkas template resmi memuat header baku: `brand_name`, `category_name`, `sku`, `name`, `base_hpp`, `recommend_price`, `unit`. | Not Executed |
| **`TC-CAT-IMP-002`** | Positif | Eksekusi Import Massal Excel Data Bersih (500 Baris Produk) | Templat terisi 500 produk valid | Unggah berkas `katalog-500-sku.xlsx` | Sistem memproses data dalam 5 *batch chunk* (100 baris/chunk), 500 produk berhasil masuk ke DB, memori RAM puncak < 64 MB. | Not Executed |
| **`TC-CAT-IMP-003`** | Positif | Normalisasi Otomatis Format Kode SKU (*SKU Normalization*) | Teks SKU di Excel: ` sch-mcb_16a  ` (huruf kecil & spasi) | Import dijalankan | `BulkSkuNormalizationService` menstandarisasi teks menjadi `SCH-MCB-16A` (uppercase, trim whitespace, ganti underscore dengan tanda hubung). | Not Executed |
| **`TC-CAT-IMP-004`** | Positif | Pencocokan Otomatis Nama Brand & Kategori Eksisting | Kolom brand memuat: "Schneider Electric" | Import dijalankan | Sistem mencocokkan string dengan tabel `brands`, menemukan ID yang tepat secara otomatis tanpa harus memasukkan ID numerik manual. | Not Executed |
| **`TC-CAT-IMP-005`** | Positif | Pembaruan Otomatis Produk Eksisting (*Upsert by SKU*) | SKU `SCH-MCB-16A` sudah ada dengan HPP Rp 50.000 | Excel memuat SKU sama dengan HPP baru Rp 55.000 | Sistem tidak membuat baris duplikat, melainkan memperbarui (*update*) data produk lama dengan harga dan spesifikasi baru. | Not Executed |
| **`TC-CAT-IMP-006`** | Positif | Pembuatan Otomatis Brand Baru jika Belum Terdaftar | Kolom brand memuat nama prinsipal baru "Siemens" | Import dijalankan | Sistem secara cerdas membuat entri Brand baru di tabel `brands`, menghasilkan slug unik, dan mengaitkannya ke produk terkait. | Not Executed |
| **`TC-CAT-IMP-007`** | Negatif | Validasi Penolakan Berkas dengan Header Kolom Tidak Standar | File Excel diubah struktur kolomnya (header acak) | Unggah berkas Excel rusak | Sistem menolak proses import dengan pesan error: "Struktur kolom template tidak sesuai. Silakan gunakan templat resmi PBB". | Not Executed |
| **`TC-CAT-IMP-008`** | Negatif | Pelaporan Baris Data Tidak Valid (*Detailed Error Reporting*) | Baris ke-45 memiliki HPP bernilai teks "GRATIS" | Import dijalankan | Sistem melewatkan baris 45, mencatat laporan error: "Baris 45: Nilai Base HPP wajib berupa angka numerik", dan melanjutkan sisa baris lainnya. | Not Executed |
| **`TC-CAT-IMP-009`** | Negatif | Penolakan Unggahan Format Berkas Bukan Spreadsheet | Pengguna mengunggah berkas PDF atau Word | Unggah berkas `daftar-harga.pdf` pada form import | Sistem menolak dengan validasi: "Format berkas wajib berupa XLSX atau CSV". | Not Executed |
| **`TC-CAT-IMP-010`** | Negatif | Penolakan Berkas Spreadsheet Kosong (0 Baris Data) | Berkas template hanya berisi baris header | Unggah berkas kosong | Sistem menampilkan notifikasi informasi: "Berkas spreadsheet tidak memuat baris data produk untuk diimpor". | Not Executed |
| **`TC-CAT-IMP-011`** | Keamanan | Perlindungan Terhadap Formula / CSV Injection (DDE Attack) | Sel Excel memuat formula `=CMD|' /C calc'!A0` | Import dijalankan | Parser menolak atau me-neutralize karakter awalan `=`, `@`, `+`, `-` pada teks deskripsi sehingga formula berbahaya tidak tereksekusi. | Not Executed |
| **`TC-CAT-IMP-012`** | Keamanan | Pembatasan Akses Fitur Import Khusus Role Super Admin & Sales PIC | Staf Logistik mencoba mengakses URL import massal | Request akses endpoint import | Sistem memblokir aksi dengan status HTTP 403 Forbidden. | Not Executed |
| **`TC-CAT-IMP-013`** | Batas Nilai | Import Berkas Ukuran Besar (> 2.000 Baris SKU) | Berkas Excel memuat 2.500 produk | Unggah berkas `katalog-lengkap-2500.xlsx` | Sistem mengeksekusi chunking secara berurutan (25 chunk x 100 baris), seluruh proses selesai dalam < 45 detik tanpa HTTP timeout. | Not Executed |
| **`TC-CAT-IMP-014`** | Transaksi DB | Rollback Transaksi Database pada Kegagalan Chunk Kritis | Terjadi kegagalan koneksi database di tengah chunk 3 | Simulasi query failure pada chunk 3 | Transaksi database pada chunk 3 di-rollback penuh (`DB::rollBack()`), mencegah terciptanya data korup separuh baris. | Not Executed |
| **`TC-CAT-IMP-015`** | Audit | Pencatatan Lengkap Eksekusi Import pada ActivityLog | Import 500 produk selesai | Periksa tabel `activity_log` | Tercatat: `causer_id` (Admin ID), Event (`catalog_bulk_import`), Ringkasan: 480 Berhasil Ditambahkan, 20 Diperbarui, 0 Gagal. | Not Executed |
| **`TC-CAT-IMP-016`** | Notifikasi UI | Ringkasan Hasil Import pada Panel Notifikasi Filament | Proses import selesai di background | Cek panel lonceng notifikasi Filament | Muncul notifikasi hijau formal: "Import Selesai: 500 produk berhasil diproses", memuat tombol untuk mengunduh log jika ada baris error. | Not Executed |
| **`TC-CAT-IMP-017`** | Positif | Pembangkitan Otomatis SKU Internal PBB Berformat 12 Karakter `[TIPE][DIV][BRAND][SEQUENCE]` | Berkas Excel diimpor tanpa kolom SKU internal PBB (hanya memuat model pabrikan) | Import dijalankan | `SkuGeneratorService` via `ProductObserver` secara otomatis menyematkan SKU unik internal 12 karakter `[TIPE(1)][DIV(2)][BRAND(3)][URUTAN(6)]` (contoh: `SEL001000001`) pada setiap produk baru yang tersimpan di basis data. | Not Executed |
| **`TC-CAT-IMP-018`** | Positif | Proteksi Immutabilitas SKU Internal PBB & Normalisasi saat Pembaruan Produk | Produk sudah memiliki SKU internal `SEL001000001` | Import dijalankan dengan kolom SKU bernilai kosong/null | `ProductObserver` mempertahankan SKU asli produk tanpa menimpanya menjadi null, mencegah hilangnya identitas inventaris rak. | Not Executed |
| **`TC-CAT-IMP-019`** | Positif | Pewarisan Hierarkis Kode Divisi Kategori Induk saat Sub-kategori Tidak Berkode | Produk diimpor ke sub-kategori yang kolom `code`-nya kosong, namun kategori induknya memiliki kode `EL` | Import dijalankan | `SkuGeneratorService` menelusuri kategori induk (`parent_id`) dan menggunakan kode divisi `EL`, menghasilkan SKU berformat 12 karakter `SEL[BRAND][SEQUENCE]`. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-CAT-IMP-002 (Eksekusi Chunk Import 100 Baris)

1. **Langkah 1:** Siapkan berkas Excel `test-import-500.xlsx` berisi 500 baris data SKU valid.
2. **Langkah 2:** Buka menu **Katalog -> Produk** -> Klik tombol **"Import Massal Excel"**.
3. **Langkah 3:** Pilih berkas tersebut dan klik tombol **"Mulai Import"**.
4. **Verifikasi Teknis:**
   - Kelas `ProductsBulkImport` mengimplementasikan antarmuka `WithChunkReading` dengan konfigurasi `chunkSize(): int = 100`.
   - Log sistem mencatat eksekusi bertahap: "Processing chunk 1 (rows 1-100)... Chunk 5 (rows 401-500)".
   - Tabel `products` di database bertambah tepat 500 baris baru.
   - Alokasi RAM server PHP selama proses tidak pernah melebihi 64 MB.

### 3.2 Prosedur Uji TC-CAT-IMP-011 (Uji Mitrasi CSV Injection)

1. **Langkah 1:** Buat berkas Excel uji dengan nama produk: `=SUM(1+1)*cmd|' /C notepad'!A0`.
2. **Langkah 2:** Jalankan import berkas tersebut.
3. **Verifikasi Teknis:**
   - Metode `BulkSkuNormalizationService::sanitizeTextField()` mendeteksi awalan karakter formula.
   - String disimpan di basis data sebagai teks polos dengan tanda kutip tunggal pengaman (`'=SUM...`) sehingga aman saat diekspor kembali ke Excel.
