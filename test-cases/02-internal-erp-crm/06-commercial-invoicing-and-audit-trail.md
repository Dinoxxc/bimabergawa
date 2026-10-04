# Test Cases: Faktur Tagihan Komersial, Verifikasi Pembayaran, & Jejak Audit Sistem
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-06` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 08 Sep 2026 | Principal QA Architect | Disetujui | Skenario pengujian faktur komersial (`INV-YYYYMM-XXXX`), tempo kredit, verifikasi bukti bayar, soft deletes, dan immutable activity log |
| `1.1.0` | 08 Sep 2026 | Principal QA Architect | Disetujui | Penambahan skenario pembayaran parsial `paid_amount` (TC-ERP-INV-011) dan mitigasi benturan slug soft delete (TC-ERP-INV-012) |
| `1.2.0` | 08 Sep 2026 | Principal QA Architect | Disetujui | Penambahan skenario relasi produk discontinue withTrashed (TC-ERP-INV-013), resolusi inkuiri latestOfMany (TC-ERP-INV-014), dan siklus hidup restoring slug (TC-ERP-INV-015) |
| `1.3.0` | 08 Sep 2026 | Principal QA Architect | Disetujui | Penambahan 5 skenario mitigasi celah: isolasi CommissionPayout (TC-ERP-INV-016), perlindungan catatan internal (TC-ERP-INV-017), loop deterministik restoring slug (TC-ERP-INV-018), full-chain withTrashed transaksi induk (TC-ERP-INV-019), dan bypass staf internal AgentTenantScope (TC-ERP-INV-020) |
| `1.4.0` | 03 Okt 2026 | Principal QA Architect | Disetujui | Penambahan 4 skenario uji Executive Reports Hub 4-Tab, AR Aging report, serta verifikasi QR Code & One-Click WhatsApp Dispatch (`TC-ERP-REP-001` s.d. `004`) |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Fitur ERP Komersial, Alur Penagihan & Tempo Kredit)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional Manajemen Faktur & Logika Finansial)
  - `DOC-PBB-SDD-01` (Skema Tabel `invoices`, `invoice_items`, `activity_logs`)
  - `DOC-PBB-SDD-04` (Spesifikasi Resource Filament `InvoiceResource`)
  - `DOC-PBB-UIUX-04` (Wireframe Antarmuka Faktur Tagihan & Modal Verifikasi Pembayaran)

---

## 2. Matriks Skenario Kasus Uji (Test Scenario Matrix)

| ID Kasus Uji | Tipe Pengujian | Judul Skenario Uji | Kondisi Awal (Preconditions) | Langkah Uji (Test Steps) | Hasil yang Diharapkan (Expected Results) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-INV-001`** | Fungsional | Penerbitan Faktur Penagihan dengan Nomor Atomik `INV-YYYYMM-XXXX` | Sales Order berstatus `ready_to_ship` atau `partially_fulfilled` | Finance klik tombol `Terbitkan Invoice Baru` | Sistem membuat record `invoices` dengan format penomoran atomik bebas race condition `INV-YYYYMM-XXXX` dan snapshot nilai DPP serta tarif PPN saat terbit. | Not Executed |
| **`TC-ERP-INV-002`** | Bisnis | Perhitungan Tanggal Jatuh Tempo Berdasarkan Termin Kredit (Net 14/30/60) | Invoice diterbitkan untuk klien dengan termin kredit Net 30 | Input tanggal invoice 08 September 2026 dengan termin Net 30 | Kolom `due_date` terhitung tepat 30 hari ke depan (08 Oktober 2026). Status otomatis berstatus `unpaid`. | Not Executed |
| **`TC-ERP-INV-003`** | Validasi | Deteksi Keterlambatan Bayar dan Peringatan Overdue | Invoice melewati tanggal `due_date` tanpa verifikasi bayar | Cron / Scheduled task harian mengecek invoice | Status pembayaran berubah menjadi `overdue`, badge tabel menampilkan warna kontras merah, dan notifikasi peringatan terkirim ke lonceng Finance. | Not Executed |
| **`TC-ERP-INV-004`** | Fungsional | Verifikasi Pembayaran Transfer Bank & Unggah Berkas Struk | Invoice berstatus `unpaid` atau `overdue` | Finance membuka modal verifikasi, memilih bank, mengunggah bukti bayar, dan klik simpan | Status invoice berubah menjadi `paid`, kolom `paid_at` tercatat waktu real-time, berkas bukti transfer tersimpan privat, dan faktur tidak dapat diedit ulang tanpa otorisasi Super Admin. | Not Executed |
| **`TC-ERP-INV-005`** | Keamanan | Enkripsi dan Proteksi Akses Berkas Bukti Transfer Bank | Berkas struk transfer tersimpan di storage | Akses langsung URL berkas bukti transfer tanpa otentikasi sesi Finance | Web server memblokir akses langsung (`HTTP 403 Forbidden`). Akses hanya dapat dibuka melalui controller privat berotentikasi. | Not Executed |
| **`TC-ERP-INV-006`** | Integritas | Proteksi Penghapusan Lunak (Soft Deletes) pada Faktur Penagihan | Faktur tagihan berstatus `paid` atau `unpaid` | Staf menekan tombol hapus pada tabel invoice | Data tidak terhapus permanen dari basis data; kolom `deleted_at` terisi timestamp. Relasi foreign key tetap utuh dan data dapat dipulihkan (*restore*) oleh Super Admin. | Not Executed |
| **`TC-ERP-INV-007`** | Audit Trail | Pencatatan Otomatis Log Mutasi Sensitif pada `activity_logs` | Admin mengubah tarif PPN pada `system_settings` atau menyetujui margin kritis | Lakukan perubahan setting sistem atau otorisasi diskon | Tabel `activity_logs` mencatat record baru: nama event, user_id pelaku, IP address, timestamp, dan array JSON `properties` memuat nilai lama (*old*) serta nilai baru (*new*). | Not Executed |
| **`TC-ERP-INV-008`** | Validasi | Unggah Berkas PO Resmi Pelanggan pada Sales Order | Sales Order baru dibuat dari penawaran deal | Sales PIC mengunggah berkas scan PO pelanggan (`customer_po_file_path`) | Sistem memvalidasi ekstensi PDF/JPG/PNG/WebP maksimal 5 MB, menyimpan file secara aman, dan mengizinkan Direksi meninjau fisik PO sebelum menyetujui pesanan. | Not Executed |
| **`TC-ERP-INV-009`** | Fungsional | Catatan Evaluasi & Instruksi Direktur pada Penawaran Margin Kritis | Penawaran memiliki gross margin < 8% | Direktur menyetujui atau menolak penawaran dengan mengisi catatan instruksi | Nilai `director_notes` tersimpan pada database dan tertampil pada panel Sales PIC sebagai dasar negosiasi atau penyesuaian diskon. | Not Executed |
| **`TC-ERP-INV-010`** | Multikurensi | Konsistensi Kode Mata Uang Transaksi (`currency`) | Penawaran atau pesanan menggunakan mata uang USD | Sales membuat penawaran dengan memilih currency 'USD' | Kolom `currency` tersimpan 'USD', simbol mata uang pada preview dan cetak PDF menampilkan '$', dan kalkulasi matematis subtotal tetap akurat 2 desimal. | Not Executed |
| **`TC-ERP-INV-011`** | Finansial | Pelacakan Cicilan Bertahap (`paid_amount`) & Accessor Sisa Tagihan | Klien membayar termin uang muka 30% dari total tagihan | Finance memverifikasi bayar Rp 3.000.000 pada invoice Rp 10.000.000 | Nilai `paid_amount` bertambah, status menjadi `partially_paid`, dan accessor `remaining_balance` otomatis bernilai Rp 7.000.000. Saat lunas, sisa tagihan tepat 0.00. | Not Executed |
| **`TC-ERP-INV-012`** | Database | Mitigasi Benturan Indeks Unik saat Soft Delete Produk | Produk lama dihapus sementara dari katalog | Admin menghapus produk dengan slug `schneider-mcb-16a` | `ProductObserver` mengubah slug lama menjadi `schneider-mcb-16a-deleted-{timestamp}`, sehingga slug asli bebas digunakan kembali oleh produk baru tanpa error duplicate key MySQL. | Not Executed |
| **`TC-ERP-INV-013`** | Integritas | Relasi Item Transaksi Historis terhadap Produk Discontinue (`withTrashed`) | Produk telah di-soft-delete karena discontinued oleh prinsipal | Buka invoice, penawaran, atau surat jalan lama (3 tahun lalu) yang memuat SKU tersebut | Relasi `$item->product` tetap berhasil me-load instance produk aslinya tanpa error null object (`belongsTo(Product::class)->withTrashed()`). | Not Executed |
| **`TC-ERP-INV-014`** | Bisnis | Resolusi Inkuiri Multi-Revisi Penawaran via `latestOfMany()` | Inkuiri memiliki 3 revisi penawaran (QUO, QUO-R1, QUO-R2) | Panggil relasi `$inquiry->quotation` pada Kanban CRM | Sistem selalu mengembalikan record penawaran revisi termutakhir (QUO-R2), bukan penawaran draft lama yang telah usang. | Not Executed |
| **`TC-ERP-INV-015`** | Siklus Hidup | Pemulihan Produk (*Product Restoring*) dan Normalisasi Slug Bebas Benturan | Produk di tong sampah dipulihkan (*Restore*) oleh Admin | Klik tombol "Pulihkan Produk" pada panel admin | `ProductObserver::restoring` membersihkan akhiran `-deleted-{timestamp}` dan secara otomatis memeriksa ketersediaan slug; jika bentrok, menambahkan angka urut `-2` tanpa error constraint. | Not Executed |
| **`TC-ERP-INV-016`** | Keamanan | Isolasi Data Pencairan Komisi Mitra Agen (Anti-IDOR) | Agen B mencoba memuat pencairan komisi milik Agen A | Agen B mengakses kueri `CommissionPayout` atau endpoint `/agent/payouts` | `AgentTenantScope` membatasi kueri secara otomatis (`WHERE freelance_sales_id = auth()->agent_id`). Rekord milik agen lain tidak pernah tertampil (0% IDOR). | Not Executed |
| **`TC-ERP-INV-017`** | Privasi | Perlindungan Catatan Internal Staf & Modal HPP dari Akses Mitra Agen | Transaksi inkuiri memiliki catatan internal staf (memuat HPP) dan catatan publik agen | Mitra agen memanggil relasi `$inquiry->comments` atau tabel diskusi di portal agen | Global Scope `agent_comment_visibility` otomatis memfilter kueri `WHERE type = 'agent_visible'`. Catatan HPP internal tersembunyi 100% tanpa celah kebocoran. | Not Executed |
| **`TC-ERP-INV-018`** | Siklus Hidup | Mitigasi Tabrakan Deterministik Slug Restoring via Candidate While Loop | Produk A dihapus, Produk B mengambil slug original, dan Produk C mengambil slug `-2` | Pulihkan (*Restore*) Produk A | `ProductObserver::restoring` menjalankan loop pengecekan kandidat slug berulang hingga menemukan kandidat bebas (`-3`), mencegah benturan duplicate key error MySQL. | Not Executed |
| **`TC-ERP-INV-019`** | Integritas | Akses Rantai Induk Transaksi Historis yang Di-Soft-Delete (Full-Chain withTrashed) | Sales Order atau Quotation induk telah di-soft delete / diarsipkan | Buka faktur (`Invoice`) atau surat jalan (`DeliveryOrder`) lama | Seluruh relasi induk (`$invoice->salesOrder`, `$deliveryOrder->salesOrder`, `$salesOrder->quotation`, `$inquiry->freelanceSales`) tetap me-load objek aslinya tanpa error null reference. | Not Executed |
| **`TC-ERP-INV-020`** | Otoritas | Bypass Eksplisit Otoritas Staf Internal pada `AgentTenantScope` | Staf internal (Super Admin / Finance) memiliki akun dengan `freelance_sales_id` untuk testing | Staf internal login dan membuka menu Inquiries atau Quotations | Sistem mendeteksi role internal dan membypass tenant scope secara total, sehingga staf tetap dapat melihat seluruh data korporat perusahaan tanpa terkunci. | Not Executed |
| **`TC-ERP-REP-001`** | Pelaporan | Tampilan 4-Tab Pusat Laporan Bisnis & Keuangan (`/admin/reports`) | Login sebagai Super Admin atau Finance | Buka menu `/admin/reports` | Halaman memuat 4 tab terstruktur: Performa Penjualan, Analisis Margin HPP, Umur Piutang AR, dan Komisi Agen. Seluruh stat card menampilkan angka agregasi real-time. | Passed |
| **`TC-ERP-REP-002`** | Keamanan | Pembatasan Hak Akses Tab Margin HPP pada Reports Hub (Zero HPP Leak) | Login sebagai Sales PIC atau Staf Gudang | Akses rute `/admin/reports` | Sales PIC hanya dapat melihat Tab 1 (Performa Penjualan); Tab 2 (Analisis Margin HPP) disembunyikan total atau menghasilkan 403 Forbidden. | Passed |
| **`TC-ERP-REP-003`** | Finansial | Akurasi Analisis Umur Piutang Komersial (AR Aging Buckets) | Database memuat invoice jatuh tempo 15 hari, 45 hari, dan 75 hari | Buka Tab 3 (Umur Piutang AR) pada Reports Hub | Faktur terkelompokkan akurat ke bucket: Piutang Lancar, 31-60 Hari, dan >60 Hari. Tombol "Kirim Pengingat WA" menghasilkan draft reminder penagihan resmi. | Passed |
| **`TC-ERP-REP-004`** | Integrasi | Verifikasi QR Code Dokumen Transaksi & One-Click WhatsApp Dispatch | Penawaran/SO/DO resmi diterbitkan | Pindai QR Code di dokumen cetak PDF atau klik tombol "Kirim via WhatsApp" | QR Code mengarah ke `/verify-doc/{type}/{hash}` dan menampilkan segel dokumen resmi PBB; tombol WhatsApp membuka tautan chat dengan pesan ter-prefill. | Passed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-INV-004 (Verifikasi Pembayaran Transfer Bank)

