# Test Cases: Surat Jalan (Delivery Order), Partial Delivery, & Logistik
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-05` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Logistics QA Tester | Draf Awal | Pengujian penerbitan surat jalan standar |
| `1.1.0` | 03 Sep 2026 | Supply Chain Tester | Ditinjau | Penambahan uji pengiriman parsial (Partial DO) & tracking sisa |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan upload bukti tanda terima (POD) & status fulfillment |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi Surat Jalan, Partial Delivery, & Pemenuhan Pesanan)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-SO-05 s.d. FR-SO-08)
  - `DOC-PBB-SDD-03` (Service Layer Delivery Order & Tracking Sisa Kuantitas)
  - `DOC-PBB-SDD-04` (Filament Resource Delivery Order & Form Pengiriman)
- **Modul Sistem:** Modul Logistik, Pengiriman, & Surat Jalan (Delivery Order)
- **Target Kelas / Controller / Service:**
  - `App\Filament\Resources\DeliveryOrderResource`
  - `App\Services\DeliveryOrderFulfillmentService`
  - `App\Services\DocumentNumberService`
  - `App\Models\DeliveryOrder`, `App\Models\DeliveryOrderItem`, `App\Models\SalesOrder`
- **Target Method:** `createFullDeliveryOrder()`, `createPartialDeliveryOrder()`, `updateRemainingQty()`, `uploadProofOfDelivery()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-DO-001`** | Positif | Penerbitan Surat Jalan Pengiriman Penuh (*Full Delivery Order*) | Sales Order berstatus `approved` | Buat DO -> Pilih semua item dengan kuantitas penuh | Sistem membuat record `delivery_orders` bernomor `DO-202609-0001`, seluruh item terkirim 100%, status SO menjadi `fulfilled`. | Not Executed |
| **`TC-ERP-DO-002`** | Positif | Penerbitan Surat Jalan Parsial Tahap 1 (*Partial DO Batch 1*) | Pesanan SO memuat 100 pcs SKU `SCH-MCB-16A` | Buat Partial DO dengan Qty Kirim = 40 pcs | `DO-202609-0001` terbit dengan 40 pcs. Field `remaining_qty` pada SO berkurang menjadi 60 pcs, status SO menjadi `partially_delivered`. | Not Executed |
| **`TC-ERP-DO-003`** | Positif | Penerbitan Surat Jalan Parsial Tahap 2 & Penutupan SO | Sisa pesanan pada SO = 60 pcs | Buat Partial DO ke-2 dengan Qty Kirim = 60 pcs | `DO-202609-0002` terbit dengan 60 pcs. Sisa pesanan menjadi 0 pcs, status SO otomatis terkunci menjadi `fulfilled` (selesai). | Not Executed |
| **`TC-ERP-DO-004`** | Positif | Pengisian Informasi Ekspedisi & Armada Pengiriman | Form pembuatan Surat Jalan aktif | Input: Nama Kurir/Driver, No. Polisi Kendaraan, No. Resi | Data ekspedisi tersimpan lengkap pada tabel `delivery_orders`, tercantum pada cetakan berkas Surat Jalan. | Not Executed |
| **`TC-ERP-DO-005`** | Positif | Pembuatan Berkas PDF Surat Jalan Resmi (Tanpa Informasi Harga) | DO berstatus `approved` | Klik tombol "Cetak Surat Jalan (DO)" | Sistem menghasilkan PDF Surat Jalan: Kop resmi PBB Rasuna Said, alamat pengiriman, nomor PO klien, daftar SKU & Qty fisik, blok tanda tangan penerima/driver, 0 informasi harga rupiah. | Not Executed |
| **`TC-ERP-DO-006`** | Positif | Pembaruan Status Pengiriman (*In-Transit -> Delivered*) | DO sedang dalam perjalanan | Staf Logistik mengubah status menjadi `delivered` | Status DO berubah menjadi `delivered`, field `delivered_at` terisi waktu saat ini, notifikasi pengiriman selesai dikirim ke PIC Sales. | Not Executed |
| **`TC-ERP-DO-007`** | Positif | Pengunggahan Bukti Tanda Terima (*Proof of Delivery / POD*) | Barang telah sampai di lokasi klien | Unggah foto Surat Jalan bertanda tangan & stempel basah klien | Berkas tersimpan di storage terenkripsi, status DO tervalidasi penuh (*verified delivery*). | Not Executed |
| **`TC-ERP-DO-008`** | Negatif | Validasi Penolakan Kuantitas Kirim Melebihi Sisa Pesanan | Sisa kuantitas pesanan pada SO = 20 pcs | Input Qty Kirim pada form DO = 25 pcs | Sistem menolak dengan error validasi: "Kuantitas pengiriman (25) tidak boleh melebihi sisa pesanan yang belum terkirim (20)". | Not Executed |
| **`TC-ERP-DO-009`** | Negatif | Penerbitan Surat Jalan untuk Sales Order yang Belum Disetujui | SO berstatus `pending_director_approval` atau `draft` | Coba akses endpoint pembuatan DO | Sistem menolak dengan pesan: "Surat Jalan hanya dapat diterbitkan untuk Sales Order yang telah disetujui resmi". | Not Executed |
| **`TC-ERP-DO-010`** | Negatif | Penerbitan Surat Jalan untuk Sales Order yang Sudah Fulfilled | SO berstatus `fulfilled` (Sisa Qty = 0) | Coba klik tombol "Buat DO Baru" | Tombol dinonaktifkan / sistem memunculkan notifikasi: "Seluruh item pesanan pada Sales Order ini telah terkirim lengkap". | Not Executed |
| **`TC-ERP-DO-011`** | Keamanan | Isolasi Data Finansial dari Staf Logistik & Gudang | Login sebagai staf peran Logistik | Buka detail DO dan daftar pesanan | Staf logistik hanya melihat data fisik barang (SKU, nama produk, jumlah unit, alamat kirim). Harga satuan, diskon, margin HPP, dan total nominal disembunyikan. | Not Executed |
| **`TC-ERP-DO-012`** | Keamanan | Pencegahan Manipulasi Status Pengiriman oleh Pihak Luar | Request modifikasi status pengiriman | Pengguna tanpa autentikasi/role logistik mengirim request | Sistem menolak aksi dengan status HTTP 401 Unauthorized / HTTP 403 Forbidden. | Not Executed |
| **`TC-ERP-DO-013`** | Audit | Pencatatan Lengkap Penerbitan dan Serah Terima pada Log | DO diterbitkan dan bukti POD diunggah | Periksa tabel `activity_log` | Tercatat: Pembuat Surat Jalan, Nomor Kendaraan, Waktu Serah Terima, dan File Bukti POD. | Not Executed |
| **`TC-ERP-DO-014`** | Batas Nilai | Penanganan Surat Jalan dengan Lebih dari 50 Baris SKU | Transaksi proyek suplai gedung bertingkat | Buat DO dengan 60 jenis produk | DomPDF menghasilkan dokumen Surat Jalan multi-halaman dengan penomoran "Halaman 1 dari 2", tabel terpotong rapi tanpa tumpang tindih. | Not Executed |
| **`TC-ERP-DO-015`** | Konkurensi | Penomoran Dokumen Surat Jalan Bebas Tabrakan (*Unique DO Numbering*) | 2 staf gudang menerbitkan Surat Jalan secara serentak | Klik tombol simpan DO di detik yang sama | `DocumentNumberService` menggunakan transaksi atomik, menghasilkan nomor berurutan `DO-202609-0001` dan `DO-202609-0002` tanpa duplikasi. | Not Executed |
| **`TC-ERP-DO-016`** | Retur / Anomali | Penanganan Pengembalian Barang (*Returned / Delivery Failed*) | Pengiriman gagal karena lokasi proyek tutup | Staf mengubah status DO menjadi `delivery_failed` | Sisa kuantitas pesanan pada SO otomatis dikembalikan (*restored*) ke status siap kirim ulang, alasan kegagalan wajib dicatat. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-DO-002 (Penerbitan Partial Delivery Order)

1. **Langkah 1:** Buka Sales Order disetujui bernomor `SO-202609-0001` (Kuantitas Item: 100 pcs SKU `SCH-MCB-16A`).
2. **Langkah 2:** Klik aksi **"Buat Surat Jalan Parsial"**.
3. **Langkah 3:** Pada tabel repeater item, ubah kolom `qty_to_ship` dari 100 menjadi `40`.
4. **Langkah 4:** Masukkan data driver: "Budi Santoso - B 9123 PBB".
5. **Langkah 5:** Klik **"Terbitkan Surat Jalan"**.
6. **Verifikasi Teknis:**
   - Surat Jalan `DO-202609-0001` terbit dengan 40 pcs.
   - Kolom `sales_order_items.remaining_qty` terupdate menjadi `60`.
   - Status Sales Order berubah menjadi `partially_delivered`.

### 3.2 Prosedur Uji TC-ERP-DO-008 (Validasi Batas Kuantitas Pengiriman)

1. **Langkah 1:** Pada SO dengan sisa 20 pcs, coba buat DO dengan kuantitas 25 pcs.
2. **Langkah 2:** Klik submit form.
3. **Verifikasi Teknis:**
   - Metode `DeliveryOrderFulfillmentService::validateShippedQuantities()` melempar `ValidationException`.
   - UI menampilkan pesan error: "Kuantitas pengiriman tidak boleh melebihi sisa pesanan".
   - Tidak ada baris baru pada tabel `delivery_orders`.
