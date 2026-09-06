# 03. Layanan Bisnis Inti & Logika Komponen (Core Services & Business Logic)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-03` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Senior Software Engineer | Draf Awal | Implementasi `DocumentNumberService` dengan atomic lock & DomPDF |
| `1.1.0` | 03 Sep 2026 | Backend Lead | Ditinjau | Penambahan `LiveMarginCalculator` & generator PDF Delivery Order tanpa harga |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Validasi watermarking dinamis DomPDF (`DRAFT` vs `OFFICIAL`) |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.4.0` | 06 Sep 2026 | Principal System Architect | Disetujui | Perhitungan dinamis tarif PPN multi-periode via `SystemSetting` (anti-hardcoded 11%) |

---

## 1. `DocumentNumberService` (Concurrency-Safe Generator)

Menghasilkan nomor unik terurut secara atomik menggunakan `DB::transaction()` dan pesimistis locking `lockForUpdate()` untuk mencegah *race condition* akibat pengiriman form bersamaan:

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\SalesOrder;

class DocumentNumberService
{
    public static function generateInquiryNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'RFQ-' . date('Ym') . '-';
            $last = Inquiry::where('inquiry_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = $last ? (int) substr($last->inquiry_number, -4) + 1 : 1;
            return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    public static function generateQuotationNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'QUO-' . date('Ym') . '-';
            $last = Quotation::where('quotation_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = $last ? (int) substr($last->quotation_number, -4) + 1 : 1;
            return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    public static function generateSalesOrderNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'SO-' . date('Ym') . '-';
            $last = SalesOrder::where('order_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = $last ? (int) substr($last->order_number, -4) + 1 : 1;
            return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    public static function generateDeliveryOrderNumber(string $soNumber, int $partialIndex = 1): string
    {
        $cleanSo = str_replace('SO-', 'DO-', $soNumber);
        return $cleanSo . '/' . str_pad($partialIndex, 2, '0', STR_PAD_LEFT);
    }
}
```

---

## 2. `PricingAndMarginCalculatorService` (Komersial, HPP & Termin)

Mengelola seluruh logika perhitungan finansial standar B2B, verifikasi batas margin laba kotor internal, dan validasi termin pembayaran:

```php
namespace App\Services;

use InvalidArgumentException;

class PricingAndMarginCalculatorService
{
    /**
     * Hitung nilai finansial komersial penawaran harga dengan PPN dinamis multi-periode.
     * Jika $vatRate bernilai null, sistem otomatis mengambil tarif aktif via SystemSettingService / config.
     */
    public static function calculate(
        array $items,
        float $discountAmount = 0.0,
        float $commissionRate = 0.0,
        ?float $vatRate = null
    ): array {
        $subtotal = 0.0;
        $totalCost = 0.0;

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $costPrice = (float) ($item['cost_price'] ?? 0);

            $subtotal += ($qty * $unitPrice);
            $totalCost += ($qty * $costPrice);
        }

        $dpp = max(0.0, $subtotal - $discountAmount);

        // Ambil tarif PPN aktif (dinamis: 11%, 12%, 13%, dll. via setting ERP atau fallback env)
        $effectiveVatRate = $vatRate ?? (float) SystemSettingService::get('tax_vat_rate', (float) env('STATIC_VAT_RATE', 11.00));
        $taxAmount = round($dpp * ($effectiveVatRate / 100), 2);
        $grandTotal = $dpp + $taxAmount;

        // Gross Profit Margin %
        $grossProfit = $dpp - $totalCost;
        $marginPercentage = $dpp > 0 ? round(($grossProfit / $dpp) * 100, 2) : 0.0;
        $requiresApproval = ($marginPercentage < 15.0) || ($discountAmount > ($subtotal * 0.10));

        // Hak Komisi Mitra Freelance
        $commissionAmount = round($dpp * ($commissionRate / 100), 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'dpp' => $dpp,
            'vat_rate_applied' => $effectiveVatRate,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'margin_percentage' => $marginPercentage,
            'requires_director_approval' => $requiresApproval,
            'commission_amount' => $commissionAmount,
        ];
    }

    public static function validatePaymentMilestones(array $milestones): bool
    {
        $totalPercentage = 0.0;
        foreach ($milestones as $phase) {
            $totalPercentage += (float) ($phase['percentage'] ?? 0);
        }

        if (abs($totalPercentage - 100.00) > 0.01) {
            throw new InvalidArgumentException("Akumulasi total persentase termin pembayaran wajib tepat 100.00% (Saat ini: {$totalPercentage}%).");
        }

        return true;
    }
}
```

---

## 3. `DomPdfService` (PDF Rendering & Dynamic Watermark Layer)

Menghasilkan dokumen resmi PDF beresolusi tinggi dengan lapisan watermark otomatis:

