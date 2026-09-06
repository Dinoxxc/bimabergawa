# Test Cases: Kalkulasi Komisi Otomatis, Ledger Mutasi, & Pencairan Dana
## Domain 03: Portal Agen Freelance (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-AGN-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Financial QA Tester | Draf Awal | Pengujian kalkulasi komisi persentase dasar |
| `1.1.0` | 03 Sep 2026 | Accounting Specialist | Ditinjau | Penambahan uji ledger mutasi saldo & verifikasi bukti transfer |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan alur approval pencairan oleh Finance & Direksi |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi Alur Komisi Agen, Ledger Transaksi, & Pencairan)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-AGENT-04 & FR-ERP-08)
  - `DOC-PBB-SDD-03` (Service Layer Kalkulasi Komisi & State Machine Ledger)
  - `DOC-PBB-SDD-04` (Filament Resource Commission Management & Agent Ledger)
- **Modul Sistem:** Modul Komisi Agen & Rekonsiliasi Finansial
- **Target Kelas / Model / Service:**
  - `App\Services\CommissionPayoutService`
  - `App\Models\CommissionLedger`
  - `App\Filament\Resources\CommissionLedgerResource`
  - `App\Filament\Agent\Pages\MyCommissionLedger`
- **Target Method:** `calculateCommission()`, `requestPayout()`, `approvePayout()`, `markAsPaid()`, `getAgentBalance()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-AGN-COM-001`** | Positif | Kalkulasi Otomatis Hak Komisi saat Terbit Sales Order | SO terbit senilai Rp 100.000.000 (Komisi Agen = 2.5%) | Sales Order disetujui resmi | Sistem membuat baris baru di `commission_ledgers`: Nominal Komisi = Rp 2.500.000, status `pending_invoice_payment`. | Not Executed |
| **`TC-AGN-COM-002`** | Positif | Perubahan Status Komisi Menjadi Siap Cair (*Payable*) | Klien melunasi faktur komersial | Departemen Finance menandai invoice status `paid` | Status komisi agen berubah dari `pending_invoice_payment` menjadi `payable` (Siap Dicairkan), saldo komisi bertambah. | Not Executed |
| **`TC-AGN-COM-003`** | Positif | Pengajuan Pencairan Komisi Mandiri oleh Agen (*Payout Request*) | Saldo komisi agen `payable` = Rp 5.000.000 | Agen klik "Ajukan Pencairan" -> Nominal Rp 5.000.000 | Status komisi berubah menjadi `payout_requested`, saldo tertahan sementara, notifikasi diajukan ke Departemen Finance. | Not Executed |
| **`TC-AGN-COM-004`** | Positif | Persetujuan & Verifikasi Pencairan oleh Departemen Finance | Pengajuan komisi berstatus `payout_requested` | Staf Finance meninjau rekening -> Klik "Verifikasi & Setujui" | Status berubah menjadi `payout_approved`, berkas siap untuk dieksekusi transfer perbankan. | Not Executed |
| **`TC-AGN-COM-005`** | Positif | Eksekusi Pencairan & Unggah Bukti Transfer Bank | Komisi berstatus `payout_approved` | Finance upload struk bukti transfer PDF/JPG & klik "Tandai Telah Ditransfer" | Status berubah menjadi `paid`, nomor referensi bank tercatat, agen menerima email konfirmasi bukti pembayaran komisi. | Not Executed |
| **`TC-AGN-COM-006`** | Positif | Tampilan Riwayat Ledger Mutasi Komisi pada Portal Agen | Agen membuka portal komisi | Akses menu "Komisi & Ledger Saya" | Tampil ringkasan kartu: Total Akumulasi Komisi, Komisi Telah Cair, Saldo Siap Cair, dan tabel mutasi riwayat per transaksi. | Not Executed |
| **`TC-AGN-COM-007`** | Positif | Unduh Bukti Tanda Terima Komisi Resmi (*Voucher PDF*) | Komisi berstatus `paid` | Agen / Finance klik "Unduh Bukti Potong / Voucher" | Sistem menghasilkan berkas PDF resmi: Kop PBB Rasuna Said, rincian inkuiri klien, nominal komisi, PPh 21 (jika ada), dan tanda tangan Finance. | Not Executed |
| **`TC-AGN-COM-008`** | Negatif | Penolakan Pengajuan Pencairan di Bawah Batas Minimum (< Rp 500.000) | Saldo agen = Rp 250.000 | Agen mencoba mengajukan pencairan | Sistem menolak pengajuan dengan pesan: "Batas minimum pencairan komisi adalah Rp 500.000". | Not Executed |
| **`TC-AGN-COM-009`** | Negatif | Penolakan Pengajuan Komisi untuk Invoice yang Belum Lunas | Inkuiri sukses namun invoice berstatus `unpaid` | Agen mencoba mencairkan komisi `pending_invoice_payment` | Tombol pencairan tidak aktif; sistem menegaskan komisi baru dapat dicairkan setelah klien melunasi tagihan ke PBB. | Not Executed |
| **`TC-AGN-COM-010`** | Negatif | Penolakan Pengajuan Melebihi Saldo Aktif (*Overdraft Attempt*) | Saldo aktif agen = Rp 2.000.000 | Input nominal pengajuan = Rp 3.000.000 | Validasi form memunculkan error: "Nominal pengajuan pencairan tidak boleh melebihi saldo yang tersedia". | Not Executed |
| **`TC-AGN-COM-011`** | Keamanan | Pencegahan Pencairan Ganda Serentak (*Double Payout Prevention*) | Agen mengklik tombol submit pengajuan pencairan 2x cepat | Double click cepat pada form ajukan pencairan | `CommissionPayoutService` menggunakan *Atomic Lock* database, pengajuan kedua ditolak dan hanya 1 tiket pencairan yang terbuat. | Not Executed |
| **`TC-AGN-COM-012`** | Keamanan | Isolasi Rekening Bank Tujuan Transfer Komisi | Data rekening diambil saat pencairan | Request payout dijalankan | Sistem selalu mengambil rekening dari data profil terenkripsi (`AgentProfile`), menolak pengubahan nomor rekening dadakan via parameter request. | Not Executed |
| **`TC-AGN-COM-013`** | Audit | Pencatatan Lengkap Seluruh Mutasi Komisi pada Audit Log | Komisi dibayar oleh Finance | Periksa tabel `activity_log` | Tercatat: `causer_id` (Finance User ID), `subject_id` (Commission Ledger ID), Nominal, Nomor Referensi Bank, dan Bukti Transfer. | Not Executed |
| **`TC-AGN-COM-014`** | Batas Nilai | Presisi Kalkulasi Komisi Pecahan Desimal | Nilai transaksi = Rp 12.345.678 (Komisi = 2.75%) | Kalkulasi otomatis | Hasil = Rp 339.506,15. Tipe data `DECIMAL(12,2)` mencegah kehilangan pecahan nilai rupiah. | Not Executed |
| **`TC-AGN-COM-015`** | Batal Transaksi | Penyesuaian Ledger Komisi saat Sales Order Dibatalkan (*Reversal*) | SO yang sudah disetujui dibatalkan klien | SO berstatus `cancelled` | Komisi berstatus `pending` otomatis dibatalkan (`status = cancelled`), saldo tidak bertambah, alasan pembatalan tercatat di ledger. | Not Executed |
| **`TC-AGN-COM-016`** | Eskalasi | Kewajiban Persetujuan Direktur untuk Pencairan Komisi Besar (>= Rp 25 Juta) | Agen mengajukan komisi proyek besar = Rp 35.000.000 | Finance setujui pencairan | Status dialihkan ke `pending_director_payout_approval`, mewajibkan persetujuan Direktur sebelum transfer bank dapat dieksekusi. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-AGN-COM-001 (Kalkulasi Otomatis Komisi SO)

