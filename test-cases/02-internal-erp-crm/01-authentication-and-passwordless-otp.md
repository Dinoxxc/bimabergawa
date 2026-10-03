# Test Cases: Otentikasi Passwordless Email OTP, Sesi, & Spatie RBAC
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-01` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | IAM Security Tester | Draf Awal | Pengujian otentikasi login & session timeout |
| `1.1.0` | 03 Sep 2026 | Security Automation Engineer | Ditinjau | Penambahan uji token Passwordless OTP & mitigasi brute force |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan matriks hak akses 5 peran Spatie RBAC |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 02 Okt 2026 | Principal QA Architect | Disetujui | Penyelarasan pengujian Arsitektur Panel Opsi B: verifikasi auto-redirect rute root GET / ke /admin (staf internal) atau /mitra (mitra agen freelance) via PanelRedirectionController |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi Otentikasi Tanpa Sandi & Portal Internal ERP)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-ERP-01 s.d. FR-ERP-03)
  - `DOC-PBB-SDD-03` (Arsitektur Service Otentikasi OTP & Spatie Permissions)
  - `DOC-PBB-SDD-04` (Arsitektur Dua Panel Mandiri /admin & /mitra)
  - `DOC-PBB-API-03` (Rute Subdomain ERP & Topologi Panel)
  - `DOC-PBB-SEC-01` (Kebijakan Keamanan Sesi, HTTPS Cookie, & RBAC Matrix)
- **Modul Sistem:** Modul Otentikasi, Manajemen Sesi, & Otorisasi RBAC ERP
- **Target Kelas / Service / Controller:**
  - `App\Services\OtpAuthenticationService`
  - `App\Http\Controllers\PanelRedirectionController`
  - `App\Filament\Pages\Auth\PasswordlessLogin`
  - `App\Http\Middleware\Authenticate`
  - `App\Jobs\SendOtpEmailJob`
- **Target Method:** `requestOtp()`, `verifyOtp()`, `burnOtpToken()`, `throttleCheck()`, `regenerateSession()`, `index()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-AUTH-001`** | Positif | Permintaan Token OTP dengan Alamat Email Internal Terdaftar | Pengguna internal terdaftar di tabel `users` | Input email: `sales.pic@bimabergawa.com` | Token OTP 6-digit dihasilkan, di-hash SHA-256 ke cache (TTL 300s), job email dikirim, antarmuka beralih ke input OTP. | Not Executed |
| **`TC-ERP-AUTH-002`** | Positif | Verifikasi Token OTP Valid & Regenerasi Sesi | Token OTP 6-digit aktif diterima di email | Masukkan kode OTP 6-digit persis dalam < 5 menit | Kode terverifikasi, token langsung dihapus (*One-Time Burn*), session ID diregenerasi, pengguna masuk ke dashboard Filament sesuai rolenya. | Not Executed |
| **`TC-ERP-AUTH-003`** | Positif | Penegakan Jeda Waktu Tunggu Kirim Ulang (*Cooldown 60 Detik*) | OTP pertama telah dikirim | Cek tombol "Kirim Ulang OTP" pada UI | Tombol dalam status disabled dengan hitung mundur 60 detik. Tombol baru aktif kembali setelah tepat 60 detik. | Not Executed |
| **`TC-ERP-AUTH-004`** | Positif | Otorisasi Hak Akses Peran Super Admin | Login sebagai Super Admin | Akses seluruh menu: CRM, Quotation, SO, DO, User, Settings | Seluruh menu navigasi Filament dapat diakses penuh tanpa restriksi (HTTP 200). | Not Executed |
| **`TC-ERP-AUTH-005`** | Positif | Otorisasi Hak Akses Peran Sales PIC | Login sebagai Sales PIC | Akses menu CRM Inquiries, Quotations, Sales Orders | Menu terbuka normal. Menu Pengaturan Sistem & Manajemen User disembunyikan dan ditolak jika diakses langsung (HTTP 403). | Not Executed |
| **`TC-ERP-AUTH-006`** | Positif | Otorisasi Hak Akses Peran Finance | Login sebagai Finance | Akses menu Invoices, Approval Komisi, Laporan Finansial | Dapat memverifikasi pembayaran faktur dan approve komisi. Tidak dapat mengedit katalog produk atau mengubah konfigurasi server. | Not Executed |
| **`TC-ERP-AUTH-007`** | Positif | Otorisasi Hak Akses Peran Logistik | Login sebagai Logistik / Gudang | Akses menu Delivery Orders & Cetak Surat Jalan | Dapat membuat DO parsial dan mengubah status pengiriman. Dilarang melihat margin laba HPP pada quotation (disembunyikan). | Not Executed |
| **`TC-ERP-AUTH-008`** | Positif | Pembersihan Sesi saat Logout Sukses | Pengguna berada dalam sesi aktif ERP | Klik menu profile -> "Keluar / Logout" | Sesi dihancurkan (`session()->invalidate()`), token CSRF diregenerasi, pengguna diarahkan kembali ke halaman login `app.bimabergawa.com`. | Not Executed |
| **`TC-ERP-AUTH-009`** | Negatif | Permintaan OTP dengan Email yang Tidak Terdaftar | Database `users` tidak memuat email tersebut | Input email: `penyusup@external.com` | Sistem menampilkan pesan generik: "Jika email terdaftar, kode OTP telah dikirim" (mencegah *User Enumeration Attack*). Tidak ada email keluar. | Not Executed |
| **`TC-ERP-AUTH-010`** | Negatif | Verifikasi dengan Token OTP yang Salah | OTP valid ada di cache | Input kode salah: `000000` (maksimal 3x percobaan) | Sistem menolak dengan pesan "Kode OTP salah", counter percobaan bertambah, sesi login belum terbuka. | Not Executed |
| **`TC-ERP-AUTH-011`** | Negatif | Verifikasi dengan Token OTP yang Telah Kedaluwarsa (> 5 Menit) | Kode OTP dikirim 301 detik yang lalu | Input kode OTP asli yang telah lewat 5 menit | Sistem menolak dengan pesan: "Kode OTP telah kedaluwarsa. Silakan minta kode baru." Cache OTP telah terhapus otomatis. | Not Executed |
| **`TC-ERP-AUTH-012`** | Negatif | Penggunaan Ulang Token OTP yang Sudah Terpakai (*Replay Attack*) | Token OTP sudah sukses digunakan login | Coba submit ulang request verifikasi dengan kode sama | Sistem menolak karena token telah dibakar (*burned*) pada saat login pertama kali. | Not Executed |
| **`TC-ERP-AUTH-013`** | Keamanan | Penegakan Batas Brute-Force OTP (*Throttle 3 Attempts*) | OTP aktif di cache | Salah memasukkan OTP 3 kali berturut-turut | Sesi OTP langsung dibatalkan, form terkunci dengan status HTTP 429 Too Many Requests, mewajibkan permintaan OTP baru dari awal. | Not Executed |
| **`TC-ERP-AUTH-014`** | Keamanan | Pencegahan Eksploitasi Session Fixation | Sesi anonymous sebelum login | Tangkap Session ID sebelum dan sesudah verifikasi OTP | Session ID berubah total setelah proses otentikasi berhasil (`session()->regenerate()`), session lama tidak berlaku lagi. | Not Executed |
| **`TC-ERP-AUTH-015`** | Keamanan | Penegakan Atribut Keamanan Cookie Sesi HTTPS | Sesi login aktif | Inspeksi cookie `pbb_erp_session` di browser | Memuat atribut: `HttpOnly = true`, `Secure = true`, `SameSite = Lax`, `Path = /`, Domain = `app.bimabergawa.com`. | Not Executed |
| **`TC-ERP-AUTH-016`** | Batas Nilai | Otomasi Kunci Sesi Pengguna Tidak Aktif (*Inactivity Timeout 120 Menit*) | Pengguna login dan mendiamkan tab browser | Tidak ada aktivitas mouse/keyboard selama 121 menit | Pada request berikutnya (klik menu/refresh), sistem memutus sesi otomatis dan mengarahkan pengguna ke halaman login dengan notifikasi sesi habis. | Not Executed |
| **`TC-ERP-AUTH-017`** | Positif | Auto-Redirect Rute Root (`GET /`) Berbasis Peran (Option B Dispatch) | Pengguna login dengan sesi aktif | Buka URL root `https://app.bimabergawa.com/` | Pengguna dengan peran staf internal diarahkan ke `https://app.bimabergawa.com/admin`. Pengguna dengan peran agen freelance diarahkan ke `https://app.bimabergawa.com/mitra`. Pengguna anonim (tamu) diarahkan ke form login. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-AUTH-002 (Verifikasi Passwordless OTP)

