# 03. Layanan Bisnis Inti & Logika Komponen (Core Services & Business Logic)
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-03` |
| **Versi Dokumen** | 1.9.1 |
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
| `1.5.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan penomoran atomik Faktur Penagihan (`INV-YYYYMM-XXXX`) pada `DocumentNumberService` |
| `1.6.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Penambahan spesifikasi DTOs (Data Transfer Objects), Domain Events, dan Custom Domain Exceptions untuk Fondasi Service Layer Tahap A |
| `1.7.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Penyempurnaan parameter DTOs: penambahan ekstensi BOQ industri (xls, docx, csv, jpeg) dan maxSizeBytes pada FileUploadRequestDTO, field fisik armada logistik pada DeliveryOrderFulfillmentDTO, diskon nominal lump-sum pada CalculateMarginDTO, serta konstanta dan guard validasi tipe dokumen pada DocumentNumberRequestDTO |
| `1.8.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Harmonisasi arsitektur `DocumentNumberService` berbasis tabel `document_sequences` dengan pesimistis locking (`lockForUpdate()`), `DB::transaction(..., 3)` auto-retry deadlock, integrasi `DocumentNumberRequestDTO`, format resmi `INQ-YYYYMM-XXXX`, revisi penawaran `-R{n}`, dan emisi domain event `DocumentNumberGenerated` |
| `1.9.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Pembaruan standar penomoran resmi korporat PBB pada kasus uji dan arsitektur |
| `1.9.1` | 20 Sep 2026 | Principal System Architect | Disetujui | Penyeragaman seluruh kode tipe transaksi menjadi tepat 3 huruf (`SO` -> `SOR`, `DO` -> `DOR`) untuk kepatuhan standar format korporat PBB |
| `1.10.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Pembersihan format penomoran dokumen menjadi standar korporat bersih `PBB/{TIPE}/{YYYY}/{MM}/{XXXX}` tanpa salt kriptografis |

---

## 1. `DocumentNumberService` (Concurrency-Safe Generator)

Menghasilkan nomor unik terurut secara atomik menggunakan tabel terisolasi `document_sequences`, transaksi database dengan pesimistis locking `lockForUpdate()`, mekanisme auto-retry 3x saat terjadi deadlock MySQL (`DB::transaction(..., 3)`), serta menerapkan standar format korporat B2B Indonesia yang bersih, elegan, dan profesional (`PBB/{TYPE}/YYYY/MM/XXXX`) tanpa karakter salt acak untuk kepatuhan format resmi `AGENTS.md` Bagian 3.7:

```php
namespace App\Services;

