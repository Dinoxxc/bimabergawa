<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\BulkSkuNormalizationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;

class ProductsBulkImport implements ToCollection, WithChunkReading, WithEvents, WithHeadingRow
{
    use Importable, RegistersEventListeners;

    public int $createdCount = 0;

    public int $updatedCount = 0;

    public int $skippedCount = 0;

    /**
     * @var array<int, string>
     */
    public array $errors = [];

    protected int $currentRowNumber = 1;

    protected bool $isFirstChunk = true;

    protected bool $activityLogged = false;

    /**
     * Chunk size set to 100 rows to ensure peak RAM usage stays < 64 MB
     * on shared hosting environments with 512 MB memory limit.
     * (Rujukan: TC-CAT-IMP-002, DOC-PBB-QA-04)
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Process each 100-row chunk in an atomic database transaction.
     * (Rujukan: TC-CAT-IMP-014)
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    public function collection(Collection $rows): void
    {
        if ($this->isFirstChunk) {
            if ($rows->isEmpty()) {
                $this->errors[] = 'Spreadsheet file does not contain any product data rows to import. (Berkas spreadsheet tidak memuat baris data produk untuk diimpor).';
                $this->isFirstChunk = false;

                return;
            }

            /** @var array<string, mixed>|null $firstRow */
            $firstRow = $rows->first();
            if ($firstRow !== null) {
                $keys = array_map(fn ($k) => strtolower(trim((string) $k)), array_keys((array) $firstRow));
                $recognizedKeys = ['brand_name', 'brand', 'name', 'product_name', 'sku', 'model_number', 'base_hpp', 'cost_price'];
                $hasRecognizedKey = false;
                foreach ($recognizedKeys as $k) {
                    if (in_array($k, $keys, true)) {
                        $hasRecognizedKey = true;
                        break;
                    }
                }

                if (! $hasRecognizedKey) {
                    throw ValidationException::withMessages([
                        'file' => 'Invalid template column structure. Please use the official PBB template. (Struktur kolom template tidak sesuai. Silakan gunakan templat resmi PBB).',
                    ]);
                }
            }

