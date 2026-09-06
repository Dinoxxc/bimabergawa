# Test Cases: Registrasi Agen, Verifikasi Identitas, & Enkripsi Rekening Bank
## Domain 03: Portal Agen Freelance (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-AGN-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Agent Operations QA | Draf Awal | Pengujian registrasi akun agen dasar |
| `1.1.0` | 03 Sep 2026 | Security & Compliance QA | Ditinjau | Penambahan uji enkripsi NIK/NPWP/Bank & verifikasi berkas KTP |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan alur approval profil agen oleh Super Admin |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-01` (Spesifikasi Persona Mitra Agen Freelance & Kebutuhan Portal)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-AGENT-01 s.d. FR-AGENT-04)
  - `DOC-PBB-SDD-02` (Skema Relasi Database `users` & `agent_profiles`)
  - `DOC-PBB-SEC-04` (Enkripsi Data Sensitif At-Rest Sesuai UU PDP No. 27/2022)
- **Modul Sistem:** Modul Manajemen Profil & Registrasi Mitra Agen Freelance
- **Target Kelas / Model / Service:**
  - `App\Models\AgentProfile`
  - `App\Filament\Agent\Pages\Auth\AgentRegistration`
  - `App\Filament\Resources\AgentManagementResource`
  - `App\Services\SecureFileUploadService`
- **Target Method:** `register()`, `verifyProfile()`, `encryptBankDetails()`, `storeKtpFile()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-AGN-REG-001`** | Positif | Pendaftaran Akun Agen Freelance Mandiri Lengkap | Halaman registrasi agen dibuka | Form: Nama, Email, No. HP/WA, NIK 16 digit, NPWP, Nama Bank, No. Rekening, Foto KTP | Pendaftaran berhasil, akun dibuat dengan status `pending_verification`, berkas KTP tersimpan di private storage, notifikasi dikirim ke Admin. | Not Executed |
| **`TC-AGN-REG-002`** | Positif | Enkripsi Otomatis NIK KTP pada Basis Data (*At-Rest Encryption*) | Data agen didaftarkan | Inspeksi kolom `agent_profiles.nik_ktp` di MySQL | Data tersimpan dalam bentuk ciphertext terenkripsi (format `eyJpdiI6...`), tidak terbaca dalam bentuk teks polos (plaintext). | Not Executed |
| **`TC-AGN-REG-003`** | Positif | Enkripsi Otomatis Nomor Rekening Bank pada Basis Data | Data agen didaftarkan | Inspeksi kolom `agent_profiles.bank_account_number` | Kolom tersimpan dalam bentuk ciphertext AES-256-CBC, otomatis didekripsi hanya saat diakses via Eloquent Model resmi. | Not Executed |
| **`TC-AGN-REG-004`** | Positif | Verifikasi & Persetujuan Akun Agen oleh Super Admin | Akun agen berstatus `pending_verification` | Super Admin meninjau KTP -> Klik "Setujui Akun Agen" & tetapkan komisi (misal: 2.5%) | Status agen berubah menjadi `active`, role Spatie `mitra-agen` aktif, agen menerima email pemberitahuan akun telah disetujui. | Not Executed |
| **`TC-AGN-REG-005`** | Positif | Pembaruan Nomor Rekening Bank oleh Agen Aktif | Agen login ke portal | Update informasi bank -> Simpan profil | Rekening baru terenkripsi ulang, perubahan tercatat pada `activity_log`, histori pencairan masa lalu tetap merujuk pada rekaman historis. | Not Executed |
| **`TC-AGN-REG-006`** | Positif | Pratinjau Berkas KTP oleh Super Admin via Temporary URL | Super Admin membuka detail profil agen | Klik tombol "Lihat Berkas KTP" | Berkas ditampilkan aman melalui signed temporary URL yang kedaluwarsa dalam 10 menit, mencegah URL KTP diindeks publik. | Not Executed |
| **`TC-AGN-REG-007`** | Negatif | Validasi Format NIK KTP Tidak Valid (Bukan 16 Digit Angka) | Form registrasi agen | Input NIK = `3171012345` (10 digit) atau `3171ABC123456789` | HTTP 422. Form menolak dengan pesan: "Nomor Induk Kependudukan (NIK) wajib terdiri dari 16 digit angka numerik". | Not Executed |
| **`TC-AGN-REG-008`** | Negatif | Pendaftaran dengan Alamat Email yang Sudah Terdaftar | Email `agen.ahmad@gmail.com` sudah ada di DB | Coba daftar akun baru dengan email sama | Form menolak dengan pesan: "Alamat email ini telah terdaftar pada sistem". | Not Executed |
| **`TC-AGN-REG-009`** | Negatif | Unggah Berkas KTP dengan Format Tidak Diizinkan (.docx / .exe) | Form registrasi agen | Unggah berkas `ktp_identitas.docx` | Sistem menolak dengan pesan: "Format berkas KTP wajib berupa JPG, PNG, atau PDF". | Not Executed |
| **`TC-AGN-REG-010`** | Negatif | Penolakan Akun Agen oleh Admin dengan Alasan Wajib | Akun agen berstatus `pending_verification` | Admin klik "Tolak Akun" -> Isi alasan: "Foto KTP buram" | Status akun menjadi `rejected`, agen menerima notifikasi email beserta alasan penolakan untuk mengunggah ulang. | Not Executed |
| **`TC-AGN-REG-011`** | Keamanan | Pencegahan Akses Berkas KTP secara Langsung via URL Publik | Berkas KTP tersimpan di `storage/app/private/identity/` | Akses URL publik langsung: `https://app.bimabergawa.com/storage/identity/uuid.jpg` | HTTP 403 Forbidden / 404 Not Found. Berkas berada di disk privat di luar folder `public_html`. | Not Executed |
| **`TC-AGN-REG-012`** | Keamanan | Pencegahan Kebocoran Kunci Enkripsi `APP_KEY` pada Log Error | Query exception atau error parsing model | Pemicuan error simulasi pada model `AgentProfile` | Error handler menyembunyikan ciphertext dan APP_KEY dari log output, mencegah kebocoran kunci enkripsi. | Not Executed |
| **`TC-AGN-REG-013`** | Keamanan | Penegakan Role Spatie `mitra-agen` saat Registrasi Disetujui | Akun agen baru diapprove | Cek tabel `model_has_roles` | User ID agen hanya terikat pada role `mitra-agen`, tidak memiliki role internal (Sales PIC, Finance, Logistik, atau Super Admin). | Not Executed |
| **`TC-AGN-REG-014`** | Batas Nilai | Penanganan Nama Pemilik Rekening dengan Karakter Panjang (> 100 Karakter) | Nama PT / Yayasan Agen panjang | Input Nama Rekening: 120 karakter | Sistem memotong aman (*sanitize & trim*) atau menolak sesuai batasan skema `VARCHAR(150)` tanpa database fatal error. | Not Executed |
| **`TC-AGN-REG-015`** | Batas Nilai | Pengaturan Persentase Komisi Dasar Agen (0.1% s.d. 15.0%) | Admin menetapkan komisi pada profil agen | Input: `2.50%` | Nilai tersimpan presisi pada kolom `DECIMAL(5,2)`, digunakan sebagai basis hitung otomatis saat inkuiri agen berhasil terbit SO. | Not Executed |
| **`TC-AGN-REG-016`** | Audit | Pencatatan Verifikasi Profil Agen pada Spatie ActivityLog | Admin menyetujui akun agen | Periksa tabel `activity_log` | Tercatat: `causer_id` (Admin ID), `subject_id` (Agent Profile ID), Event (`agent_verified`), dan persentase komisi awal. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-AGN-REG-002 (Verifikasi Enkripsi Database NIK KTP)

