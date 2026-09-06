# Test Cases: Master Produk SKU, Pipeline Media WebP, & Sanitasi EXIF
## Domain 04: Katalog & Manajemen Inventaris (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-CAT-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Media QA Tester | Draf Awal | Pengujian input master produk & upload gambar dasar |
| `1.1.0` | 03 Sep 2026 | Image Optimization Engineer | Ditinjau | Penambahan uji pipeline konversi WebP & penghapusan metadata EXIF |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan validasi keunikan SKU & upload berkas datasheet PDF |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-CAT-01 s.d. FR-CAT-06)
  - `DOC-PBB-SDD-03` (Service Layer Media Optimization & Intervention Image Driver)
  - `DOC-PBB-SEC-03` (Pengerasan Unggahan Berkas, Magic Bytes, & Proteksi RCE)
  - `DOC-PBB-MIG-04` (Spesifikasi Pipeline Optimasi WebP & Manajemen Media)
- **Modul Sistem:** Modul Master Produk & Pipeline Konversi Media
- **Target Kelas / Model / Service:**
  - `App\Filament\Resources\ProductResource`
  - `App\Services\ProductImageProcessingService`
  - `App\Models\Product`
- **Target Method:** `processAndStore()`, `scaleDown()`, `toWebp()`, `stripExif()`, `storeDatasheet()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-CAT-PRD-001`** | Positif | Pembuatan Produk Baru Lengkap dengan Gambar & Datasheet | Brand & Kategori aktif ada di DB | Input: SKU = "SCH-MCB-16A", Nama, Base HPP, Harga Rekomendasi, Satuan "PCS", Upload JPG 4MB, Upload PDF | Produk tersimpan, gambar terkonversi jadi WebP kualitas 82% (< 250 KB), datasheet PDF tersimpan, status `is_active = true`. | Not Executed |
| **`TC-CAT-PRD-002`** | Positif | Konversi Otomatis Gambar Format JPEG/PNG ke WebP Murni | Gambar sumber format PNG transparan (3.5 MB) | Unggah pada form foto produk | Service `ProductImageProcessingService` mengonversi berkas ke format `.webp` asli, ukuran berkas terpangkas > 75% tanpa kehilangan detail visual. | Not Executed |
| **`TC-CAT-PRD-003`** | Positif | Penskalaan Resolusi Maksimal (*Dimension Constraint <= 1600px*) | Unggah foto resolusi tinggi (4000 x 3000 piksel) | Simpan produk | Gambar diubah skalanya menjadi lebar tepat 1600px dengan aspek rasio tetap proporsional (1600 x 1200 piksel). | Not Executed |
| **`TC-CAT-PRD-004`** | Positif | Penghapusan Otomatis Metadata EXIF & Koordinat GPS | Foto diambil dari kamera smartphone (memuat data GPS & model HP) | Unggah foto ke katalog produk | Proses rasterisasi menghapus seluruh metadata EXIF, header berkas bersih dari informasi geolokasi maupun nama perangkat. | Not Executed |
| **`TC-CAT-PRD-005`** | Positif | Penamaan Berkas Acak Menggunakan UUID (*UUID Renaming*) | Nama berkas asli: `Foto MCB Proyek Gedung_12.jpg` | Simpan produk | Berkas tersimpan di disk publik dengan nama aman: `catalog/8f14e45f-1234-4a56-b789-0123456789ab.webp`. | Not Executed |
| **`TC-CAT-PRD-006`** | Positif | Pengunggahan Berkas Datasheet PDF Teknis | Berkas PDF spesifikasi pabrik (2.1 MB) | Unggah pada field `datasheet_file` | Berkas tersimpan di folder `datasheets/uuid.pdf`, tautan unduh muncul pada halaman detail produk web publik. | Not Executed |
| **`TC-CAT-PRD-007`** | Positif | Fitur Soft Delete & Pemulihan Data Master Produk (*Restore*) | Produk aktif ada di database | Klik aksi "Hapus Produk" -> Konfirmasi | Produk masuk ke status *Soft Deleted* (`deleted_at != null`), hilang dari katalog publik, dapat dipulihkan kembali oleh Super Admin. | Not Executed |
| **`TC-CAT-PRD-008`** | Negatif | Validasi Penolakan Duplikasi Nomor SKU (*Duplicate SKU*) | SKU `SCH-MCB-16A` sudah terdaftar | Coba buat produk baru dengan SKU sama persis | Validasi form memunculkan error HTTP 422: "Kode SKU ini telah digunakan oleh produk lain". | Not Executed |
| **`TC-CAT-PRD-009`** | Negatif | Validasi Penolakan Base HPP Nol atau Negatif | Form master produk | Input Base HPP = `Rp 0` atau `-50000` | Sistem menolak dengan error validasi: "Harga Pokok Penjualan (HPP) modal dasar wajib bernilai positif lebih dari 0". | Not Executed |
| **`TC-CAT-PRD-010`** | Negatif | Validasi Penolakan Berkas Unggahan Gambar Rusak (*Corrupt Image*) | Berkas JPG 0 bytes atau berkas teks yang diubah nama jadi `.jpg` | Unggah berkas pada form gambar | Image Driver melempar pengecualian `NotReadableException`, sistem memunculkan pesan ramah: "Berkas gambar rusak atau tidak dapat dibaca". | Not Executed |
| **`TC-CAT-PRD-011`** | Negatif | Penolakan Unggahan Gambar Melebihi Batas Dimensi Memori Server | Gambar berukuran raksasa 50 Megapiksel (10.000 x 5.000 px) | Coba unggah ke panel Filament | Sistem memvalidasi dimensi maksimum sebelum alokasi RAM penuh (512 MB) dan menolak berkas dengan pesan informatif. | Not Executed |
| **`TC-CAT-PRD-012`** | Keamanan | Penolakan Penyamaran Skrip Berbahaya pada Foto (*MIME Magic Byte*) | Berkas skrip PHP dinamai `product.jpg` (Header teks PHP) | Unggah ke form produk | Validasi Magic Byte mendeteksi bahwa berkas bukan citra biner JPEG/PNG sah dan membatalkan unggahan seketika. | Not Executed |
| **`TC-CAT-PRD-013`** | Keamanan | Larangan Karakter Path Traversal pada Nama SKU | Form input SKU | Input SKU = `../../etc/passwd` atau `SCH/MCB\16A` | Skrip sanitasi membersihkan karakter pemisah path, SKU distandarisasi menjadi karakter alfanumerik aman `SCH-MCB-16A`. | Not Executed |
| **`TC-CAT-PRD-014`** | Batas Nilai | Penanganan Deskripsi Teknis Produk Sangat Panjang (> 5.000 Karakter) | Produk industri dengan tabel parameter rumit | Input teks spesifikasi detail pada Rich Editor | Kolom `TEXT` / `LONGTEXT` di MySQL menyimpan seluruh format tabel HTML dengan aman tanpa terpotong (*no truncation*). | Not Executed |
| **`TC-CAT-PRD-015`** | Zero Emoji | Penegakan Larangan Emoji pada Judul & Spesifikasi Produk | Admin memasukkan teks berisi icon emoji | Input Nama Produk: "Schneider MCB 16A Kualitas Terbaik" | Validasi regex menolak emoji atau mutator otomatis membersihkan karakter non-ASCII sebelum penyimpanan ke basis data. | Not Executed |
| **`TC-CAT-PRD-016`** | Audit | Pencatatan Perubahan Base HPP pada Spatie ActivityLog | Admin mengubah Base HPP dari Rp 50.000 ke Rp 55.000 | Simpan produk | `activity_log` mencatat: `causer_id` (Admin ID), Event (`product_hpp_updated`), Nilai HPP lama, Nilai HPP baru, dan Waktu mutasi. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-CAT-PRD-002 (Verifikasi Konversi WebP & EXIF Stripping)

