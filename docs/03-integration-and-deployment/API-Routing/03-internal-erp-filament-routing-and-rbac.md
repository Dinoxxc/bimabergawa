# 03. Spesifikasi Rute Internal ERP Filament, Subdomain, & RBAC
## API & Routing Specification Suite (API-Routing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-API-03` |
| **Versi Dokumen** | 1.5.0 |
| **Tanggal Efektif** | Oktober 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | ERP Architect | Draf Awal | Rute dasar Filament Admin & resource CRM |
| `1.1.0` | 03 Sep 2026 | Security Specialist | Ditinjau | Penambahan rute Passwordless OTP Login & RBAC Spatie |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Pemisahan panel mandiri agen freelance di rute `/mitra` |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas spesifik rute ERP, otorisasi RBAC, & zero emoji |
| `1.4.0` | 02 Okt 2026 | Principal Integration Engineer | Disetujui | Penyelarasan Arsitektur Panel Opsi B: Pendefinisian dua rute panel mandiri di subdomain `app.bimabergawa.com` — `/admin` untuk Panel Internal Staf dan `/mitra` untuk Portal Agen Freelance dengan rute root `GET /` auto-redirect cerdas via `PanelRedirectionController` serta isolasi total modul finansial |
| `1.5.0` | 03 Okt 2026 | Principal Integration Engineer | Disetujui | Penambahan rute dan RBAC matrix untuk `/admin/suppliers` (Manajemen Rekanan Vendor), `/admin/reports` (Executive Reports Hub 4-Tab), serta rute verifikasi QR Code publik `/verify-doc/{type}/{hash}` |

---

## 1. Topologi Subdomain ERP & Arsitektur Dua Panel (`app.bimabergawa.com`)

Panel internal ERP dan portal kemitraan terisolasi di bawah subdomain khusus `app.bimabergawa.com` untuk memastikan seluruh lalu lintas operasional terpisah secara logis dan tidak dapat diindeks oleh mesin pencari publik:

```php
// routes/erp.php (atau routes/web.php Subdomain ERP Routing Group)
Route::domain(config('app.erp_domain', 'app.bimabergawa.com'))->group(function () {
    // 1. Root Intelligent Redirection (Auto-Routing Berdasarkan Peran)
    Route::get('/', [PanelRedirectionController::class, 'index'])->name('erp.root');

    // 2. Passwordless Email OTP Authentication Shared Flow
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthOtpController::class, 'showLoginForm'])->name('auth.otp.login');
        Route::post('/login/send-otp', [AuthOtpController::class, 'sendOtp'])->name('auth.otp.send');
        Route::post('/login/verify-otp', [AuthOtpController::class, 'verifyOtp'])->name('auth.otp.verify');
    });

    // 3. Panel Staf Internal (/admin) - Dikelola oleh AdminPanelProvider
    // Middleware: ['auth', 'verified', 'role:super_admin|sales_pic|finance|logistics']
    
    // 4. Portal Kemitraan Agen (/mitra) - Dikelola oleh AgentPanelProvider
    // Middleware: ['auth', 'verified', 'role:freelance_agent']

    // 5. Rute Download Dokumen Transaksi Berizin & Signed URL
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/admin/quotations/{quotation}/pdf', [QuotationPdfController::class, 'download'])
            ->name('admin.quotations.pdf.download');
        Route::get('/admin/delivery-orders/{deliveryOrder}/pdf', [DeliveryOrderPdfController::class, 'download'])
            ->name('admin.delivery-orders.pdf.download');
        Route::get('/mitra/payouts/{payout}/slip', [CommissionPayoutController::class, 'downloadSlip'])
            ->name('mitra.payouts.slip');
    });

    // 6. Rute Verifikasi Publik QR Code Dokumen Transaksi (Tanpa Login)
    Route::get('/verify-doc/{type}/{hash}', [DocumentVerificationController::class, 'verify'])
        ->name('document.verify');
});
```

---

## 2. Alur Otentikasi Passwordless Email OTP

1. **Request OTP (`POST /login/send-otp`):**
   - Menerima `email` pengguna terdaftar di tabel `users`.
   - Mengenerate 6 digit angka acak CSPRNG (`random_int(100000, 999999)`) yang di-hash SHA-256 dan disimpan di cache dengan TTL 300 detik (5 menit).
   - Mengirim email notifikasi berisi 6 digit OTP resmi.
2. **Verify OTP (`POST /login/verify-otp`):**
   - Menerima `email` dan `otp_code`.
   - Melakukan validasi kesesuaian dan masa berlaku kode (maksimal 3x percobaan gagal).
   - Menginisiasi sesi Laravel terenkripsi (`auth()->login($user)`), membakar token OTP (*one-time burn*), dan meregenerasi Session ID untuk mencegah *Session Fixation*.
3. **Redirect Berbasis Peran (*Post-Login Dispatch*):**
   - Staf internal (`super_admin`, `sales_pic`, `finance`, `logistics`) diarahkan ke `/admin`.
   - Mitra agen freelance (`freelance_agent`) diarahkan ke `/mitra`.