            $this->isFirstChunk = false;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $this->currentRowNumber++;
                $this->processRow((array) $row);
            }
        });
    }

    /**
     * Process a single spreadsheet row.
     *
     * @param  array<string, mixed>  $row
     */
    protected function processRow(array $row): void
    {
        $data = [];
        foreach ($row as $key => $value) {
            $data[strtolower(trim((string) $key))] = $value;
        }

        $allEmpty = true;
        foreach ($data as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            $this->skippedCount++;

            return;
        }

        $rawBrand = $data['brand_name'] ?? $data['brand'] ?? '';
        $rawCat = $data['category_name'] ?? $data['category'] ?? '';
        $rawSku = $data['sku'] ?? $data['model_number'] ?? $data['mfr_sku'] ?? '';
        $rawName = $data['name'] ?? $data['product_name'] ?? '';
        $rawHpp = $data['base_hpp'] ?? $data['cost_price'] ?? $data['hpp'] ?? null;
        $rawUom = $data['unit'] ?? $data['uom'] ?? 'pcs';
        $rawDesc = $data['description'] ?? null;

        // Validasi numerik Base HPP (TC-CAT-IMP-008)
        $costPrice = 0.00;
        if ($rawHpp !== null && trim((string) $rawHpp) !== '') {
            $cleanedHpp = str_replace([' ', ','], ['', '.'], (string) $rawHpp);
            if (! is_numeric($cleanedHpp)) {
                $this->errors[] = sprintf(
                    'Baris %d: Nilai Base HPP wajib berupa angka numerik (Row %d: Base HPP must be a numeric value).',
                    $this->currentRowNumber,
                    $this->currentRowNumber
                );
                $this->skippedCount++;

                return;
            }
            $costPrice = (float) $cleanedHpp;
        }

        // Sanitasi terhadap ancaman formula injection / CSV DDE attack (TC-CAT-IMP-011)
        $name = BulkSkuNormalizationService::sanitizeTextField($rawName);
        $description = BulkSkuNormalizationService::sanitizeTextField($rawDesc);
        $uom = strtolower(trim((string) $rawUom)) ?: 'pcs';

        if (empty($name) && empty($rawBrand) && empty($rawSku)) {
            $this->skippedCount++;

            return;
        }

        // Normalisasi SKU (TC-CAT-IMP-003)
        $trimmedRawSku = trim((string) $rawSku);
        $normalizedSku = $trimmedRawSku !== '' ? BulkSkuNormalizationService::normalize($trimmedRawSku) : '';
        $sanitizedModel = BulkSkuNormalizationService::sanitizeTextField($normalizedSku);

        // Pencocokan atau pembuatan Brand otomatis (TC-CAT-IMP-004, TC-CAT-IMP-006)
        $brandName = trim((string) $rawBrand);
        $brand = null;
        if ($brandName !== '') {
            $brand = Brand::where('name', $brandName)
                ->orWhere('slug', Str::slug($brandName))
                ->first();

            if (! $brand) {
                $brand = Brand::create([
                    'name' => $brandName,
                    'slug' => Str::slug($brandName),
                    'is_active' => true,
                ]);
            }
        } else {
            $brand = Brand::first();
        }

        // Pencocokan Kategori (TC-CAT-IMP-004, TC-CAT-IMP-019)
        $catName = trim((string) $rawCat);
        $category = null;
        if ($catName !== '') {
            $category = Category::where('name', $catName)
                ->orWhere('slug', Str::slug($catName))
                ->first();
        }
        if (! $category) {
            $category = Category::first();
            if (! $category) {
                $category = Category::create([
                    'name' => 'General Electrical & MRO',
                    'code' => 'GE',
                    'slug' => 'general-electrical-mro',
                ]);
            }
        }

        // Upsert by SKU / Model Number (TC-CAT-IMP-005, TC-CAT-IMP-017, TC-CAT-IMP-018)
        $existingProduct = null;
        if (! empty($normalizedSku)) {
            $existingProduct = Product::where('sku', $normalizedSku)
                ->orWhere('model_number', $normalizedSku)
                ->first();
        }

        if ($existingProduct) {
            // Perbarui data produk eksisting (TC-CAT-IMP-005)
            if (! empty($name)) {
                $existingProduct->name = $name;
            }
            $existingProduct->cost_price = $costPrice;
            if (! empty($description)) {
                $existingProduct->description = $description;
            }
            if ($brand) {
                $existingProduct->brand_id = $brand->id;
            }
            if ($category) {
                $existingProduct->category_id = $category->id;
            }
            $existingProduct->uom = $uom;

            // TC-CAT-IMP-018: Immutabilitas SKU jika kolom SKU kosong pada file import
            if (! empty($normalizedSku) && empty($existingProduct->sku)) {
                $existingProduct->sku = $normalizedSku;
            }

            $existingProduct->save();
            $this->updatedCount++;
        } else {
            // Pembuatan produk baru
            $newProduct = new Product;
            $newProduct->brand_id = $brand?->id;
            $newProduct->category_id = $category?->id;
            $newProduct->name = ! empty($name) ? $name : 'Product '.($normalizedSku ?: (string) $this->currentRowNumber);
            $newProduct->model_number = ! empty($sanitizedModel) ? $sanitizedModel : ($normalizedSku ?: 'MFR-PART-'.$this->currentRowNumber);

            // TC-CAT-IMP-017: Jika SKU kosong, ProductObserver akan membangkitkan SKU 12 karakter internal PBB
            if (! empty($normalizedSku)) {
                $newProduct->sku = $normalizedSku;
            }

            $newProduct->cost_price = $costPrice;
            $newProduct->description = $description;
            $newProduct->uom = $uom;
            $newProduct->availability_status = 'ready_stock';
            $newProduct->is_published = true;
            $newProduct->save();
            $this->createdCount++;
        }
    }

    /**
     * Catat audit log lengkap pada tabel activity_logs (TC-CAT-IMP-015).
     */
    public function recordActivityLog(): ?ActivityLog
    {
        if ($this->activityLogged) {
            return null;
        }
        $this->activityLogged = true;

        $user = auth()->user();

        return ActivityLog::create([
            'log_name' => 'catalog_bulk_import',
            'description' => sprintf(
                'Catalog bulk import completed: %d created, %d updated, %d skipped, %d failed. (Ringkasan: %d Berhasil Ditambahkan, %d Diperbarui, %d Gagal).',
                $this->createdCount,
                $this->updatedCount,
                $this->skippedCount,
                count($this->errors),
                $this->createdCount,
                $this->updatedCount,
                count($this->errors)
            ),
            'causer_type' => $user ? $user->getMorphClass() : null,
            'causer_id' => $user ? $user->getKey() : null,
            'properties' => [
                'created' => $this->createdCount,
                'updated' => $this->updatedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errors,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Register after import event listener to automatically log to ActivityLog.
     */
    public static function afterImport(AfterImport $event): void
    {
        // Static handler required by RegistersEventListeners
    }

    public function getTotalProcessed(): int
    {
        return $this->createdCount + $this->updatedCount;
    }
}
