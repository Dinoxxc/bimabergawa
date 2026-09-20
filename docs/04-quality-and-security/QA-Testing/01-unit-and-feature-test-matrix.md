# 01. Matriks Kasus Uji Otomatis Unit & Feature Test (Pest PHP)
## Quality Assurance & Testing Suite (QA-Testing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-QA-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Engineer | Draf Awal | Kasus uji dasar submit RFQ & RBAC Spatie |
| `1.1.0` | 03 Sep 2026 | Test Automation Lead | Ditinjau | Penambahan uji validasi margin HPP & normalisasi SKU |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan pengujian snapshot PPN & otentikasi OTP |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Pemisahan berkas mandiri matriks uji Pest PHP & zero emoji |

---

## 1. Matriks Pengujian Unit (Unit Tests - `tests/Unit/`)

Pengujian fungsionalitas logika murni pada layer Domain Services dan Utility Models tanpa memicu HTTP request:

| ID Uji | Kelas Layanan (*Service Class*) | Skenario Kasus Uji | Ekspektasi Hasil (*Assertion*) |
| :--- | :--- | :--- | :--- |
| **UT-01** | `CommercialCalculationService` | Menghitung DPP, PPN 11%, dan Gross Margin ($18\text{ Jt}$ HPP, $22.5\text{ Jt}$ Jual). | DPP: $22.5\text{ Jt}$, Tax: $2.475\text{ Jt}$, Margin: $20.0\%$, Approval: `false`. |
| **UT-02** | `CommercialCalculationService` | Menghitung penawaran dengan margin rendah ($8.5\%$) atau diskon $>10\%$. | Margin: $8.5\%$, Flag `requires_director_approval` bernilai `true`. |
| **UT-03** | `CommercialCalculationService` | Validasi termin pembayaran milestone dengan total bobot $90\%$ atau $110\%$. | Melempar eksepsi `InvalidArgumentException` ("Total termin wajib 100%"). |
| **UT-04** | `BulkSkuNormalizationService` | Memasukkan part number bervariasi (`FSHA 65-50-200/5.5`, `fsha-65-50-200`, `FSHA  65 50 200`). | Seluruh variasi berhasil me-resolve model produk Ebara FSHA 65-50-200. |
| **UT-05** | `DocumentNumberService` | Permintaan nomor dokumen pada bulan September 2026. | Menghasilkan string terformat `PBB/QUO/2026/092F9C/0001` dengan atomic lock. |

### Contoh Kode Uji Pest PHP (`tests/Unit/CommercialCalculationTest.php`)

```php
use App\Services\CommercialCalculationService;

test('kalkulasi margin laba kotor dan ppn snapshot berfungsi akurat', function () {
    $items = [
        ['quantity' => 2, 'unit_price' => 22500000, 'cost_price' => 18000000, 'discount_percent' => 0]
    ];

    $result = CommercialCalculationService::calculateQuotationSummary($items, 0, 11.0, 3.0);

    expect($result['subtotal'])->toBe(45000000.0)
        ->and($result['dpp'])->toBe(45000000.0)
        ->and($result['tax_amount'])->toBe(4950000.0)
        ->and($result['grand_total'])->toBe(49950000.0)
        ->and($result['margin_percentage'])->toBe(20.0)
        ->and($result['requires_director_approval'])->toBeFalse()
        ->and($result['commission_amount'])->toBe(1350000.0);
});

test('validasi termin milestone melempar eksepsi jika akumulasi tidak 100 persen', function () {
    $invalidMilestones = [
        ['phase_name' => 'DP', 'percentage' => 30],
        ['phase_name' => 'Pelunasan', 'percentage' => 60],
    ];

    expect(fn () => CommercialCalculationService::validatePaymentMilestones($invalidMilestones))
        ->toThrow(InvalidArgumentException::class);
});
```

---

## 2. Matriks Pengujian Fitur & Integrasi (Feature Tests - `tests/Feature/`)

| ID Uji | Modul / Endpoint | Skenario Kasus Uji | Ekspektasi Hasil (*Assertion*) |
| :--- | :--- | :--- | :--- |
| **FT-01** | `POST /rfq/submit` | Mengirim data pemohon lengkap dan 3 SKU produk dari keranjang. | Status `201 Created`, record tersimpan di tabel `inquiries` dan `inquiry_items`. |
| **FT-02** | `POST /api/bulk-sku/parse` | Mengirim teks multiline 3 part number (2 valid, 1 fiktif). | Status `200 OK`, `matched_count: 2`, `unmatched_count: 1`. |
| **FT-03** | `POST /login/send-otp` | Meminta kode OTP lebih dari 3 kali dalam 10 menit dari IP yang sama. | Permintaan ke-4 gagal dengan status `422 Unprocessable` / `429 Too Many Requests`. |
| **FT-04** | `POST /login/verify-otp` | Memasukkan kode 6 digit OTP yang sesuai dan belum kedaluwarsa. | Status `200 OK`, inisiasi sesi otentikasi aktif, redirect ke panel. |
| **FT-05** | `GET /quotations/{id}/pdf` | Mengunduh dokumen penawaran berstatus `draft`. | Output file `application/pdf` memuat stempel watermark merah `DRAFT - NOT OFFICIAL`. |
| **FT-06** | `GET /mitra` (RBAC) | Pengguna dengan peran `sales_representative` mencoba mengakses rute portal mitra. | Akses ditolak dengan kode `403 Forbidden`. |
| **FT-07** | `POST /lacak-rfq/search` | Mencari status pesanan via nomor RFQ resmi. | Status `200 OK`, mengembalikan riwayat tahapan progres dan info resi ekspedisi. |

---

## 3. Eksekusi Pengujian Otomatis

```bash
# Menjalankan seluruh Unit dan Feature Test
php artisan test --parallel

# Menjalankan pengujian dengan laporan cakupan kode (Code Coverage)
php artisan test --coverage --min=80
```