---

## 3. Matriks Hak Akses Rute Berbasis Peran (RBAC Route Matrix)

| Modul / Rute Filament | Peran Super Admin | Peran Sales Rep | Peran Finance | Peran Logistik | Peran Mitra Agen |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **`/` (Root Redirection)** | Auto -> `/admin` | Auto -> `/admin` | Auto -> `/admin` | Auto -> `/admin` | Auto -> `/mitra` |
| **`/admin` (Dashboard Internal)**| Akses Penuh | Terbatas PIC | Terbatas Finansial | Terbatas Pengiriman | Ditolak (403) |
| **`/admin/inquiries` (Kanban CRM)**| Akses Penuh | Akses Penuh | Read-Only | Read-Only | Ditolak (403) |
| **`/admin/quotations` (Builder)**| Akses Penuh | Akses Penuh | Read-Only | Ditolak | Ditolak (403) |
| **`/admin/sales-orders` (SO Lock)**| Akses Penuh | Buat & Lihat | Verifikasi Bayar | Lihat Item | Ditolak (403) |
| **`/admin/delivery-orders` (DO)**| Akses Penuh | Lihat Status | Read-Only | Buat & Update Resi| Ditolak (403) |
| **`/admin/products` (Master Spek)**| Akses Penuh | Read-Only (HPP) | Read-Only | Read-Only | Ditolak (403) |
| **`/admin/suppliers` (Vendor Rekanan)**| Akses Penuh | Lihat & Kontak | Lihat & Tempo Bayar| Ditolak | Ditolak (403) |
| **`/admin/reports` (Executive Reports)**| Akses Penuh (4 Tab)| Terbatas Tab 1 | Tab 2, 3, & 4 | Ditolak | Ditolak (403) |
| **`/admin/invoices` (Faktur)** | Akses Penuh | Ditolak | Verifikasi Bank | Ditolak | Ditolak (403) |
| **`/admin/system-settings`** | Akses Penuh | Terbatas CRM | Terbatas Pajak | Terbatas Gudang | Ditolak (403) |
| **`/mitra` (Portal Mandiri Agen)** | Akses Audit | Ditolak | Ditolak | Ditolak | Akses Mandiri |
| **`/mitra/inquiries` (Inkuiri Agen)**| Akses Audit | Ditolak | Ditolak | Ditolak | Milik Sendiri |
| **`/mitra/payouts` (Komisi)** | Akses Audit | Ditolak | Ditolak | Ditolak | Ajukan Mandiri |
| **`/verify-doc/{type}/{hash}`** | Akses Terbuka | Akses Terbuka | Akses Terbuka | Akses Terbuka | Akses Terbuka |

---

## 4. Spesifikasi Streaming PDF Ber-Kop Surat Resmi

### 4.1 Endpoint Download Surat Penawaran (`GET /quotations/{id}/pdf`)
- **Controller:** `App\Http\Controllers\QuotationPdfController@download`
- **Output:** File `application/pdf` yang dirender via DomPDF secara streaming tanpa menyimpan berkas statis di disk publik.
- **Kondisi Watermark:**
  - Status `draft`: Watermark diagonal merah `DRAFT - NOT OFFICIAL`.
  - Status `sent` / `approved`: Berkas bersih ber-Kop Surat resmi PT. Panca Bima Bergawa.
  - Status `expired` / `cancelled`: Watermark abu-abu `VOID / EXPIRED`.

### 4.2 Endpoint Download Surat Jalan Pengiriman (`GET /delivery-orders/{id}/pdf`)
- **Controller:** `App\Http\Controllers\DeliveryOrderPdfController@download`
- **Output:** File `application/pdf` ber-Kop Surat resmi.
- **Batasan Kerahasiaan (Zero Price):** Seluruh nominal harga, HPP, diskon, dan subtotal dihilangkan $100\%$ dari berkas PDF Surat Jalan fisik.

---

## 5. Rute Portal Mandiri Freelance Agent (`app.bimabergawa.com/mitra`)

Untuk mitra agen freelance, antarmuka dibatasi hanya pada panel khusus dengan isolasi data otomatis (*Global Eloquent Scope* `where('agent_id', auth()->id())`):

| URI Path Mitra | Fungsionalitas |
| :--- | :--- |
| `/mitra` | Dashboard performa komisi pribadi, omzet terbuku, & status bayar |
| `/mitra/leads/create` | Formulir input prospek baru (otomatis terikat ke ID agen yang sedang login) |
| `/mitra/leads` | Daftar riwayat prospek dan status transaksi (Inquiry -> Quote -> SO -> Selesai) |
| `/mitra/payouts` | Riwayat pencairan komisi bank dan tombol unduh bukti transfer resmi |
| `/mitra/payouts/{id}/slip` | Unduh file slip transfer bank resmi (`bukti_transfer.jpg/pdf`) |
