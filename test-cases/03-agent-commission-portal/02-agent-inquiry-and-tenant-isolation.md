# Test Cases: Inkuiri Agen, Pelacakan Status, & Isolasi Data Tenant
## Domain 03: Portal Agen Freelance (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-AGN-02` |
| **Versi Dokumen** | 1.5.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Security & QA Tester | Draf Awal | Pengujian input inkuiri oleh mitra agen |
| `1.1.0` | 03 Sep 2026 | Multi-Tenant Architect | Ditinjau | Penambahan uji isolasi data tenant (AgentTenantScope) & mitigasi IDOR |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan visibilitas status komersial tanpa membocorkan margin HPP |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Penambahan uji chat inkuiri agen freelance, isolasi zero HPP leak dari catatan internal staf, dan pembatasan mention |
| `1.5.0` | 02 Okt 2026 | Principal QA Architect | Disetujui | Verifikasi otomatis Passed pada TC-AGN-INQ-002, TC-AGN-INQ-006, TC-AGN-INQ-007, TC-AGN-INQ-012, dan TC-AGN-INQ-018 via AgentTenantScopeTest dan InvoiceModelTest |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-01` (Spesifikasi Kebutuhan Portal Agen & Privasi Klien Agen)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-AGENT-02 & FR-AGENT-03)
  - `DOC-PBB-SAD-03` (Arsitektur Isolasi Data Multi-Tenant Agen)
  - `DOC-PBB-SDD-01` (Skema Tabel `internal_comments` Kolom `type` & `mentioned_user_ids`)
  - `DOC-PBB-SDD-04` (Komponen Livewire Internal Comments Timeline & Pembatasan Chat Agen)
  - `DOC-PBB-UIUX-04` (UX Linimasa Percakapan Portal Agen & Larangan Tab Catatan Internal)
  - `DOC-PBB-SEC-01` (Kebijakan Otorisasi RBAC & Pencegahan Kebocoran Data Antar Agen)
- **Modul Sistem:** Modul Inkuiri Agen, Obrolan Koordinasi, & Isolasi Data Multi-Tenant
- **Target Kelas / Model / Service:**
  - `App\Models\Scopes\AgentTenantScope`
  - `App\Filament\Agent\Resources\AgentInquiryResource`
  - `App\Livewire\InternalCommentsTimeline`
  - `App\Models\Inquiry`, `App\Models\InquiryItem`, `App\Models\InternalComment`
- **Target Method:** `apply()`, `createInquiry()`, `trackStatus()`, `authorizeView()`, `postComment()`, `filterCommentsForAgent()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-AGN-INQ-001`** | Positif | Input Inkuiri Klien Prospek oleh Agen Freelance | Agen login dengan akun aktif | Form: Nama Klien, Perusahaan, Kontak, Daftar SKU & Qty, Lampiran BOQ | Inkuiri tersimpan di DB dengan `agent_id` otomatis terisi ID Agen yang sedang login, status awal `lead`, notifikasi masuk ke Sales. | Passed |
| **`TC-AGN-INQ-002`** | Positif | Penegakan Isolasi Data Tenant (*AgentTenantScope Active*) | Agen A login ke portal agen | Buka menu "Inkuiri Saya" | Tabel hanya menampilkan daftar inkuiri dengan `agent_id = auth()->id()`, query database otomatis terinjeksi `WHERE agent_id = ?`. | Passed |
| **`TC-AGN-INQ-003`** | Positif | Pelacakan Status Progres Komersial oleh Agen (*Timeline Tracking*) | Inkuiri agen diproses oleh tim Sales PBB | Buka detail inkuiri dari portal agen | Agen dapat melihat timeline status: *Diterima -> Dibuatkan Penawaran -> Menang (Won) -> Terbit PO/SO -> Pengiriman Selesai*. | Passed |
| **`TC-AGN-INQ-004`** | Positif | Unggah Lampiran BOQ Proyek oleh Agen | Form inkuiri agen dibuka | Unggah berkas `spek-proyek-mall.pdf` (2.5 MB) | Berkas tersimpan di private storage, dapat diunduh kembali oleh agen dan tim internal Sales PBB. | Passed |
| **`TC-AGN-INQ-005`** | Positif | Notifikasi Email ke Agen saat Status Inkuiri Berubah | Inkuiri agen berubah status menjadi `quoted` / `won` | Tim internal mengubah status inkuiri | Sistem mengirimkan email notifikasi otomatis ke agen: "Inkuiri Prospek [Nama Klien] Telah Diterbitkan Penawaran Resmi". | Passed |
| **`TC-AGN-INQ-006`** | Negatif | Pencegahan Akses Data Inkuiri Milik Agen Lain (*Anti-IDOR*) | Inkuiri ID #50 milik Agen B | Agen A login dan mencoba akses URL `/agent/inquiries/50` | HTTP 404 Not Found (karena `AgentTenantScope` menyembunyikan record) atau HTTP 403 Forbidden. Agen A dilarang melihat data Agen B. | Passed |
| **`TC-AGN-INQ-007`** | Negatif | Pencegahan Akses Inkuiri Publik Langsung oleh Agen | Inkuiri masuk langsung via web publik (`agent_id = null`) | Agen login ke portal agen | Inkuiri publik tidak muncul pada tabel agen dan tidak dapat diakses melalui URL detail oleh akun agen. | Passed |
| **`TC-AGN-INQ-008`** | Negatif | Penolakan Penyuntingan Inkuiri Setelah Diterbitkan Penawaran | Inkuiri sudah berstatus `quoted` atau `won` | Agen mencoba mengedit rincian item SKU | Antarmuka dalam mode read-only (*view only*), tombol edit dinonaktifkan untuk menjaga konsistensi penawaran harga resmi. | Passed |
| **`TC-AGN-INQ-009`** | Negatif | Penolakan Penghapusan Inkuiri yang Sedang Berjalan | Inkuiri berstatus aktif di CRM | Agen mencoba menghapus inkuiri | Aksi delete dinonaktifkan / mengembalikan error: "Inkuiri yang sedang diproses tidak dapat dihapus". | Passed |
| **`TC-AGN-INQ-010`** | Keamanan | Penyembunyian Seluruh Informasi Margin Laba HPP dari Agen | Agen membuka detail inkuiri / penawaran | Inspeksi elemen HTML & respon JSON API portal agen | Informasi HPP, profit margin PBB, dan harga modal sama sekali tidak dikirimkan ke client side (0% kebocoran rahasia bisnis). | Passed |
| **`TC-AGN-INQ-011`** | Keamanan | Pencegahan Manipulasi Kolom `agent_id` via Form Request | Agen memanipulasi payload HTTP POST | Request memuat `agent_id = 99` (ID agen lain) | Controller mengabaikan input form dan selalu memaksa pengisian `agent_id = auth()->id()` dari sesi terverifikasi. | Passed |
| **`TC-AGN-INQ-012`** | Keamanan | Bypass Global Scope oleh Pengguna Internal Berwenang | Super Admin / Sales PIC membuka menu CRM | Query inkuiri dijalankan | Pengguna internal memiliki hak akses global (`withoutGlobalScope(AgentTenantScope::class)`), sehingga dapat melihat seluruh inkuiri agen. | Passed |
| **`TC-AGN-INQ-013`** | Batas Nilai | Penanganan Inkuiri Agen dengan Multi-Item SKU (> 30 Item) | Agen memasukkan 35 jenis item produk | Simpan form inkuiri | Seluruh 35 item tersimpan di tabel `inquiry_items` dengan relasi presisi ke master SKU tanpa timeout server. | Passed |
| **`TC-AGN-INQ-014`** | Audit | Pencatatan Aktivitas Input Inkuiri Agen pada Log | Agen menerbitkan inkuiri baru | Periksa tabel `activity_log` | Tercatat: `causer_id` (User ID Agen), Event (`agent_inquiry_created`), Nomor Registrasi, dan Waktu presisi. | Passed |
| **`TC-AGN-INQ-015`** | Ekspor | Unduh Rekap Ringkas Inkuiri Saya (*Export Summary*) | Agen memiliki 20 riwayat inkuiri | Klik tombol "Ekspor Rekap Inkuiri (PDF/CSV)" | Sistem mengekspor ringkasan inkuiri milik agen tersebut (Nama Klien, Tanggal, Status) tanpa membocorkan data pihak ketiga. | Passed |
| **`TC-AGN-INQ-016`** | UX Mobile | Pengoperasian Portal Inkuiri Agen pada Layar Smartphone | Agen mengakses via smartphone | Input inkuiri saat bertemu klien di lapangan | Form responsif, input SKU mudah dipilih via dropdown autosearch, upload foto BOQ langsung dari kamera HP berjalan lancar. | Passed |
| **`TC-AGN-INQ-017`** | Komunikasi | Pengiriman Pesan Klarifikasi Teknis oleh Mitra Agen via Obrolan Inkuiri | Inkuiri agen aktif, halaman detail dibuka agen | Kirim pesan koordinasi teknis dan lampirkan file SLD | Komentar tersimpan di `internal_comments` dengan `type = 'agent_visible'` dan `user_id = auth()->id()`. Notifikasi instan terkirim ke Sales PIC yang ditugaskan. | Passed |
| **`TC-AGN-INQ-018`** | Keamanan | Isolasi Total Catatan Rahasia Internal Staf dari Tampilan Agen | Staf internal membuat catatan `type = 'internal'` (memuat HPP & margin) pada inkuiri agen | Agen membuka halaman detail inkuiri miliknya | Catatan `internal` 100% difilter oleh kueri (`WHERE type = 'agent_visible'`), DOM agen tidak memuat teks HPP atau tab internal (Zero Leak). | Passed |
| **`TC-AGN-INQ-019`** | Keamanan | Pembatasan Mention Agen Eksklusif ke Sales PIC yang Ditugaskan | Agen mengetik `@` pada form balasan inkuiri | Inspeksi dropdown autocomplete mention | Popover hanya menampilkan nama Sales PIC yang di-assign untuk inkuiri tersebut. Nama Direktur, Finance, atau staf lain difilter keluar dari daftar. | Passed |
| **`TC-AGN-INQ-020`** | Keamanan | Pemblokiran Total Akses Agen ke Linimasa Dokumen Komersial Lain | Agen memiliki akun aktif | Agen mencoba akses URL diskusi Quotation/SO/DO via request langsung | Aplikasi memblokir akses dengan HTTP 403 Forbidden / 404 Not Found. Diskusi Quotation, SO, dan DO eksklusif hanya untuk staf internal PBB. | Passed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-AGN-INQ-002 (Verifikasi Tenant Global Scope)

1. **Langkah 1:** Login sebagai `Agen A` (User ID: 10).
2. **Langkah 2:** Buka halaman `https://app.bimabergawa.com/agent/inquiries`.
3. **Verifikasi Teknis:**
   - Query SQL yang dieksekusi oleh Laravel:
     ```sql
     SELECT * FROM inquiries WHERE agent_id = 10 AND deleted_at IS NULL ORDER BY created_at DESC;
     ```
   - Inkuiri milik `Agen B` (User ID: 11) sama sekali tidak termuat di DOM maupun memory aplikasi.

### 3.2 Prosedur Uji TC-AGN-INQ-006 (Uji Penetrasi IDOR Antar Agen)

1. **Langkah 1:** Identifikasi inkuiri ID #55 milik Agen B.
2. **Langkah 2:** Sebagai Agen A, kirim request langsung: `GET https://app.bimabergawa.com/agent/inquiries/55`.
3. **Verifikasi Teknis:**
   - Eloquent Model `Inquiry::findOrFail(55)` gagal menemukan data karena terfilter oleh `AgentTenantScope`.
   - Aplikasi merespons dengan status code `HTTP 404 Not Found`.
   - Tidak ada data nama klien atau lampiran berkas milik Agen B yang bocor ke Agen A.
