# Test Cases: Quotation Builder, Live Margin HPP, Snapshot PPN, & Ekspor PDF
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-03` |
| **Versi Dokumen** | 1.5.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Financial QA Tester | Draf Awal | Pengujian kalkulasi penawaran harga dasar |
| `1.1.0` | 03 Sep 2026 | Business Logic Tester | Ditinjau | Penambahan uji live margin HPP & snapshot tarif PPN 11% |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan generate PDF DomPDF & alur revisi nomor dokumen |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 17 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 20 Sep 2026 | Core Service Engineer | Disetujui | Verifikasi lolos 100% Service Layer kalkulasi, live margin guard, PPN dinamis, & termin (TC-ERP-QUO-002, 003, 004, 005, 009, 013, 014, 016) |
| `1.5.0` | 04 Okt 2026 | Principal ERP Engineer | Disetujui | Implementasi penuh Filament Quotation Builder, Live Margin Guard, approval Direktur, DomPDF export, proteksi edit, dan tracking revisi (100% lulus uji otomatis) |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi Quotation Builder, Live Margin Guard, & PDF Export)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-QUOTE-01 s.d. FR-QUOTE-08)
  - `DOC-PBB-SDD-03` (Service Layer Kalkulasi Finansial, HPP Margin, & DomPDF Engine)
  - `DOC-PBB-UIUX-04` (UX Quotation Form Repeater, Live Margin Alert, & Template PDF)
- **Modul Sistem:** Modul Pembuatan Penawaran Harga (Quotation & Commercial Engine)
- **Target Kelas / Controller / Service:**
  - `App\Services\PricingAndMarginCalculatorService`
  - `App\Services\QuotationCalculationService`
  - `App\Services\CommercialCalculationService`
  - `App\Filament\Resources\QuotationResource`
  - `App\Services\PdfExportService`
  - `App\Models\Quotation`, `App\Models\QuotationItem`
- **Target Method:** `calculate()`, `calculateItem()`, `calculateHppMargin()`, `calculateTotals()`, `generateQuotationPdf()`, `createRevision()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-QUO-001`** | Positif | Pembuatan Draf Penawaran Harga (*Quotation Draft*) Multi-Item | Klien terdaftar di DB, master SKU aktif | Buat Quotation -> Tambah 3 SKU dengan kuantitas & harga jual | Data tersimpan di tabel `quotations` & `quotation_items`, total harga subtotal terkalkulasi otomatis. | Passed |
| **`TC-ERP-QUO-002`** | Positif | Kalkulasi Real-Time Live Margin HPP Ideal (Margin >= 15%) | Base HPP = Rp 100.000 | Input Harga Jual = Rp 125.000 (Qty = 10) | Margin = 20.00%. Badge indikator margin berwarna hijau (Aman / Approved for Dispatch). | Passed |
| **`TC-ERP-QUO-003`** | Positif | Peringatan Live Margin Tipis (*Margin Warning*: 8% <= Margin < 15%) | Base HPP = Rp 100.000 | Input Harga Jual = Rp 110.000 (Qty = 10) | Margin = 9.09%. Muncul peringatan badge kuning: "Margin Tipis (9.09%). Disarankan peninjauan ulang diskon." | Passed |
| **`TC-ERP-QUO-004`** | Positif | Blokir & Kewajiban Approval Direksi saat Margin Kritis (< 8%) | Base HPP = Rp 100.000 | Input Harga Jual = Rp 105.000 (Margin = 4.76%) | Badge merah menyala: "Margin Kritis (<8%)". Tombol kirim ke klien terkunci dan mewajibkan eskalasi persetujuan Direktur. | Passed |
| **`TC-ERP-QUO-005`** | Positif | Penerapan Snapshot Pajak PPN 11% Statis (*Tax Immutability*) | Quotation dibuat pada periode tarif PPN 11% | Simpan Quotation | Kolom `vat_rate_applied` terisi `11.00`, nilai PPN = 11% x Subtotal Netto, Grand Total = Subtotal + PPN. Angka ini terkunci permanen. | Passed |
| **`TC-ERP-QUO-006`** | Positif | Pembuatan Berkas PDF Resmi Penawaran Harga (*DomPDF Export*) | Quotation berstatus `approved` | Klik tombol "Cetak / Unduh PDF Quotation" | Sistem menghasilkan berkas PDF berstandar tinggi: Kop surat resmi PBB Rasuna Said, tabel SKU, syarat pembayaran, blok tanda tangan, 0 emoji. | Passed |
| **`TC-ERP-QUO-007`** | Positif | Pengiriman Otomatis PDF Quotation ke Email Klien | Quotation disetujui | Klik aksi "Kirim via Email ke Klien" | Job email mengeksekusi pengiriman berkas PDF terlampir ke alamat email PIC klien dengan status inkuiri berubah menjadi `quoted`. | Passed |
| **`TC-ERP-QUO-008`** | Positif | Alur Pembuatan Revisi Dokumen Penawaran (*Revision Tracking*) | Quotation `PBB/QUO/2026/09/0001` aktif | Klik aksi "Buat Revisi (Revise)" | Sistem membuat salinan quotation baru bernomor `PBB/QUO/2026/09/0001-R1`, status dokumen lama berubah menjadi `superseded`. | Passed |
| **`TC-ERP-QUO-009`** | Negatif | Validasi Penolakan Harga Jual di Bawah HPP (*Minus Margin*) | Base HPP = Rp 100.000 | Input Harga Jual = Rp 90.000 (Margin = -11.1%) | Sistem menolak penyimpanan atau mengunci total dengan status `Hard Rejection: Harga jual di bawah modal dasar (HPP) dilarang`. | Passed |
| **`TC-ERP-QUO-010`** | Negatif | Validasi Nilai Kuantitas Nol atau Negatif | Form item quotation | Input Qty = `0` atau `-5` | Validasi form memunculkan error: "Kuantitas produk minimal 1 pcs". | Passed |
| **`TC-ERP-QUO-011`** | Negatif | Modifikasi Item pada Quotation yang Telah Berstatus Approved/Won | Quotation berstatus `won` atau `converted_to_so` | Pengguna mencoba menyunting harga / kuantitas | Form dalam mode read-only (terkunci), tombol simpan dinonaktifkan untuk menjaga integritas data akuntansi. | Passed |
| **`TC-ERP-QUO-012`** | Keamanan | Penyembunyian Angka HPP dan Persentase Margin dari Peran Non-Otorisasi | Login sebagai staf Logistik atau Tamu | Akses tampilan rincian quotation | Kolom "Base HPP", "Profit Margin (Rp)", dan "Margin (%)" disembunyikan sepenuhnya dari HTML DOM dan respon API. | Passed |
| **`TC-ERP-QUO-013`** | Keamanan | Integritas Kalkulasi Finansial Sisi Server (*Server-Side Recalculation*) | Penyerang memanipulasi field `grand_total` via inspect element | Submit form dengan grand total dimanipulasi jadi Rp 1.000 | `QuotationCalculationService` menghitung ulang seluruh subtotal, PPN, dan grand total di level server, nilai manipulasi client diabaikan. | Passed |
| **`TC-ERP-QUO-014`** | Batas Nilai | Penanganan Diskon Maksimal (Diskon 0% s.d. 99.99%) | Subtotal = Rp 10.000.000 | Input Diskon = 0% dan 50% | Kalkulasi subtotal terdiskon berjalan presisi hingga 2 desimal tanpa pembulatan liar (*floating point precision*). | Passed |
| **`TC-ERP-QUO-015`** | Batas Nilai | Ekspor PDF Quotation dengan Jumlah Item Sangat Banyak (> 50 SKU) | Quotation memuat 60 baris produk | Cetak berkas PDF | DomPDF memecah halaman (*page break*) secara rapi dengan pengulangan header tabel pada halaman ke-2 dan ke-3 tanpa teks tertimpa. | Passed |
| **`TC-ERP-QUO-016`** | Konkurensi | Penomoran Dokumen Penawaran Simultan (*Race Condition Prevention*) | 2 Sales membuat quotation baru di detik yang sama | Klik simpan penawaran baru secara serentak | `DocumentNumberService` menggunakan *Atomic Lock* / `SELECT FOR UPDATE`, menghasilkan nomor unik berurutan `PBB/QUO/2026/09/0001` dan `PBB/QUO/2026/09/0002` tanpa duplikasi. | Passed |
| **`TC-ERP-QUO-017`** | Audit | Pencatatan Riwayat Perubahan Harga pada Jejak Audit | Sales mengubah harga jual dari Rp 120.000 ke Rp 115.000 | Simpan perubahan draf | `activity_log` mencatat mutasi harga beserta User ID pelaksana dan selisih penurunan margin. | Passed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-QUO-004 (Live Margin Kritis & Approval Lock)

1. **Langkah 1:** Buat Quotation baru untuk produk SKU `SCH-MCB-16A` (HPP Master: Rp 50.000).
2. **Langkah 2:** Masukkan Unit Price = `Rp 52.000` (Margin = 3.85% < 8%).
3. **Verifikasi Teknis:**
   - Event reaktif Livewire memicu fungsi `QuotationCalculationService::calculateHppMargin()`.
   - UI merender badge merah: "Margin Kritis: 3.85% - Memerlukan Approval Direksi".
   - Status quotation diatur ke `pending_director_approval`.
   - Tombol aksi "Terbitkan / Kirim ke Klien" dinonaktifkan hingga Direktur memberikan approval.

### 3.2 Prosedur Uji TC-ERP-QUO-006 (Inspeksi Dokumen PDF Resmi)

1. **Langkah 1:** Buka quotation berstatus `approved` bernomor `PBB/QUO/2026/09/0001`.
2. **Langkah 2:** Klik tombol **"Unduh PDF"**.
3. **Verifikasi Teknis:**
   - Header memuat Logo PBB, Nomor Dokumen, Tanggal, dan Alamat Rasuna Said lengkap.
   - Tabel memuat No, Kode SKU, Deskripsi, Qty, Satuan, Harga Satuan, dan Total Harga.
   - Baris footer memuat Subtotal, PPN 11% (`vat_rate_applied`), dan Grand Total.
   - Footer memuat catatan masa berlaku penawaran (14 hari kerja) dan rekening bank resmi PBB.