1. **Langkah 1:** Login ke panel internal ERP `https://app.bimabergawa.com` dengan akun Finance.
2. **Langkah 2:** Buka menu **Operasional Utama > Invoices** (`/admin/invoices`).
3. **Langkah 3:** Pilih faktur tagihan berstatus `[Unpaid]` dan klik tombol aksi `[Verifikasi Pembayaran]`.
4. **Langkah 4:** Pada modal popover, pilih `Bank BCA Operasional`, masukkan tanggal transfer hari ini, dan unggah struk transfer `bukti_transfer_bca.pdf`.
5. **Langkah 5:** Klik `[Verifikasi & Tandai Lunas]`.
6. **Verifikasi Teknis:**
   - Status invoice berganti menjadi `[Paid]`.
   - Kolom `paid_at` pada database terisi waktu server saat ini.
   - Tabel `activity_logs` mencatat entri baru dengan deskripsi: *"Invoice INV-YYYYMM-XXXX ditandai lunas oleh Finance"*.

### 3.2 Prosedur Uji TC-ERP-REP-001 (Akses Reports Hub & Verifikasi 4 Tab)

1. **Langkah 1:** Login ke panel internal ERP `https://app.bimabergawa.com` sebagai Super Admin atau Finance.
2. **Langkah 2:** Buka menu **Menu Utama > Pusat Laporan** (`/admin/reports`).
3. **Langkah 3:** Verifikasi keberadaan 4 tab navigasi di bagian atas: `[1. Performa Penjualan]`, `[2. Analisis Margin HPP]`, `[3. Umur Piutang AR]`, dan `[4. Komisi Agen]`.
4. **Langkah 4:** Klik Tab 2 (Analisis Margin HPP) dan periksa kartu metrik laba kotor serta distribusi margin (<8%, 8-15%, >=15%).
5. **Langkah 5:** Klik Tab 3 (Umur Piutang AR) dan verifikasi bahwa faktur yang telah jatuh tempo terdistribusi dengan benar sesuai umur tunggakan.
6. **Langkah 6:** Klik tombol **"Ekspor Excel"** untuk memastikan berkas spreadsheet dapat diunduh tanpa error memori PHP.