1. **Langkah 1:** Siapkan foto kamera ponsel berformat JPEG resolusi 3000x2000 piksel dengan metadata GPS aktif (Ukuran: 3.2 MB).
2. **Langkah 2:** Unggah foto tersebut pada pembuatan produk SKU `PHI-LED-12W`.
3. **Langkah 3:** Simpan produk dan catat path berkas yang dihasilkan pada tabel database `products.image_path` (misal: `catalog/abc-123.webp`).
4. **Verifikasi Teknis:**
   - Ekstensi berkas di disk penyimpanan adalah `.webp`.
   - Jalankan tool inspeksi EXIF via CLI:
     ```bash
     exiftool public/storage/catalog/abc-123.webp
     ```
   - Hasil inspeksi menunjukkan 0 tag GPS Latitude/Longitude dan 0 tag Camera Make/Model.
   - Ukuran berkas terkompresi berada di bawah 250 KB.

### 3.2 Prosedur Uji TC-CAT-PRD-008 (Verifikasi Keunikan SKU)

1. **Langkah 1:** Buat produk baru dengan SKU `SCH-MCB-16A` (sukses tersimpan).
2. **Langkah 2:** Buka kembali form produk baru dan masukkan SKU yang sama persis: `SCH-MCB-16A`.
3. **Langkah 3:** Klik tombol simpan.
4. **Verifikasi Teknis:**
   - Request mengembalikan validasi error `HTTP 422 Unprocessable Entity`.
   - Field SKU menampilkan border merah dan pesan: "Kode SKU ini telah digunakan".