use App\DTOs\DocumentNumberRequestDTO;
use App\Events\DocumentNumberGenerated;
use App\Models\DocumentSequence;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    /**
     * Peta awalan prefix dokumen resmi PT. Panca Bima Bergawa (Semua tipe 3 huruf).
     */
    public const PREFIX_MAP = [
        DocumentNumberRequestDTO::TYPE_INQUIRY        => 'INQ',
        DocumentNumberRequestDTO::TYPE_QUOTATION      => 'QUO',
        DocumentNumberRequestDTO::TYPE_SALES_ORDER    => 'SOR',
        DocumentNumberRequestDTO::TYPE_DELIVERY_ORDER => 'DOR',
        DocumentNumberRequestDTO::TYPE_INVOICE        => 'INV',
        DocumentNumberRequestDTO::TYPE_COMMISSION     => 'COM',
        'inquiry'                                     => 'INQ',
        'quotation'                                   => 'QUO',
        'sales_order'                                 => 'SOR',
        'delivery_order'                              => 'DOR',
        'invoice'                                     => 'INV',
        'commission'                                  => 'COM',
        'INQ'                                         => 'INQ',
        'QUO'                                         => 'QUO',
        'SOR'                                         => 'SOR',
        'DOR'                                         => 'DOR',
        'INV'                                         => 'INV',
        'COM'                                         => 'COM',
        'SO'                                          => 'SOR',
        'DO'                                          => 'DOR',
    ];

    /**
     * Generate nomor dokumen secara concurrency-safe via DocumentNumberRequestDTO atau tipe string.
     * Menghasilkan format resmi: PBB/{TIPE}/{YYYY}/{MM}/{XXXX}
     */
    public function generateNextNumber(
        DocumentNumberRequestDTO|string $request,
        ?DateTimeInterface $date = null,
        ?int $userId = null
    ): string {
        $effectiveDate = $date;
        $effectiveUserId = $userId;

        if ($request instanceof DocumentNumberRequestDTO) {
            $targetType = $request->targetType;
            $effectiveDate = $request->date ?? $date;
        } else {
            $targetType = $request;
        }

        $normalizedType = trim($targetType);
        $lookupKey = strtolower($normalizedType);

        if (! isset(self::PREFIX_MAP[$lookupKey]) && ! isset(self::PREFIX_MAP[$normalizedType])) {
            throw new InvalidArgumentException(sprintf(
                'Tipe dokumen tidak valid: [%s]. Harus salah satu dari tipe terdaftar.',
                $targetType
            ));
        }

        $prefix = self::PREFIX_MAP[$lookupKey] ?? self::PREFIX_MAP[$normalizedType];
        $targetDate = $effectiveDate ?? now();
        $year = $targetDate->format('Y');
        $month = $targetDate->format('m');
        $yearMonth = $year . $month;

        $sequenceNumber = DB::transaction(function () use ($prefix, $yearMonth) {
            $sequenceRecord = DocumentSequence::where('document_type', $prefix)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->first();

            if (! $sequenceRecord) {
                try {
                    $sequenceRecord = DocumentSequence::create([
                        'document_type'    => $prefix,
                        'year_month'       => $yearMonth,
                        'current_sequence' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $sequenceRecord = DocumentSequence::where('document_type', $prefix)
                        ->where('year_month', $yearMonth)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $sequenceRecord = DocumentSequence::where('id', $sequenceRecord->id)
                    ->lockForUpdate()
                    ->first();
            }

            $sequenceRecord->increment('current_sequence');

            return (int) $sequenceRecord->current_sequence;
        }, 3);

        if ($sequenceNumber > 9999) {
            $documentNumber = sprintf('PBB/%s/%s/%s/%d', $prefix, $year, $month, $sequenceNumber);
        } else {
            $documentNumber = sprintf('PBB/%s/%s/%s/%04d', $prefix, $year, $month, $sequenceNumber);
        }

        $resolvedUserId = $effectiveUserId ?? (auth()->check() ? auth()->id() : null);

        DocumentNumberGenerated::dispatch($targetType, $documentNumber, $resolvedUserId);

        return $documentNumber;
    }

    public function generate(DocumentNumberRequestDTO $dto, ?int $userId = null): string
    {
        return $this->generateNextNumber($dto, $dto->date, $userId);
    }

    public function generateForType(string $targetType, ?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber($targetType, $date, $userId);
    }

    public function generateInquiryNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_INQUIRY, $date, $userId);
    }

    public function generateQuotationNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_QUOTATION, $date, $userId);
    }

    public function generateSalesOrderNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_SALES_ORDER, $date, $userId);
    }

    public function generateInvoiceNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_INVOICE, $date, $userId);
    }

    public function generateCommissionNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_COMMISSION, $date, $userId);
    }

    public function generateDeliveryOrderNumber(
        ?string $soNumber = null,
        int $partialIndex = 1,
        ?DateTimeInterface $date = null,
        ?int $userId = null
    ): string {
        if ($soNumber !== null && trim($soNumber) !== '') {
            return $this->formatDeliveryOrderPartial($soNumber, $partialIndex);
        }

        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_DELIVERY_ORDER, $date, $userId);
    }

    /**
     * Format revisi penawaran resmi (misal: PBB/QUO/2026/09/0001-R1).
     */
    public function formatQuotationRevision(string $baseQuoNumber, int $revision): string
    {
        $cleanBase = preg_replace('/-R\d+$/i', '', trim($baseQuoNumber));
        if ($revision <= 0) {
            return $cleanBase;
        }

        return sprintf('%s-R%d', $cleanBase, $revision);
    }

    /**
     * Format surat jalan parsial berdasar sales order (misal: PBB/DOR/2026/09/0001/01).
     */
    public function formatDeliveryOrderPartial(string $soNumber, int $partialIndex = 1): string
    {
        $cleanSo = trim($soNumber);

        if (str_contains($cleanSo, '/SOR/')) {
            $cleanDo = str_replace('/SOR/', '/DOR/', $cleanSo);
        } elseif (str_contains($cleanSo, '/SO/')) {
            $cleanDo = str_replace('/SO/', '/DOR/', $cleanSo);
        } else {
            $cleanDo = preg_replace('/^(SO|SOR)-/i', 'DOR-', $cleanSo);
        }

        return sprintf('%s/%02d', $cleanDo, max(1, $partialIndex));
    }
}
```
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



---

## 9. Standard B2B Data Transfer Objects (`app/DTOs/`)

Untuk memastikan integritas tipe data yang ketat (`declare(strict_types=1);`), immutabilitas data antar-lapisan, dan memfasilitasi refactoring modular, seluruh parameter kompleks antar-lapisan (Controller, Service, Event, Listener) diwajibkan menggunakan PHP 8.3 `readonly class` DTOs:

### 9.1 `DocumentNumberRequestDTO`
```php
namespace App\DTOs;