```php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Quotation;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\Storage;

class DomPdfService
{
    public static function renderQuotationPdf(Quotation $quotation): string
    {
        // Tentukan teks watermark dinamis
        $watermarkText = null;
        if ($quotation->status === 'draft') {
            $watermarkText = 'DRAFT - NOT OFFICIAL';
        } elseif ($quotation->status === 'expired') {
            $watermarkText = 'EXPIRED / VOID';
        }

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation->load(['items.product.brand', 'paymentTerms', 'user']),
            'watermark' => $watermarkText,
            'company' => [
                'name' => 'PT. Panca Bima Bergawa (PBB)',
                'address' => 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Karet, Setiabudi, Jakarta Selatan 12920',
                'phone' => '+62 21-XXXXXXX',
                'email' => 'sales@bimabergawa.com'
            ]
        ])->setPaper('a4', 'portrait');

        $fileName = "quotations/{$quotation->quotation_number}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        $quotation->update(['pdf_path' => $fileName]);
        return $fileName;
    }

    public static function renderDeliveryOrderPdf(DeliveryOrder $deliveryOrder): string
    {
        // Render Surat Jalan MURNI TANPA HARGA
        $pdf = Pdf::loadView('pdf.delivery_order', [
            'deliveryOrder' => $deliveryOrder->load(['salesOrder.quotation', 'items']),
            'company' => [
                'name' => 'PT. Panca Bima Bergawa (PBB)',
                'address' => 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Jakarta Selatan 12920',
            ]
        ])->setPaper('a4', 'portrait');

        $fileName = "delivery-orders/" . str_replace('/', '-', $deliveryOrder->delivery_number) . ".pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
```

---

## 4. `ImageOptimizationService` (WebP Pipeline)

Mengonversi seluruh foto produk menjadi format WebP dengan lebar maksimum 1200px menggunakan `Intervention Image`:

```php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    public static function optimizeAndStoreProductImage(UploadedFile $file, string $folder = 'products'): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Resize proporsional jika lebar > 1200px
        if ($image->width() > 1200) {
            $image->scale(width: 1200);
        }

        // Encode ke format WebP kualitas 85%
        $encoded = $image->toWebp(quality: 85);
        $fileName = $folder . '/' . Str::random(40) . '.webp';

        Storage::disk('public')->put($fileName, (string) $encoded);
        return $fileName;
    }
}
```

---

## 5. `MonthlyDatabaseBackupService` (Pencadangan Bulanan)

Command Laravel `system:backup-database-monthly` yang dijalankan otomatis setiap tanggal 1 pukul 01:00 dini hari:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MonthlyDatabaseBackupCommand extends Command
{
    protected $signature = 'system:backup-database-monthly';
    protected $description = 'Pencadangan basis data MySQL terkompresi .sql.gz bulanan';

    public function handle(): int
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $fileName = "backups/backup-bimabergawa-" . date('Y-m-01') . ".sql.gz";
        $storagePath = storage_path("app/{$fileName}");

        if (!file_exists(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        // Eksekusi mysqldump terkompresi Gzip
        $cmd = "mysqldump -h {$dbHost} -u {$dbUser} -p\"{$dbPass}\" {$dbName} | gzip > \"{$storagePath}\"";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info("Backup basis data bulanan berhasil dibuat: {$fileName}");
            return Command::SUCCESS;
        }

        $this->error("Gagal menjalankan pencadangan basis data MySQL.");
        return Command::FAILURE;
    }
}
```

---

## 6. `BulkSkuNormalizationService` (Normalisasi & Toleransi Part Number)

Menangani pencocokan cerdas daftar BOQ/Excel dari pembeli terhadap nomor model di database katalog:

```php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class BulkSkuNormalizationService
{
    public function matchSku(string $rawInput): ?Product
    {
        // 1. Bersihkan string input (hapus spasi berlebih & uppercase)
        $cleanSku = trim(preg_replace('/\s+/', ' ', $rawInput));
        $normalizedSku = strtoupper(str_replace([' ', '-', '/', '#'], '', $cleanSku));

        // 2. Coba pencocokan exact match terlebih dahulu
        $product = Product::where('model_number', $cleanSku)->first();
        if ($product) {
            return $product;
        }

        // 3. Fallback toleran (regex perbandingan karakter alfanumerik)
        return Product::whereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(model_number, ' ', ''), '-', ''), '/', ''), '#', '')) = ?",
            [$normalizedSku]
        )->first();
    }
}
```

---

## 7. `OtpThrottlingService` (Proteksi Kuota SMTP & Rate Limiter OTP)

Melindungi endpoint login tanpa sandi (*Passwordless Email OTP*) dari serangan spam dan kehabisan kuota pengiriman email shared hosting:

```php
namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpThrottlingService
{
    public function ensureIsNotRateLimited(string $email, string $ip): void
    {
        $throttleKey = 'send-otp:' . strtolower($email) . '|' . $ip;

        // Maksimal 3 request per 10 menit
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak permintaan OTP. Silakan coba lagi dalam {$seconds} detik."
            ]);
        }

        RateLimiter::hit($throttleKey, 600); // Kunci selama 10 menit (600 detik)
    }
}
```

---

## 8. `SystemSettingService` (Manajemen Konfigurasi Dinamis & Cache Layer)

Menyediakan akses cepat terhadap konfigurasi dinamis sistem (seperti tarif PPN aktif, ambang margin, hotline WhatsApp, dan profil kantor) dengan lapisan cache database otomatis:

```php
namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    private const CACHE_TTL_SECONDS = 86400; // 24 jam (di-flush otomatis saat diupdate)

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting:{$key}", self::CACHE_TTL_SECONDS, function () use ($key, $default) {
            $setting = SystemSetting::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'number' => is_numeric($setting->value) ? (float) $setting->value : $default,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true) ?? $default,
                default => $setting->value,
            };
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $description = null): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type,
                'description' => $description,
            ]
        );

        Cache::forget("system_setting:{$key}");
    }
}
```

