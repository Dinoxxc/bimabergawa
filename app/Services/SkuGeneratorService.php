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
     * Membangkitkan internal SKU resmi PBB 12 karakter untuk model Product:
     * Format: [TIPE(1)][DIVISI(2)][BRAND(3)][URUTAN(6)]
     * Contoh: SEL001000001, IAU010000015
     */
    public static function generate(Product $product): string
    {
        $category = $product->category ?? ($product->category_id ? Category::find($product->category_id) : null);
        $brand = $product->brand ?? ($product->brand_id ? Brand::find($product->brand_id) : null);
        $type = self::resolveType($product);

        return self::generateFor($category, $brand, $type);
    }

    /**
     * Memetakan status ketersediaan produk menjadi 1 karakter tipe (S/I/P).
     */
    public static function resolveType(?Product $product): string
    {
        if ($product === null) {
            return 'S';
        }

        return match ($product->availability_status) {
            'indent_principal' => 'I',
            'project' => 'P',
            default => 'S',
        };
    }

    /**
     * Membangkitkan internal SKU resmi PBB 12 karakter berdasarkan Category, Brand, dan Tipe:
     */
    public static function generateFor(?Category $category = null, ?Brand $brand = null, string $type = 'S'): string
    {
        $typeChar = strtoupper(substr(trim($type), 0, 1));
        if (! in_array($typeChar, ['S', 'I', 'P'], true)) {
            $typeChar = 'S';
        }

        // Resolusi kode divisi 2 huruf
        $rawCategoryCode = $category?->getEffectiveCode() ?? 'GE';
        $cleanCategory = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $rawCategoryCode));
        if (strlen($cleanCategory) < 2) {
            $cleanCategory = str_pad($cleanCategory, 2, 'E');
        }
        $categoryCode = substr($cleanCategory, 0, 2);

        // Resolusi kode brand 3 angka
        $rawBrandCode = $brand?->getEffectiveCode() ?? '000';
        $cleanBrand = (string) preg_replace('/[^0-9]/', '', $rawBrandCode);
        if ($cleanBrand === '') {
            $brandCode = '000';
        } else {
            $brandCode = sprintf('%03d', (int) $cleanBrand);
            if (strlen($brandCode) > 3) {
                $brandCode = substr($brandCode, -3);
            }
        }

        $prefix = sprintf('%s%s%s', $typeChar, $categoryCode, $brandCode);

        return DB::transaction(function () use ($prefix) {
            // Cari SKU terbaru dengan awalan yang sama (6 karakter prefix), urutkan numerik dari digit ke-7
            $latestSku = Product::withTrashed()
                ->where('sku', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(sku, 7) AS UNSIGNED) DESC')
                ->orderByDesc('sku')
                ->value('sku');

            $nextSequence = 1;
            if ($latestSku !== null && strlen($latestSku) >= 12) {
                $rawSeq = substr($latestSku, 6);
                if (is_numeric($rawSeq)) {
                    $nextSequence = ((int) $rawSeq) + 1;
                }
            }

            // Loop deterministik memastikan tidak ada benturan indeks unik MySQL
            $candidateSku = sprintf('%s%06d', $prefix, $nextSequence);
            while (Product::withTrashed()->where('sku', $candidateSku)->exists()) {
                $nextSequence++;
                $candidateSku = sprintf('%s%06d', $prefix, $nextSequence);
            }

            return $candidateSku;
        }, 3);
    }
}