readonly class DocumentNumberRequestDTO
{
    public const TYPE_INQUIRY = 'inquiry';
    public const TYPE_QUOTATION = 'quotation';
    public const TYPE_SALES_ORDER = 'sales_order';
    public const TYPE_DELIVERY_ORDER = 'delivery_order';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_COMMISSION = 'commission';

    public const VALID_TYPES = [
        self::TYPE_INQUIRY,
        self::TYPE_QUOTATION,
        self::TYPE_SALES_ORDER,
        self::TYPE_DELIVERY_ORDER,
        self::TYPE_INVOICE,
        self::TYPE_COMMISSION,
    ];

    public function __construct(
        public string $targetType,
        public ?\DateTimeInterface $date = null,
        public ?int $tenantId = null,
    ) {
        if (! in_array($this->targetType, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid document target type [%s]. Must be one of: %s',
                $this->targetType,
                implode(', ', self::VALID_TYPES)
            ));
        }
    }
}
```

### 9.2 `CalculateMarginDTO`
```php
namespace App\DTOs;

readonly class CalculateMarginDTO
{
    public function __construct(
        public float $costPrice,
        public float $unitPrice,
        public int|float $quantity = 1,
        public float $discountPercent = 0.0,
        public float $discountAmount = 0.0,
        public float $vatRate = 11.0,
        public float $exchangeRate = 1.0,
        public string $currency = 'IDR',
    ) {}
}
```

### 9.3 `ParseSkuRequestDTO`
```php
namespace App\DTOs;

readonly class ParseSkuRequestDTO
{
    public function __construct(
        public string $rawContent,
        public string $delimiter = "\n",
        public bool $strictBrandCheck = false,
    ) {}
}
```

### 9.4 `FileUploadRequestDTO`
```php
namespace App\DTOs;

readonly class FileUploadRequestDTO
{
    public function __construct(
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public string $tmpPath,
        public array $allowedExtensions = ['pdf', 'xlsx', 'xls', 'docx', 'jpg', 'jpeg', 'png', 'csv'],
        public bool $isPublic = false,
        public int $maxSizeBytes = 10485760,
    ) {}
}
```

### 9.5 `DeliveryOrderFulfillmentDTO`
```php
namespace App\DTOs;