1. **Langkah 1:** Buka `https://app.bimabergawa.com/login`.
2. **Langkah 2:** Masukkan email terdaftar `sales@bimabergawa.com` lalu klik tombol "Kirim Kode OTP".
3. **Langkah 3:** Ambil 6-digit kode OTP dari mailbox server / log mailer (misal: `482910`).
4. **Langkah 4:** Masukkan `482910` ke kotak input OTP dan klik "Masuk ke Panel ERP".
5. **Verifikasi Teknis:**
   - Server merespons dengan HTTP 302 Redirect ke `https://app.bimabergawa.com`.
   - Perintah `burnOtpToken()` menghapus cache key `otp_sales@bimabergawa.com`.
   - Event `Illuminate\Auth\Events\Login` tertrigger dan dicatat di `activity_log`.

### 3.2 Prosedur Uji TC-ERP-AUTH-013 (Uji Brute-Force Lockout)

1. **Langkah 1:** Minta OTP baru untuk email pengguna.
2. **Langkah 2:** Jalankan script pengujian submit OTP salah:
   - Percobaan 1: `111111` -> Respon: "Kode OTP salah (Sisa percobaan: 2)".
   - Percobaan 2: `222222` -> Respon: "Kode OTP salah (Sisa percobaan: 1)".
   - Percobaan 3: `333333` -> Respon: "Percobaan habis. Sesi OTP dibatalkan."
3. **Verifikasi Teknis:**
   - Cache key OTP langsung dihapus dari Redis/Cache DB.
   - Percobaan ke-4 dengan kode yang benar sekalipun akan ditolak karena sesi telah terkunci.
