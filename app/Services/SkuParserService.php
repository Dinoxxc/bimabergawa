<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ParseSkuRequestDTO;

class SkuParserService
{
    /**
     * Mengurai teks input mentah (dari textarea Quick Paste atau clipboard Excel)
     * menjadi daftar array terstruktur berisi SKU, kuantitas, dan catatan teknis opsional.
     *
     * Format yang didukung:
     * 1. Pemisah koma/titik-koma/pipa: "FSHA 65-50-200, 2, Flange ANSI 150"
     * 2. Pemisah tab/spasi standar: "SCH-MCB-16A    100"
     * 3. Part number saja (kuantitas default = 1): "ABB-ISOL-32A"
     * 4. Awalan kuantitas: "2x FSHA 65-50-200" atau "10 pcs E2E-X7D1-N"
     *
     * (Rujukan: TC-PUB-RFQ-006, TC-PUB-RFQ-007, SDD-05 Bagian 3).
     */
    public static function parseRawText(string $rawText, string $delimiter = "\n"): array
    {
        $lines = preg_split('/\\r?\\n|'.preg_quote($delimiter, '/').'/', $rawText);
        if ($lines === false || empty($lines)) {
            return [];
        }

        $parsed = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Lewati baris kosong atau baris komentar/header
            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#') || str_starts_with($trimmedLine, '//')) {
                continue;
            }