readonly class DeliveryOrderFulfillmentDTO
{
    public function __construct(
        public int $salesOrderId,
        public array $items, // [['sales_order_item_id' => int, 'quantity_delivered' => int]]
        public string $shippingMethod,
        public ?string $driverNotes = null,
        public ?string $driverName = null,
        public ?string $vehiclePlateNumber = null,
        public ?string $courierName = null,
        public ?string $trackingNumber = null,
        public ?string $recipientPicName = null,
        public ?string $recipientPhone = null,
        public ?string $shippingAddress = null,
        public ?int $totalPackages = null,
        public ?float $totalWeightKg = null,
    ) {}
}
```

### 9.6 `CommissionCalculationDTO`
```php
namespace App\DTOs;

readonly class CommissionCalculationDTO
{
    public function __construct(
        public int $salesOrderId,
        public int $agentId,
        public float $subtotalEligible,
        public float $tierRatePercent,
        public float $calculatedAmount,
    ) {}
}
```

---

## 10. Standard Domain Events (`app/Events/`)

Sistem menggunakan Domain Events untuk decoupling aksi sekunder (notifikasi, audit trail, pembuatan dokumen turunan, telemetri):

| Nama Domain Event | Payload Utama | Trigger / Asal Kejadian | Aksi Turunan Listener |
| :--- | :--- | :--- | :--- |
| `DocumentNumberGenerated` | `targetType`, `documentNumber`, `userId` | `DocumentNumberService` | Audit log penomoran dokumen |
| `QuotationCalculated` | `quotationId`, `subtotal`, `marginPercent`, `vatAmount` | `QuotationCalculationService` | Evaluasi ambang batas approval direksi |
| `QuotationApproved` | `quotationId`, `approverUserId`, `approvedAt` | Modul Approval Direksi | Status transition & notifikasi sales PIC |
| `SalesOrderConfirmed` | `salesOrderId`, `soNumber`, `grandTotal` | Penerimaan PO Klien | Reservasi stok & penyiapan draf Delivery Order |
| `DeliveryOrderDispatched` | `deliveryOrderId`, `doNumber`, `shippingMethod` | Modul Logistik Fisik | Notifikasi pengiriman ke klien & sales |
| `CommissionPayoutRequested` | `payoutId`, `agentId`, `amount`, `bankTarget` | Pengajuan Pencairan Agen | Notifikasi tim Finance untuk verifikasi struk |

---

## 11. Custom Domain Exceptions (`app/Exceptions/`)

Seluruh penanganan kesalahan logika bisnis menggunakan custom typed exceptions dengan pesan kesalahan yang terstandardisasi dan informatif:

| Nama Custom Exception | Skenario Pemicu | HTTP / UI Feedback |
| :--- | :--- | :--- |
| `CriticalMarginException` | Margin penawaran di bawah ambang batas kritis (< 8.00%) tanpa eskalasi approval direktur resmi. | HTTP 422 Unprocessable Entity: Menolak penerbitan quotation tanpa token approval direktur. |
| `DuplicateDocumentNumberException` | Terdeteksi tabrakan nomor dokumen setelah retry transaksi atomik maksimal 3 kali. | HTTP 409 Conflict: Sistem gagal mendapatkan slot urutan atomik, coba beberapa saat lagi. |
| `IdempotencyConflictException` | Aksi pengajuan atau transaksi dipanggil berulang dengan `idempotency_key` yang sama saat operasi pertama masih diproses atau sudah selesai. | HTTP 409 Conflict: Permintaan duplikat terdeteksi, operasi sedang diproses atau telah selesai. |
| `InvalidFileMagicBytesException` | Berkas yang diunggah memalsukan ekstensi file (MIME magic bytes tidak sesuai dengan header biner). | HTTP 422 Unprocessable Entity: Berkas ditolak karena anomali integritas biner / magic bytes. |
| `InsufficientFulfillmentQuantityException` | Kuantitas pengiriman pada Surat Jalan parsial melebihi sisa kuantitas Sales Order yang belum terkirim. | HTTP 422 Unprocessable Entity: Kuantitas pengiriman melebihi sisa alokasi pesanan penjualan. |