1. **Langkah 1:** Buat Sales Order untuk inkuiri milik Agen Freelance (Rate Komisi Agen: 3.00%).
2. **Langkah 2:** Nilai Net Sales Order (sebelum PPN) = `Rp 50.000.000`.
3. **Langkah 3:** Setujui (Approve) Sales Order tersebut.
4. **Verifikasi Teknis:**
   - Hook `SalesOrderApprovedEvent` memicu `CommissionPayoutService::calculateCommission()`.
   - Tabel `commission_ledgers` mencatat 1 baris baru:
     - `agent_id`: ID Agen terkait.
     - `sales_order_id`: ID SO terkait.
     - `commission_rate`: `3.00`.
     - `amount`: `1500000.00` (Rp 1.500.000).
     - `status`: `pending_invoice_payment`.

### 3.2 Prosedur Uji TC-AGN-COM-011 (Pencegahan Double Payout Atomic Lock)

1. **Langkah 1:** Buka sesi pengajuan pencairan komisi senilai Rp 2.000.000.
2. **Langkah 2:** Kirimkan 2 request pengajuan serentak dalam jendela 50 milidetik via script uji konkurensi.
3. **Verifikasi Teknis:**
   - Mutex lock `Cache::lock('agent_payout_' . $agentId, 10)` mengunci request pertama.
   - Request pertama berhasil membuat 1 pengajuan pencairan berstatus `payout_requested`.
   - Request kedua gagal dengan pesan "Permintaan pencairan sedang diproses. Harap tunggu."
   - Saldo komisi tidak mengalami minus atau penarikan ganda.