            $parsedItem = self::parseLine($trimmedLine);
            if ($parsedItem !== null) {
                $parsed[] = $parsedItem;
            }
        }

        return $parsed;
    }

    /**
     * Penguraian satu baris teks menjadi komponen SKU, quantity, dan unit note.
     */
    public static function parseLine(string $line): ?array
    {
        $cleanLine = trim($line);
        if ($cleanLine === '') {
            return null;
        }

        $leadingQty = null;
        $workingLine = $cleanLine;

        // A. Periksa apakah baris diawali kuantitas (contoh: "2x FSHA 65-50-200" atau "10 pcs E2E-X7D1-N")
        if (preg_match('/^(\d+)\s*(?:x|pcs|unit|set|buah)\s*(.+)$/i', $workingLine, $mLeading)) {
            $leadingQty = max(1, (int) $mLeading[1]);
            $workingLine = trim($mLeading[2]);
        } elseif (preg_match('/^(\d+)\s+([A-Za-z][A-Za-z0-9\-\/\.#\s]*)$/i', $workingLine, $mPureQty)) {
            // Jika diawali angka murni dan spasi sebelum nama SKU (contoh: "5 SCH-MCB-16A")
            $leadingQty = max(1, (int) $mPureQty[1]);
            $workingLine = trim($mPureQty[2]);
        }

        $sku = '';
        $qty = $leadingQty ?? 1;
        $note = null;

        // B. Periksa apakah baris memiliki pembatas koma (,), titik koma (;), atau pipa (|)
        if (preg_match('/[,;|]/', $workingLine)) {
            $parts = array_values(array_filter(
                array_map('trim', preg_split('/[,;|]/', $workingLine)),
                fn ($p) => $p !== ''
            ));

            if (! empty($parts)) {
                $sku = $parts[0];

                // Jika kuantitas belum didapat dari awalan:
                if ($leadingQty === null && isset($parts[1])) {
                    if (preg_match('/^(\d+)\s*(?:x|pcs|unit|set|buah)?$/i', $parts[1], $mQty)) {
                        $qty = max(1, (int) $mQty[1]);
                        if (isset($parts[2])) {
                            $note = implode(', ', array_slice($parts, 2));
                        }
                    } else {
                        // Bagian kedua bukan kuantitas angka murni, anggap sebagai catatan
                        $note = implode(', ', array_slice($parts, 1));
                    }
                } elseif (isset($parts[1])) {
                    // Kuantitas sudah didapat dari awalan baris, sisa segmen adalah catatan spesifikasi
                    $note = implode(', ', array_slice($parts, 1));
                }
            }
        }
        // C. Jika tidak ada pemisah koma/titik-koma/pipa:
        else {
            // Jika kuantitas belum didapat dari awalan, cari pemisah kuantitas di akhir (contoh: "SCH-MCB-16A 100", "SCH-MCB-16A - 100", "SCH-MCB-16A: 100")
            if ($leadingQty === null && preg_match('/^(.*?)(?:\s+-\s+|\s*:\s*|\s{2,}|\t|\s+)(\d+)\s*(?:pcs|unit|set|buah|x)?$/i', $workingLine, $mTrailing)) {
                $candidateSku = trim($mTrailing[1]);
                if ($candidateSku !== '') {
                    $sku = $candidateSku;
                    $qty = max(1, (int) $mTrailing[2]);
                } else {
                    $sku = $workingLine;
                }
            } else {
                $sku = $workingLine;
            }
        }

        $sku = trim($sku);
        if ($sku === '') {
            return null;
        }

        return [
            'raw_line' => $cleanLine,
            'sku' => $sku,
            'normalized_sku' => BulkSkuNormalizationService::normalize($sku),
            'quantity' => max(1, $qty),
            'unit_note' => $note !== null && $note !== '' ? $note : null,
            'special_requirements' => $note !== null && $note !== '' ? $note : null,
        ];
    }

    /**
     * Integrasi parser dengan DTO ParseSkuRequestDTO.
     */
    public static function parseFromDto(ParseSkuRequestDTO $dto): array
    {
        return self::parseRawText($dto->rawContent, $dto->delimiter);
    }

    /**
     * Integrasi parser and matcher dengan DTO ParseSkuRequestDTO.
     */
    public static function parseAndMatchFromDto(ParseSkuRequestDTO $dto, ?int $brandId = null): array
    {
        return self::parseAndMatch($dto->rawContent, $dto->delimiter, $brandId);
    }

    /**
     * Mengurai teks mentah sekaligus mencocokkan setiap item ke master produk katalog
     * menggunakan BulkSkuNormalizationService, mengelompokkannya ke matched dan unmatched.
     */
    public static function parseAndMatch(string $rawText, string $delimiter = "\n", ?int $brandId = null): array
    {
        $items = self::parseRawText($rawText, $delimiter);

        $matched = [];
        $unmatched = [];

        foreach ($items as $item) {
            $product = BulkSkuNormalizationService::matchSku($item['sku'], $brandId);

            // Fallback: Jika pemisahan awal tidak menemukan SKU namun baris mentah cocok persis dengan SKU di katalog
            // (misal SKU bernomor model "3M 6200" yang awalnya terpecah menjadi "3M" dan kuantitas "6200")
            if ($product === null && $item['quantity'] > 1) {
                $wholeLineProduct = BulkSkuNormalizationService::matchSku($item['raw_line'], $brandId);
                if ($wholeLineProduct !== null) {
                    $product = $wholeLineProduct;
                    $item['sku'] = $item['raw_line'];
                    $item['normalized_sku'] = BulkSkuNormalizationService::normalize($item['raw_line']);
                    $item['quantity'] = 1;
                }
            }

            if ($product !== null) {
                $matchType = ($product->model_number === $item['sku'] || $product->sku === $item['sku']) ? 'exact' : 'tolerant';

                $matched[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'model_number' => $product->model_number,
                    'name' => $product->name,
                    'sku' => $item['sku'],
                    'normalized_sku' => $item['normalized_sku'],
                    'requested_qty' => $item['quantity'],
                    'unit_note' => $item['unit_note'],
                    'special_requirements' => $item['special_requirements'],
                    'match_type' => $matchType,
                ];
            } else {
                $unmatched[] = [
                    'raw_line' => $item['raw_line'],
                    'sku' => $item['sku'],
                    'normalized_sku' => $item['normalized_sku'],
                    'requested_qty' => $item['quantity'],
                    'unit_note' => $item['unit_note'],
                    'reason' => 'SKU tidak ditemukan di katalog',
                ];
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'total_lines' => count($items),
            'matched_count' => count($matched),
            'unmatched_count' => count($unmatched),
            'items' => $items,
        ];
    }
}