1. **Langkah 1:** Daftarkan akun agen baru dengan NIK KTP `3171012345670001`.
2. **Langkah 2:** Buka terminal dan jalankan query MySQL langsung:
   ```sql
   SELECT id, user_id, nik_ktp, bank_account_number FROM agent_profiles ORDER BY id DESC LIMIT 1;
   ```
3. **Verifikasi Teknis:**
   - Nilai kolom `nik_ktp` di MySQL adalah string ciphertext panjang terenkripsi (Base64 payload Laravel Crypt).
   - Nilai `3171012345670001` sama sekali tidak ditemukan dalam pencarian teks mentah database.
   - Saat model `AgentProfile::find($id)` diakses di aplikasi, accessor Eloquent mengembalikan `3171012345670001` secara transparan.

### 3.2 Prosedur Uji TC-AGN-REG-011 (Uji Keamanan Akses Berkas KTP)

1. **Langkah 1:** Salin path penyimpanan berkas KTP dari database (misal: `private/identity/abc-123.jpg`).
2. **Langkah 2:** Coba akses berkas melalui peramban: `https://app.bimabergawa.com/storage/private/identity/abc-123.jpg`.
3. **Verifikasi Teknis:**
   - Web server LiteSpeed mengembalikan `404 Not Found` atau `403 Forbidden`.
   - Berkas hanya dapat diakses melalui Controller terotentikasi yang memverifikasi kepemilikan atau role Super Admin.
