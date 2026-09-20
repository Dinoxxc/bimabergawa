<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SkuGeneratorService
{
    /**
     * Membangkitkan internal SKU resmi PBB untuk model Product:
     * Format: PBB-[KODE_DIVISI]-[KODE_BRAND]-[SEQUENCE_4_DIGIT]
     * Contoh: PBB-ELE-SCH-0001, PBB-MEC-EBR-0015
     */
    public static function generate(Product $product): string
    {
        $category = $product->category ?? ($product->category_id ? Category::find($product->category_id) : null);
        $brand = $product->brand ?? ($product->brand_id ? Brand::find($product->brand_id) : null);

        return self::generateFor($category, $brand);
    }

    /**
     * Membangkitkan internal SKU resmi PBB berdasarkan objek Category dan Brand:
     */
    public static function generateFor(?Category $category = null, ?Brand $brand = null): string
    {
        $categoryCode = $category?->getEffectiveCode() ?? 'GEN';
        $brandCode = $brand?->getEffectiveCode() ?? 'GEN';

        // Sanitasi karakter alfanumerik huruf besar
        $categoryCode = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $categoryCode));
        $brandCode = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $brandCode));

        if ($categoryCode === '') {
            $categoryCode = 'GEN';
        }
        if ($brandCode === '') {
            $brandCode = 'GEN';
        }

        $prefix = sprintf('PBB-%s-%s-', $categoryCode, $brandCode);

        return DB::transaction(function () use ($prefix) {
            // Cari SKU terbaru dengan awalan yang sama, urutkan numerik terlebih dahulu
            $latestSku = Product::withTrashed()
                ->where('sku', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING_INDEX(sku, '-', -1) AS UNSIGNED) DESC")
                ->orderByDesc('sku')
                ->value('sku');

            $nextSequence = 1;
            if ($latestSku !== null && preg_match('/-(\d+)$/', $latestSku, $matches)) {
                $nextSequence = ((int) $matches[1]) + 1;
            }

            // Loop deterministik memastikan tidak ada benturan indeks unik MySQL
            $candidateSku = sprintf('%s%04d', $prefix, $nextSequence);
            while (Product::withTrashed()->where('sku', $candidateSku)->exists()) {
                $nextSequence++;
                $candidateSku = sprintf('%s%04d', $prefix, $nextSequence);
            }

            return $candidateSku;
        }, 3);
    }
}
