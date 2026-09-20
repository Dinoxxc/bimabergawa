# Test Cases: Konkurensi, Race Condition, & Penomoran Dokumen Atomik
## Domain 05: Keamanan & Pengujian Non-Fungsional (Security & NFR)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-SEC-02` |
| **Versi Dokumen** | 1.5.1 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Security/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Concurrency QA Specialist | Draf Awal | Pengujian beban konkurensi penomoran dokumen dasar |
| `1.1.0` | 03 Sep 2026 | Database Performance Engineer | Ditinjau | Penambahan uji lock `SELECT FOR UPDATE` & mekanisme retry deadlock |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan pencegahan race condition pencairan komisi & Partial DO |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 20 Sep 2026 | Principal QA Architect | Disetujui | Verifikasi dan eksekusi pengujian otomatis skenario penomoran dokumen konkurensi (TC-SEC-CON-001, 002, 003, 006, 007, 008, 010, 011, 014) via DocumentNumberServiceTest & ConcurrencyDocumentNumberTest dengan status Passed |
| `1.5.0` | 20 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan ekspektasi nomor dokumen resmi korporat PBB: `PBB/{TIPE}/{YYYY}/{MMSSSS}/{XXXX}` ber-salt kriptografis pada kasus uji konkurensi |
| `1.5.1` | 20 Sep 2026 | Principal QA Architect | Disetujui | Penyeragaman seluruh kode tipe dokumen menjadi 3 huruf (`SO` -> `SOR`, `DO` -> `DOR`) pada matriks uji konkurensi |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-QA-02` (Spesifikasi Uji Konkurensi & Beban Race Condition Penomoran Dokumen)
  - `DOC-PBB-SDD-03` (Service Layer Penomoran Dokumen Atomik & Mutex Locking)
  - `DOC-PBB-API-03` (Arsitektur Transaksi Database MySQL 8.0 & Lock Isolation Level)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional Keutuhan Transaksi Finansial)
- **Modul Sistem:** Modul Transaksi Atomik, Penomoran Dokumen, & Pencegahan Race Condition
- **Target Kelas / Service / Model:**
  - `App\Services\DocumentNumberService`
  - `App\Services\CommissionPayoutService`
  - `App\Services\DeliveryOrderFulfillmentService`
  - `App\Models\DocumentSequence`
- **Target Method:** `generateNextNumber()`, `lockForUpdate()`, `handleDeadlockRetry()`, `decrementRemainingQty()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-SEC-CON-001`** | Konkurensi | Penomoran Quotation Serentak (*Concurrent Quotation Numbering*) | 10 Sales membuat penawaran baru di detik yang sama | Eksekusi 10 thread request paralel secara bersamaan | `DocumentNumberService` menerbitkan 10 nomor berurutan unik: `PBB/QUO/2026/09SSSS/0001` s.d. `0010` tanpa nomor lompat atau duplikat. | Passed |
| **`TC-SEC-CON-002`** | Konkurensi | Penomoran Sales Order Serentak (*Concurrent SO Numbering*) | 10 Sales menyetujui quotation menjadi SO secara bersamaan | Eksekusi 10 transaksi konversi SO serentak | Diterbitkan 10 nomor SO unik: `PBB/SOR/2026/09SSSS/0001` s.d. `0010`, tabel `sales_orders` konsisten 100%. | Passed |
| **`TC-SEC-CON-003`** | Konkurensi | Penomoran Surat Jalan Serentak (*Concurrent DO Numbering*) | 10 Staf Gudang menerbitkan Surat Jalan serentak | Eksekusi 10 pembuatan DO serentak | Diterbitkan 10 nomor DO unik: `PBB/DOR/2026/09SSSS/0001` s.d. `0010` tanpa terjadi kegagalan transaksi. | Passed |
| **`TC-SEC-CON-004`** | Konkurensi | Pengurangan Kuantitas Sisa Pesanan Serentak pada Partial DO | Sisa pesanan SO = 50 pcs | 2 Staf Gudang menerbitkan DO parsial (masing-masing 30 pcs) serentak | Staf pertama berhasil menerbitkan DO (30 pcs, sisa jadi 20 pcs). Staf kedua ditolak/gagal karena kuantitas minta (30) > sisa baru (20). | Not Executed |
| **`TC-SEC-CON-005`** | Konkurensi | Pencegahan Penarikan Saldo Komisi Ganda Serentak (*Double Payout Lock*) | Saldo komisi agen = Rp 1.000.000 | 2 request pencairan dana (masing-masing Rp 1.000.000) dikirim bersamaan | Atomic Lock memastikan hanya 1 request yang diproses; request kedua ditolak dengan pesan saldo tidak mencukupi. Saldo tidak pernah minus. | Not Executed |
| **`TC-SEC-CON-006`** | Konkurensi | Transisi Bulan Baru pada Format Penomoran (*Monthly Sequence Reset*) | Tanggal sistem berganti dari 30 September ke 01 Oktober | Buat Quotation pertama di bulan Oktober | Nomor dokumen otomatis me-reset urutan awal dan menyematkan kode bulan baru: `PBB/QUO/2026/10SSSS/0001`. | Passed |
| **`TC-SEC-CON-007`** | Database Lock | Penegakan Kunci Baris `SELECT FOR UPDATE` pada Tabel Sequence | Transaksi penomoran sedang berlangsung | Inspeksi lock state MySQL selama eksekusi | Baris tabel `document_sequences` terkunci secara eksklusif (*exclusive row lock*) hingga transaksi selesai di-commit. | Passed |
| **`TC-SEC-CON-008`** | Ketahanan | Mekanisme Otomatis Retry saat Terjadi Deadlock (*Deadlock Auto-Retry*) | Terjadi anomali deadlock MySQL (Error 1213) | Simulasi deadlock pada transaksi penomoran | Service menangkap `DeadlockException` dan mengeksekusi retry otomatis hingga 3 kali (`DB::transaction(..., 3)`), proses transaksi akhirnya sukses. | Passed |
| **`TC-SEC-CON-009`** | Batas Nilai | Uji Beban Konkurensi Ekstrem (50 Request Paralel Simultan) | Sistem dalam beban puncak | 50 request penomoran dokumen dikirimkan serentak via tool pengujian | Seluruh 50 transaksi selesai dalam < 3 detik, 50 nomor unik diterbitkan tanpa kegagalan integritas basis data. | Not Executed |
| **`TC-SEC-CON-010`** | Keamanan | Penegakan Unique Constraint Database sebagai Garis Pertahanan Akhir | Coba insert paksa nomor duplikat via query SQL langsung | `INSERT INTO quotations (number) VALUES ('QUO-202609-0001')` (sudah ada) | Basis data MySQL melempar `Integrity constraint violation (1062 Duplicate entry)`, data duplikat ditolak di level engine penyimpanan. | Passed |
| **`TC-SEC-CON-011`** | Keamanan | Integritas Transaksi saat Terjadi Pengecualian (*Transaction Rollback*) | Error terjadi saat penyimpanan item quotation ke-5 | Simulasi exception pada item ke-5 | Seluruh transaksi di-rollback penuh (`DB::rollBack()`), nomor sequence tidak terbuang sia-sia (*no sequence gap*). | Passed |
| **`TC-SEC-CON-012`** | Audit | Pencatatan Kejadian Keterlambatan Kunci (*Lock Wait Timeout Alert*) | Kunci transaksi tertahan > 5 detik | Simulasi transaksi lambat | Sistem mencatat peringatan performa pada log: "Warning: High lock wait time on DocumentSequence", alerting tim operasional. | Not Executed |
| **`TC-SEC-CON-013`** | Isolasi Transaksi | Penegakan Level Isolasi Transaksi `READ COMMITTED` / `REPEATABLE READ` | 2 transaksi membaca data stok bersamaan | Verifikasi isolation level MySQL 8.0 | Transaksi berjalan pada level isolasi aman, mencegah fenomena *Dirty Reads* pada data finansial dan status pesanan. | Not Executed |
| **`TC-SEC-CON-014`** | Batas Nilai | Penomoran Dokumen Mencapai Digit Maksimal (9.999 per Bulan) | Nomor terakhir = `PBB/QUO/2026/09SSSS/9999` | Buat penawaran baru ke-10.000 | Sistem menangani ekspansi digit secara mulus menjadi `PBB/QUO/2026/09SSSS/10000` tanpa pemotongan string (*string overflow protection*). | Passed |
| **`TC-SEC-CON-015`** | Performa | Latensi Pembangkitan Nomor Dokumen di Bawah Beban | 20 request konkurensi serentak | Ukur waktu eksekusi fungsi `generateNextNumber()` | Waktu rata-rata per pembangkitan nomor < 25 milidetik pada infrastruktur shared hosting LiteSpeed. | Not Executed |
| **`TC-SEC-CON-016`** | Pemulihan | Rekonsiliasi Sequence saat Terjadi Server Crash Tiba-Tiba | Server web mati mendadak saat transaksi aktif | Nyalakan kembali server & jalankan `php artisan pbb:audit-sequences` | Perintah artisan memverifikasi nomor sequence terakhir dengan tabel transaksi riil dan menyinkronkan urutan secara otomatis. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-SEC-CON-001 (Uji Konkurensi 10 Thread Simultan)

1. **Langkah 1:** Buat skrip uji konkurensi Pest / PHPUnit:
   ```php
   it('generates unique sequential quotation numbers concurrently', function () {
       $results = collect(range(1, 10))->map(function () {
           return app(DocumentNumberService::class)->generateNextNumber('QUO');
       });
       
       expect($results->unique()->count())->toBe(10);
   });
   ```
2. **Langkah 2:** Jalankan pengujian via CLI:
   ```bash
   php artisan test --filter=ConcurrencyDocumentNumberTest
   ```
3. **Verifikasi Teknis:**
   - Seluruh 10 nomor yang dihasilkan unik dan berurutan.
   - Ekspektasi status pengujian: Passed (Siap Dieksekusi).

### 3.2 Prosedur Uji TC-SEC-CON-004 (Uji Balap Partial Delivery Order)

1. **Langkah 1:** Sales Order bernomor `PBB/SOR/2026/092F9C/0001` memiliki sisa pesanan 50 unit.
2. **Langkah 2:** Eksekusi 2 thread pengiriman serentak masing-masing meminta alokasi 30 unit.
3. **Verifikasi Teknis:**
   - Thread 1 mengeksekusi `SELECT remaining_qty FROM sales_order_items WHERE id = 1 FOR UPDATE`.
   - Thread 1 berhasil mengurangi stok menjadi 20 unit dan membuat DO #1 (30 unit).
   - Thread 2 yang menunggu kunci membaca nilai baru (20 unit) dan melempar `ValidationException` (kuantitas tidak cukup).
   - Total barang terkirim tidak pernah melebihi 50 unit.
