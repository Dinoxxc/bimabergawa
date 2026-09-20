<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CalculateMarginDTO;
use App\Events\QuotationCalculated;
use App\Exceptions\CriticalMarginException;
use InvalidArgumentException;

class PricingAndMarginCalculatorService
{
    public const DEFAULT_SAFE_MARGIN_THRESHOLD = 15.00;

    public const DEFAULT_CRITICAL_MARGIN_THRESHOLD = 8.00;

    public const DEFAULT_DIRECTOR_APPROVAL_LIMIT = 100000000.00;

    public const MAX_UNAPPROVED_DISCOUNT_PERCENT = 10.00;

    public const DEFAULT_STATIC_VAT_RATE = 11.00;

    /**
     * Hitung nilai finansial komersial penawaran harga dengan PPN dinamis multi-periode,
     * live margin HPP guard, evaluasi approval direksi, dan dukungan multi-mata uang.
     */
    public static function calculate(
        array $items,
        float $discountAmount = 0.0,
        float $commissionRate = 0.0,
        ?float $vatRate = null,
        float $exchangeRate = 1.0,
        string $currency = 'IDR',
        ?int $quotationId = null,
        bool $strict = false
    ): array {
        $subtotal = 0.0;
        $totalCost = 0.0;
        $totalItemDiscount = 0.0;
        $processedItems = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $costPrice = (float) ($item['cost_price'] ?? 0);
            $itemDiscountPercent = (float) ($item['discount_percent'] ?? 0);
            $itemDiscountAmount = (float) ($item['discount_amount'] ?? 0);

            $calculatedItem = self::calculateItem(
                $qty,
                $unitPrice,
                $costPrice,
                $itemDiscountPercent,
                $itemDiscountAmount
            );

            $subtotal += ($qty * $unitPrice);
            $totalCost += ($qty * $costPrice);
            $totalItemDiscount += $calculatedItem['discount_amount'];
            $processedItems[] = $calculatedItem;
        }

        $aggregatedDiscount = $totalItemDiscount + max(0.0, $discountAmount);
        $dpp = max(0.0, $subtotal - $aggregatedDiscount);

        // Ambil tarif PPN aktif dinamis dari SystemSettingService (mendukung 11%, 12%, 13%, dll.)
        $effectiveVatRate = $vatRate ?? SystemSettingService::getFloat(
            'commercial_tax.tax_vat_rate',
            (float) env('STATIC_VAT_RATE', self::DEFAULT_STATIC_VAT_RATE)
        );

        $taxAmount = round($dpp * ($effectiveVatRate / 100), 2);
        $grandTotal = round($dpp + $taxAmount, 2);

        // Gross Profit & Live Margin Formula
        $grossProfit = round($dpp - $totalCost, 2);
        $marginPercentage = $dpp > 0.0 ? round(($grossProfit / $dpp) * 100, 2) : 0.0;

        $marginTier = self::evaluateMarginTier($marginPercentage, $grossProfit);
        $requiresApproval = self::requiresDirectorApproval(
            $marginPercentage,
            $grandTotal,
            $aggregatedDiscount,
            $subtotal,
            $grossProfit
        );

        // Hak Komisi Mitra Agen Freelance dihitung dari DPP
        $commissionAmount = round($dpp * (max(0.0, $commissionRate) / 100), 2);

        // Hedging valas ke mata uang dasar (IDR)
        $effectiveExchangeRate = $exchangeRate > 0.0 ? $exchangeRate : 1.0;
        $grandTotalBase = round($grandTotal * $effectiveExchangeRate, 2);
        $dppBase = round($dpp * $effectiveExchangeRate, 2);

        $criticalThreshold = SystemSettingService::getFloat(
            'commercial_tax.critical_margin_threshold',
            self::DEFAULT_CRITICAL_MARGIN_THRESHOLD
        );

        // Validasi mode strict: lempar CriticalMarginException bila margin kritis / minus
        if ($strict && ($marginTier === 'critical' || $marginPercentage < $criticalThreshold || $grossProfit < 0)) {
            throw new CriticalMarginException($marginPercentage, $criticalThreshold);
        }

