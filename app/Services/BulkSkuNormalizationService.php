<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class BulkSkuNormalizationService
{
    /**
     * Standarisasi format kode SKU / Part Number:
     * - Huruf kapital (uppercase)
     * - Trimming spasi di awal/akhir
     * - Mengubah underscore (_) menjadi tanda hubung (-)
     * - Menghilangkan spasi dan tanda hubung berulang
     *
     * Contoh: "  sch-mcb_16a  " -> "SCH-MCB-16A"
     */
    public static function normalize(string $rawSku): string
    {
        $trimmed = trim($rawSku);
        if ($trimmed === '') {
            return '';
        }

        // 1. Ubah underscore menjadi strip
        $sku = str_replace('_', '-', $trimmed);

        // 2. Satukan spasi berlebih menjadi 1 spasi
        $sku = (string) preg_replace('/\s+/', ' ', $sku);

        // 3. Hapus spasi di sekitar tanda hubung (contoh: "SCH - MCB - 16A" -> "SCH-MCB-16A")
        $sku = (string) preg_replace('/\s*-\s*/', '-', $sku);

        // 4. Satukan tanda hubung berlebih menjadi 1 tanda hubung
        $sku = (string) preg_replace('/-+/', '-', $sku);

        // 5. Konversi ke huruf besar
        return strtoupper(trim($sku));
    }

    /**
     * Bersihkan string SKU hanya menyisakan karakter alfanumerik huruf besar
     * untuk keperluan pencocokan toleran di database (mengabaikan spasi, strip, garis miring, titik, pagar).
     *
     * Contoh: "FSHA 65-50-200/5.5" -> "FSHA655020055"
     */
    public static function cleanForComparison(string $rawSku): string
    {
        $alphanumeric = (string) preg_replace('/[^A-Za-z0-9]/', '', $rawSku);

        return strtoupper($alphanumeric);
    }

    /**
     * Perlindungan terhadap serangan Formula Injection (CSV / Excel Injection).
     * Mendeteksi dan menetralkan karakter awal formula (=, +, -, @, \t, \r)
     * dengan menyematkan tanda kutip tunggal pengaman (') agar aman saat diekspor.
     * (Rujukan: TC-CAT-IMP-011).
     */
    public static function sanitizeTextField(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }

        // Jika sudah dimulai dengan tanda kutip tunggal pengaman, abaikan
        if ($trimmed[0] === "'") {
            return $trimmed;
        }

        $formulaTriggers = ['=', '+', '@', "\t", "\r"];
        $firstChar = $trimmed[0];

        // Trigger jika dimulai dengan formula trigger, atau '-' diikuti karakter non-angka
        if (in_array($firstChar, $formulaTriggers, true) || ($firstChar === '-' && isset($trimmed[1]) && ! is_numeric($trimmed[1]))) {
            return "'".$trimmed;
        }

        return $trimmed;
    }

    /**
     * Mencocokkan input part number pembeli ke model Product di katalog dengan urutan presisi:
     * 1. Exact Match pada model_number
     * 2. Case-Insensitive Match pada model_number
     * 3. Normalized SKU Match (normalize())
     * 4. Tolerant Alphanumeric Match (mengabaikan spasi, strip, slash, dot, underscore)
     * 5. Base Part Match (sebelum garis miring spesifikasi, misal FSHA 65-50-200/5.5 -> FSHA 65-50-200)
     * 6. Product Name Match (persis atau case-insensitive)
     *
     * Mendukung pemfilteran opsional berdasarkan brand_id ($brandId).
     * (Rujukan: UT-04, SDD-03 Bagian 6).
     */
    public static function matchSku(string $rawInput, ?int $brandId = null): ?Product
    {
        $cleanInput = trim((string) preg_replace('/\s+/', ' ', $rawInput));
        if ($cleanInput === '') {
            return null;
        }

        // 1. Exact match langsung pada model_number
        $product = Product::where('model_number', $cleanInput)
            ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
            ->first();
        if ($product !== null) {
            return $product;
        }

        // 2. Exact match langsung pada internal SKU PBB (PBB-DIV-BRD-XXXX)
        $product = Product::where(function ($q) use ($cleanInput) {
            $q->where('sku', $cleanInput)
                ->orWhereRaw('LOWER(sku) = ?', [strtolower($cleanInput)]);
        })
            ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
            ->first();
        if ($product !== null) {
            return $product;
        }

        // 3. Case-insensitive match pada model_number
        $product = Product::whereRaw('LOWER(model_number) = ?', [strtolower($cleanInput)])
            ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
            ->first();
        if ($product !== null) {
            return $product;
        }

        // 4. Pencocokan bentuk normalisasi (underscore ke strip, spasi sekitar strip, uppercase)
        $normalized = self::normalize($cleanInput);
        if ($normalized !== '' && $normalized !== $cleanInput) {
            $product = Product::where(function ($q) use ($normalized) {
                $q->where('model_number', $normalized)
                    ->orWhere('sku', $normalized);
            })
                ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
                ->first();
            if ($product !== null) {
                return $product;
            }
        }

        // 4b. Pencocokan karakter alfanumerik murni (mengabaikan spasi, strip, slash, pagar, titik, underscore)
        $cleanAlphaNum = self::cleanForComparison($cleanInput);
        if ($cleanAlphaNum !== '') {
            $product = Product::where(function ($q) use ($cleanAlphaNum) {
                $q->whereRaw(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(model_number, ' ', ''), '-', ''), '/', ''), '#', ''), '.', ''), '_', '')) = ?",
                    [$cleanAlphaNum]
                )->orWhereRaw(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sku, ' ', ''), '-', ''), '/', ''), '#', ''), '.', ''), '_', '')) = ?",
                    [$cleanAlphaNum]
                );
            })
                ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
                ->first();

            if ($product !== null) {
                return $product;
            }
        }

        // 5. Pencocokan base part number sebelum slash (misal 'FSHA 65-50-200/5.5' -> 'FSHA 65-50-200')
        if (str_contains($cleanInput, '/')) {
            $basePart = trim(explode('/', $cleanInput)[0]);
            if ($basePart !== '' && $basePart !== $cleanInput) {
                $product = self::matchSku($basePart, $brandId);
                if ($product !== null) {
                    return $product;
                }
            }
        }

        // 6. Pencocokan pada nama produk (name) dengan grouping boolean aman
        return Product::where(function ($q) use ($cleanInput) {
            $q->where('name', $cleanInput)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($cleanInput)]);
        })
            ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId))
            ->first();
    }

    /**
     * Pencocokan batch untuk sejumlah part number sekaligus.
     * Mengembalikan Collection berisi mapping:
     * [
     *     'input' => string,
     *     'product' => ?Product,
     *     'is_matched' => bool,
     *     'match_type' => 'exact'|'tolerant'|'none'
     * ]
     */
    public static function matchMany(array $rawInputs, ?int $brandId = null): Collection
    {
        $results = collect();

        foreach ($rawInputs as $rawInput) {
            $rawInputString = trim((string) $rawInput);
            if ($rawInputString === '') {
                continue;
            }

            $product = self::matchSku($rawInputString, $brandId);
            $matchType = 'none';

            if ($product !== null) {
                $matchType = ($product->model_number === $rawInputString || $product->sku === $rawInputString) ? 'exact' : 'tolerant';
            }

            $results->push([
                'input' => $rawInputString,
                'product' => $product,
                'is_matched' => ($product !== null),
                'match_type' => $matchType,
            ]);
        }

        return $results;
    }
}
