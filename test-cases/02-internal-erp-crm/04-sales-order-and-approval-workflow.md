# Test Cases: Sales Order, Approval Direksi, & Alur Komersial
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Sales Operations QA | Draf Awal | Pengujian pembuatan Sales Order standar |
| `1.1.0` | 03 Sep 2026 | Workflow Specialist | Ditinjau | Penambahan uji batas persetujuan Direksi & verifikasi PO klien |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan penguncian komisi agen saat status SO approved |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi Sales Order, Customer PO, & Approval Workflow)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-SO-01 s.d. FR-SO-08)
  - `DOC-PBB-SDD-03` (Service Layer Sales Order & State Machine Transitions)
  - `DOC-PBB-SDD-04` (Filament Resource Sales Order & Action Hooks)
- **Modul Sistem:** Modul Pemrosesan Pesanan Penjualan (Sales Order Processing)
- **Target Kelas / Controller / Service:**
  - `App\Filament\Resources\SalesOrderResource`
  - `App\Services\SalesOrderWorkflowService`
  - `App\Services\DocumentNumberService`
  - `App\Models\SalesOrder`, `App\Models\Quotation`
- **Target Method:** `convertFromQuotation()`, `approveSalesOrder()`, `rejectSalesOrder()`, `cancelSalesOrder()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-SO-001`** | Positif | Konversi Quotation Menjadi Sales Order Resmi | Quotation berstatus `approved` atau `won` | Klik aksi "Konversi ke Sales Order" pada Quotation | Sistem membuat record `sales_orders` baru, nomor urut `PBB/SOR/2026/092F9C/0001` diterbitkan, seluruh item dan harga tersalin presisi. | Not Executed |
| **`TC-ERP-SO-002`** | Positif | Pengisian Nomor PO Klien & Unggah Berkas PO Resmi | Sales Order baru dibuat | Masukkan Nomor PO: `PO-KTR-2026-098` + Unggah PDF PO | Field `customer_po_number` dan `customer_po_file` tersimpan, berkas terenkripsi aman di private storage. | Not Executed |
| **`TC-ERP-SO-003`** | Positif | Persetujuan Sales Order Otomatis (Nilai < Rp 100 Juta & Margin Aman) | Nilai SO = Rp 45.000.000 (Margin = 18%) | Simpan & Ajukan SO | SO langsung berstatus `approved`, siap diteruskan ke Departemen Gudang/Logistik untuk pembuatan Surat Jalan. | Not Executed |
| **`TC-ERP-SO-004`** | Positif | Eskalasi Kewajiban Persetujuan Direktur (Nilai >= Rp 100 Juta) | Nilai SO = Rp 150.000.000 | Simpan & Ajukan SO | Status SO diatur ke `pending_director_approval`, notifikasi eskalasi dikirim ke dashboard Direktur. | Not Executed |
| **`TC-ERP-SO-005`** | Positif | Eksekusi Approval oleh Akun Direktur / Super Admin | SO berstatus `pending_director_approval` | Login sebagai Direktur -> Klik tombol "Setujui Sales Order" | Status berubah menjadi `approved`, field `approved_by` dan `approved_at` terisi, alur komisi agen terkait terkunci. | Not Executed |
| **`TC-ERP-SO-006`** | Positif | Penerbitan Berkas PDF Sales Order Resmi | SO berstatus `approved` | Klik aksi "Cetak Sales Order" | Sistem menghasilkan berkas PDF SO memuat kop surat resmi PBB Rasuna Said, nomor PO klien, rincian produk, dan paraf persetujuan. | Not Executed |
| **`TC-ERP-SO-007`** | Positif | Penguncian Komisi Agen Freelance saat SO Approved | Inkuiri berasal dari rujukan Agen Freelance | SO berstatus `approved` | Sistem membuat entri draf pencairan pada tabel `commission_ledgers` dengan persentase komisi terkunci permanen. | Not Executed |
| **`TC-ERP-SO-008`** | Negatif | Validasi Penolakan Konversi dari Quotation yang Ditolak (*Rejected*) | Quotation berstatus `rejected` atau `draft` | Coba akses endpoint konversi SO | Sistem menolak aksi dengan pesan error: "Hanya penawaran berstatus disetujui/won yang dapat dikonversi ke Sales Order". | Not Executed |
| **`TC-ERP-SO-009`** | Negatif | Penolakan Approval SO oleh Sales Biasa (*Unauthorized Role*) | SO butuh approval Direksi | Login sebagai Sales PIC -> Coba trigger aksi approve | Tombol aksi disembunyikan; request langsung mengembalikan status HTTP 403 Forbidden. | Not Executed |
| **`TC-ERP-SO-010`** | Negatif | Pembatalan Sales Order dengan Alasan Kosong | SO berstatus `processing` | Klik aksi "Batalkan SO" -> Kosongkan alasan | Validasi form menolak pembatalan dengan pesan: "Alasan pembatalan Sales Order wajib diisi". | Not Executed |
| **`TC-ERP-SO-011`** | Negatif | Penyuntingan Item/Harga pada Sales Order yang Telah Approved | SO berstatus `approved` | Pengguna mencoba menyunting harga jual | Form dikunci permanen (*read-only*), mencegah perubahan finansial sepihak setelah persetujuan resmi. | Not Executed |
| **`TC-ERP-SO-012`** | Keamanan | Pencegahan Manipulasi Data Transaksi via Direct API Request | Penyerang mengirim PATCH request ke endpoint SO | Payload memodifikasi nilai `grand_total` | Service memverifikasi integritas state machine, menolak modifikasi langsung, dan mengembalikan HTTP 422/403. | Not Executed |
| **`TC-ERP-SO-013`** | Audit | Pencatatan Lengkap Perubahan Status pada Spatie ActivityLog | SO di-approve oleh Direktur | Periksa catatan tabel `activity_log` | Tercatat: Pelaksana (User ID Direktur), Event (`sales_order_approved`), Nilai transaksi, dan Waktu presisi. | Not Executed |
| **`TC-ERP-SO-014`** | Batas Nilai | Penanganan Sales Order dengan Nilai Transaksi Sangat Besar (> Rp 10 Miliar) | Transaksi proyek tender besar | Grand Total = Rp 15.750.000.000 | Tipe data `DECIMAL(15,2)` menangani angka dengan presisi penuh tanpa overflow angka atau salah kalkulasi pajak. | Not Executed |
| **`TC-ERP-SO-015`** | Konkurensi | Penomoran Dokumen Sales Order Bebas Race Condition | 2 pengguna menyetujui quotation berbeda serentak | Klik konversi SO pada milidetik yang sama | `DocumentNumberService` mengunci transaksi database (`SELECT FOR UPDATE`), menerbitkan nomor unik berurutan `PBB/SOR/2026/092F9C/0001` dan `PBB/SOR/2026/092F9C/0002`. | Not Executed |
| **`TC-ERP-SO-016`** | Transisi Alur | Otomasi Kesiapan Pembuatan Surat Jalan (Delivery Order) | SO berstatus `approved` | Buka modul Logistik / Delivery Order | SO otomatis muncul pada daftar "Pesanan Siap Kirim", siap untuk diterbitkan DO penuh maupun parsial. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-SO-004 (Eskalasi SO Bernilai >= Rp 100 Juta)

1. **Langkah 1:** Konversi penawaran bernilai Rp 120.000.000 menjadi Sales Order.
2. **Langkah 2:** Klik tombol "Simpan & Ajukan Sales Order".
3. **Verifikasi Teknis:**
   - Metode `SalesOrderWorkflowService::handleSubmission()` mengevaluasi batasan nilai:
     ```php
     if ($so->grand_total >= 100_000_000 || $so->margin_percent < 8.0) {
         $so->status = 'pending_director_approval';
     }
     ```
   - Status SO menjadi `pending_director_approval`.
   - Tombol "Buat Surat Jalan (DO)" belum muncul hingga Direktur memberikan approval.

### 3.2 Prosedur Uji TC-ERP-SO-015 (Uji Konkurensi Penomoran SO)

1. **Langkah 1:** Siapkan 2 penawaran siap konversi di database.
2. **Langkah 2:** Jalankan script PHPUnit / Pest concorrency test:
   ```php
   $promises = [
       $service->convertFromQuotation($quo1),
       $service->convertFromQuotation($quo2)
   ];
   ```
3. **Verifikasi Teknis:**
   - Kedua SO berhasil terbit dengan nomor berurutan tanpa terjadi SQL Unique Constraint Violation.