        // Emisi domain event jika quotation ID disertakan
        if ($quotationId !== null) {
            QuotationCalculated::dispatch($quotationId, $subtotal, $marginPercentage, $taxAmount);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'item_discount_amount' => round($totalItemDiscount, 2),
            'global_discount_amount' => round($discountAmount, 2),
            'discount_amount' => round($aggregatedDiscount, 2),
            'dpp' => round($dpp, 2),
            'vat_rate_applied' => round($effectiveVatRate, 2),
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'total_cost' => round($totalCost, 2),
            'gross_profit' => $grossProfit,
            'margin_percentage' => $marginPercentage,
            'margin_tier' => $marginTier,
            'requires_director_approval' => $requiresApproval,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'currency' => strtoupper(trim($currency)),
            'exchange_rate_applied' => $effectiveExchangeRate,
            'dpp_base' => $dppBase,
            'grand_total_base' => $grandTotalBase,
            'items' => $processedItems,
        ];
    }

    /**
     * Hitung rincian finansial satu item/baris produk.
     */
    public static function calculateItem(
        int|float $quantity,
        float $unitPrice,
        float $costPrice,
        float $discountPercent = 0.0,
        float $discountAmount = 0.0
    ): array {
        $qty = max(1, (int) $quantity);
        $itemGross = $qty * $unitPrice;
        $itemCost = $qty * $costPrice;

        // Tentukan diskon baris: prioritaskan discountAmount eksplisit, fallback ke discountPercent
        $effectiveDiscount = $discountAmount > 0.0
            ? $discountAmount
            : round($itemGross * (max(0.0, min(100.0, $discountPercent)) / 100), 2);

        $netTotal = max(0.0, $itemGross - $effectiveDiscount);
        $grossProfit = round($netTotal - $itemCost, 2);
        $marginPercentage = $netTotal > 0.0 ? round(($grossProfit / $netTotal) * 100, 2) : 0.0;
        $isBelowCost = ($unitPrice < $costPrice) || ($grossProfit < 0);
        $marginTier = self::evaluateMarginTier($marginPercentage, $grossProfit);

        return [
            'quantity' => $qty,
            'unit_price' => round($unitPrice, 2),
            'cost_price' => round($costPrice, 2),
            'subtotal' => round($itemGross, 2),
            'discount_percent' => round($discountPercent, 2),
            'discount_amount' => round($effectiveDiscount, 2),
            'net_total' => round($netTotal, 2),
            'total_cost' => round($itemCost, 2),
            'gross_profit' => $grossProfit,
            'margin_percentage' => $marginPercentage,
            'margin_tier' => $marginTier,
            'is_below_cost' => $isBelowCost,
        ];
    }

    /**
     * Hitung penawaran harga menggunakan DTO terstruktur (CalculateMarginDTO).
     */
    public static function calculateFromDto(
        CalculateMarginDTO $dto,
        ?int $quotationId = null,
        bool $strict = false
    ): array {
        $items = [
            [
                'quantity' => $dto->quantity,
                'unit_price' => $dto->unitPrice,
                'cost_price' => $dto->costPrice,
                'discount_percent' => $dto->discountPercent,
                'discount_amount' => $dto->discountAmount,
            ],
        ];

        return self::calculate(
            items: $items,
            discountAmount: 0.0,
            commissionRate: 0.0,
            vatRate: $dto->vatRate,
            exchangeRate: $dto->exchangeRate,
            currency: $dto->currency,
            quotationId: $quotationId,
            strict: $strict
        );
    }

    /**
     * Evaluasi tingkatan status margin (safe | warning | critical).
     */
    public static function evaluateMarginTier(float $marginPercentage, float $grossProfit = 0.0): string
    {
        $warningThreshold = SystemSettingService::getFloat(
            'commercial_tax.warning_margin_threshold',
            self::DEFAULT_SAFE_MARGIN_THRESHOLD
        );

        $criticalThreshold = SystemSettingService::getFloat(
            'commercial_tax.critical_margin_threshold',
            self::DEFAULT_CRITICAL_MARGIN_THRESHOLD
        );

        if ($grossProfit < 0 || $marginPercentage < $criticalThreshold) {
            return 'critical';
        }

        if ($marginPercentage < $warningThreshold) {
            return 'warning';
        }

        return 'safe';
    }

    /**
     * Tentukan apakah transaksi penawaran membutuhkan persetujuan Direktur:
     * 1. Margin laba kotor di bawah ambang batas aman (< 15.00%) atau margin minus.
     * 2. Diskon melebihi 10.00% dari subtotal.
     * 3. Nilai transaksi (grand total) mencapai atau melebihi limit otorisasi Direktur (default Rp 100 Juta).
     */
    public static function requiresDirectorApproval(
        float $marginPercentage,
        float $grandTotal = 0.0,
        float $discountAmount = 0.0,
        float $subtotal = 0.0,
        float $grossProfit = 0.0
    ): bool {
        $warningThreshold = SystemSettingService::getFloat(
            'commercial_tax.warning_margin_threshold',
            self::DEFAULT_SAFE_MARGIN_THRESHOLD
        );

        $approvalLimit = SystemSettingService::getFloat(
            'commercial_tax.director_approval_limit',
            self::DEFAULT_DIRECTOR_APPROVAL_LIMIT
        );

        // Pemicu 1: Margin rendah atau minus
        if ($grossProfit < 0 || $marginPercentage < $warningThreshold) {
            return true;
        }

        // Pemicu 2: Diskon > 10% dari subtotal
        if ($subtotal > 0.0 && ($discountAmount > ($subtotal * (self::MAX_UNAPPROVED_DISCOUNT_PERCENT / 100)))) {
            return true;
        }

        // Pemicu 3: Nilai transaksi melampaui limit approval Direktur
        if ($grandTotal >= $approvalLimit) {
            return true;
        }

        return false;
    }

    /**
     * Validasi akumulasi bobot termin pembayaran bertahap (wajib tepat 100.00%).
     * Toleransi presisi floating-point diatur < 0.01%.
     *
     * @throws InvalidArgumentException
     */
    public static function validatePaymentMilestones(array $milestones): bool
    {
        $totalPercentage = 0.0;
        foreach ($milestones as $phase) {
            $totalPercentage += (float) ($phase['percentage'] ?? 0);
        }

        if (abs($totalPercentage - 100.00) > 0.01) {
            throw new InvalidArgumentException(
                sprintf('Akumulasi total persentase termin pembayaran wajib tepat 100.00%% (Saat ini: %.2f%%).', $totalPercentage)
            );
        }

        return true;
    }

    /**
     * Metode ringkas untuk kompatibilitas matriks QA-Testing-01 (UT-01 & UT-02).
     */
    public static function calculateQuotationSummary(
        array $items,
        float $discountAmount = 0.0,
        ?float $vatRate = null,
        float $commissionRate = 0.0
    ): array {
        return self::calculate(
            items: $items,
            discountAmount: $discountAmount,
            commissionRate: $commissionRate,
            vatRate: $vatRate
        );
    }
}
