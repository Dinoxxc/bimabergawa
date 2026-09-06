# Test Cases: Kepatuhan UU PDP No. 27/2022, Audit Log, & Retensi Pajak
## Domain 05: Keamanan & Pengujian Non-Fungsional (Security & NFR)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-SEC-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Security/QA Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Compliance QA Tester | Draf Awal | Pengujian kepatuhan privasi data & audit trail dasar |
| `1.1.0` | 03 Sep 2026 | Legal & Regulatory Auditor | Ditinjau | Penyelarasan pasal UU PDP No. 27/2022 & retensi transaksi UU KUP |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penambahan uji masking log sensitif & otomasi pembersihan log 90 hari |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-SEC-04` (Spesifikasi Kepatuhan Privasi Data PDP, Regulasi Pajak, & Jejak Audit)
  - `DOC-PBB-BRD-04` (Analisis Dampak Regulasi UU HPP PPN & UU PDP No. 27/2022)
  - `DOC-PBB-SDD-02` (Skema Basis Data MySQL 8.0 & Field Encryption Specs)
  - `DOC-PBB-SRS-05` (Kebutuhan Non-Fungsional NFR-SEC-04 & NFR-REL-03)
- **Modul Sistem:** Modul Kepatuhan Regulasi Privasi Data & Jejak Audit Forensik
- **Target Kelas / Model / Service:**
  - `Spatie\Activitylog\Models\Activity`
  - `App\Models\AgentProfile`
  - `App\Models\Quotation`
  - `App\Models\SalesOrder`
  - `App\Console\Kernel` (Scheduler)
- **Target Method:** `logActivity()`, `cleanOldLogs()`, `maskSensitiveData()`, `enforceTaxRetention()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-SEC-PDP-001`** | UU PDP | Enkripsi Data Pribadi Sensitif Agen (*At-Rest Encryption*) | Profil agen disimpan | Cek kolom `nik_ktp`, `npwp_number`, `bank_account_number` di database MySQL | Seluruh data identitas tersimpan dalam format terenkripsi AES-256-CBC, tidak dapat dibaca jika database diakses langsung (*breach mitigation*). | Not Executed |
| **`TC-SEC-PDP-002`** | UU PDP | Penyamaran Data Sensitif pada Log Aplikasi (*Log Masking*) | Pengguna login / verifikasi OTP | Buka berkas `storage/logs/laravel.log` | Kode OTP, kata sandi, dan nomor rekening perbankan disamarkan / tidak dicatat pada file log teks (*no plaintext credentials in logs*). | Not Executed |
| **`TC-SEC-PDP-003`** | UU PDP | Pemenuhan Hak Subjek Data: Pembetulan Data (*Right to Rectification*) | Agen meminta pembaruan data profil | Agen mengubah nama/kontak via portal agen | Sistem memperbarui data terenkripsi, mencatat riwayat pembaruan di `activity_log`, dan mengirimkan notifikasi konfirmasi perubahan. | Not Executed |
| **`TC-SEC-PDP-004`** | Audit Trail | Pencatatan Otomatis Perubahan Status Komersial Inkuiri | Inkuiri diubah dari `lead` ke `quoted` | Sales PIC update status inkuiri | Tabel `activity_log` mencatat: `causer_id`, `subject_type = Inquiry`, perubahan atribut lama ke baru, dan timestamp presisi. | Not Executed |
| **`TC-SEC-PDP-005`** | Audit Trail | Pencatatan Jejak Persetujuan Sales Order oleh Direktur | Direktur meng-approve SO senilai Rp 150 Juta | Klik "Setujui Sales Order" | `activity_log` mencatat rekaman bernilai hukum: ID Direktur pelaksana, waktu approval, nilai transaksi, dan catatan persetujuan. | Not Executed |
| **`TC-SEC-PDP-006`** | Audit Trail | Pencatatan Pencairan Komisi Agen oleh Finance | Finance menandai komisi `paid` | Upload bukti transfer & konfirmasi bayar | `activity_log` mencatat: User Finance, Nomor Referensi Bank, Nominal komisi cair, dan path bukti transfer. | Not Executed |
| **`TC-SEC-PDP-007`** | Audit Trail | Pencatatan Perubahan Hak Akses / Role Spatie RBAC | Super Admin memberikan role `sales-pic` ke staf | Simpan role user | `activity_log` mencatat perubahan hak otorisasi: User target, Role yang ditambahkan, dan Admin pemberi wewenang. | Not Executed |
| **`TC-SEC-PDP-008`** | Retensi Log | Eksekusi Terjadwal Pembersihan Log Usia > 90 Hari | Tabel `activity_log` memuat log berusia 95 hari | Jalankan perintah `php artisan activitylog:clean --days=90` | Log berusia > 90 hari terhapus secara otomatis, log 90 hari terakhir tetap terjaga utuh, ukuran tabel MySQL tetap ramping. | Not Executed |
| **`TC-SEC-PDP-009`** | Retensi Pajak | Kekekalan Data Transaksi Finansial Sesuai UU KUP (10 Tahun) | Transaksi PO/Faktur telah terbit | Coba lakukan hard delete pada transaksi berstatus `paid` | Sistem melarang penghapusan data fisik transaksi (*No Hard Deletion on Invoices*), data wajib dipertahankan untuk kebutuhan audit pajak 10 tahun. | Not Executed |
| **`TC-SEC-PDP-010`** | Retensi Pajak | Keterikatan Tarif Snapshot PPN Statis (`vat_rate_applied`) | Transaksi diterbitkan saat PPN 11% | Ubah konfigurasi PPN nasional di sistem menjadi 12% | Nilai transaksi historis tetap menggunakan `vat_rate_applied = 11.00`, Grand Total historis tidak berubah sama sekali. | Not Executed |
| **`TC-SEC-PDP-011`** | Hak Subjek | Hak Penolakan Pemrosesan Data Prospek (*Opt-Out / Soft-Delete*) | Klien B2B meminta penghapusan kontak | Admin mengeksekusi *Soft Delete* pada record kontak | Kontak disembunyikan dari daftar aktif (`deleted_at != null`), data transaksi historis lampau tetap dipertahankan untuk arsip akuntansi. | Not Executed |
| **`TC-SEC-PDP-012`** | Keamanan | Perlindungan Integritas Tabel Audit Log dari Modifikasi | Pengguna mencoba mengedit / menghapus baris di `activity_log` | Akses controller / model `Activity` | Model `Activity` berstatus *Append-Only* (tidak menyediakan fitur edit / delete manual melalui panel UI). | Not Executed |
| **`TC-SEC-PDP-013`** | Insiden PDP | Protokol Pencabutan Sesi Darurat saat Terjadi Pelanggaran Keamanan | Terjadi indikasi kebocoran kredensial | Eksekusi perintah darurat: `php artisan pbb:revoke-all-sessions` | Seluruh sesi aktif di tabel `sessions` dikosongkan seketika, seluruh pengguna dipaksa login ulang via Passwordless OTP baru. | Not Executed |
| **`TC-SEC-PDP-014`** | Audit | Pelaporan Log Forensik Akses Berkas Identitas KTP | Admin membuka berkas KTP agen | Klik aksi "Lihat KTP" | Sistem mencatat 1 baris log akses: "Admin #1 mengakses berkas KTP Agen #5" untuk mencegah penyalahgunaan wewenang internal. | Not Executed |
| **`TC-SEC-PDP-015`** | Batas Nilai | Penanganan Volume Log Harian Tinggi (> 10.000 Entri / Hari) | Aktivitas transaksi tinggi di panel ERP | Generate 10.000 log aktivitas | Indeks database MySQL pada kolom `log_name`, `created_at`, dan `causer_id` memastikan query filter log tetap responsif (< 100ms). | Not Executed |
| **`TC-SEC-PDP-016`** | Notifikasi UU | Alur Notifikasi Insiden Kebocoran Data Sesuai UU PDP (3 x 24 Jam) | Simulasi insiden keamanan data | Pemicuan workflow *Data Breach Notification* | Sistem memuat templat resmi pemberitahuan tertulis kepada pihak terdampak dan otoritas pengawas pelindungan data pribadi. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-SEC-PDP-008 (Eksekusi Pembersihan Log 90 Hari)

1. **Langkah 1:** Masukkan 1 baris log simulasi bertanggal 100 hari yang lalu ke tabel `activity_log`:
   ```sql
   INSERT INTO activity_log (log_name, description, created_at) VALUES ('default', 'Log lama uji', NOW() - INTERVAL 100 DAY);
   ```
2. **Langkah 2:** Jalankan perintah artisan pembersih log via CLI:
   ```bash
   php artisan activitylog:clean --days=90
   ```
3. **Verifikasi Teknis:**
   - Perintah mengembalikan: "Deleted 1 activity records older than 90 days".
   - Baris log bertanggal 100 hari yang lalu terhapus permanen dari MySQL.
   - Seluruh log yang berusia <= 90 hari tetap utuh.

### 3.2 Prosedur Uji TC-SEC-PDP-010 (Verifikasi Snapshot PPN Statis)

1. **Langkah 1:** Buat Quotation baru dengan Subtotal Rp 10.000.000 saat tarif PPN sistem diset `11.00%`.
2. **Langkah 2:** Grand Total tersimpan: `Rp 11.100.000` (PPN = Rp 1.100.000, `vat_rate_applied = 11.00`).
3. **Langkah 3:** Ubah parameter tarif PPN sistem pada `config/pbb.php` menjadi `12.00%`.
4. **Langkah 4:** Buka kembali Quotation lama dan unduh PDF resminya.
5. **Verifikasi Teknis:**
   - PDF Quotation lama tetap menampilkan PPN 11% (Rp 1.100.000) dan Grand Total Rp 11.100.000.
   - Angka transaksi historis tidak terpengaruh oleh perubahan tarif masa depan.
