# 03. Spesifikasi Rute Internal ERP Filament, Subdomain, & RBAC
## API & Routing Specification Suite (API-Routing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-API-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | ERP Architect | Draf Awal | Rute dasar Filament Admin & resource CRM |
| `1.1.0` | 03 Sep 2026 | Security Specialist | Ditinjau | Penambahan rute Passwordless OTP Login & RBAC Spatie |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Pemisahan panel mandiri agen freelance di rute `/mitra` |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas spesifik rute ERP, otorisasi RBAC, & zero emoji |

---

## 1. Topologi Subdomain ERP (`app.bimabergawa.com`)

Panel internal ERP terisolasi di bawah subdomain khusus `app.bimabergawa.com` untuk memastikan seluruh lalu lintas operasional terpisah secara logis dan tidak dapat diindeks oleh mesin pencari publik:

```php
// routes/web.php (Subdomain ERP Routing Group)
Route::domain(config('app.erp_domain', 'app.bimabergawa.com'))->group(function () {
    // 1. Passwordless Email OTP Authentication
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthOtpController::class, 'showLoginForm'])->name('filament.admin.auth.login');
        Route::post('/login/send-otp', [AuthOtpController::class, 'sendOtp'])->name('auth.otp.send');
        Route::post('/login/verify-otp', [AuthOtpController::class, 'verifyOtp'])->name('auth.otp.verify');
    });

    // 2. Filament Admin Panel & Commercial Modules (Authenticated via OTP)
    Route::middleware(['auth', 'verified', 'erp.role.guard'])->group(function () {
        // Rute streaming dan unduh berkas PDF resmi
        Route::get('/quotations/{quotation}/pdf', [QuotationPdfController::class, 'download'])
            ->name('quotations.pdf.download');
        Route::get('/delivery-orders/{deliveryOrder}/pdf', [DeliveryOrderPdfController::class, 'download'])
            ->name('delivery-orders.pdf.download');
            
        // Rute unduh slip transfer komisi agen
        Route::get('/mitra/payouts/{payout}/slip', [CommissionPayoutController::class, 'downloadSlip'])
            ->name('mitra.payouts.slip');
    });
});
```

---

## 2. Alur Otentikasi Passwordless Email OTP

1. **Request OTP (`POST /login/send-otp`):**
   - Menerima `email` pengguna terdaftar di tabel `users`.
   - Mengenerate 6 digit angka acak cryptographically secure yang disimpan di cache Redis/Database dengan TTL 5 menit.
   - Mengirim email notifikasi berisi 6 digit OTP.
2. **Verify OTP (`POST /login/verify-otp`):**
   - Menerima `email` dan `otp_code`.
   - Melakukan validasi kesesuaian dan masa berlaku kode.
   - Menginisiasi sesi Laravel terenkripsi (`auth()->login($user)`) dan meregenerasi Session ID untuk mencegah *Session Fixation*.

---

## 3. Matriks Hak Akses Rute Berbasis Peran (RBAC Route Matrix)

| Modul / Rute Filament | Peran Super Admin | Peran Sales Rep | Peran Finance | Peran Logistik | Peran Mitra Agen |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **`/` (Executive Dashboard)** | Akses Penuh | Terbatas PIC | Terbatas Finansial | Terbatas Pengiriman | Ditolak |
| **`/inquiries` (Kanban CRM)** | Akses Penuh | Akses Penuh | Read-Only | Read-Only | Ditolak |
| **`/quotations` (Builder)** | Akses Penuh | Akses Penuh | Read-Only | Ditolak | Ditolak |
| **`/sales-orders` (SO Lock)** | Akses Penuh | Buat & Lihat | Verifikasi Bayar | Lihat Item | Ditolak |
| **`/delivery-orders` (DO)** | Akses Penuh | Lihat Status | Read-Only | Buat & Update Resi| Ditolak |
| **`/products` (Master Spek)**| Akses Penuh | Read-Only (HPP) | Read-Only | Read-Only | Ditolak |
| **`/partners` (Screening)** | Akses Penuh | Ditolak | Ditolak | Ditolak | Ditolak |
| **`/payouts` (Komisi Mitra)**| Akses Penuh | Ditolak | Proses & Upload | Ditolak | Ditolak |
| **`/mitra` (Portal Agen)** | Akses Audit | Ditolak | Ditolak | Ditolak | Akses Mandiri |

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
