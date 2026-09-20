<?php

declare(strict_types=1);

namespace App\Services;

class QuotationCalculationService extends PricingAndMarginCalculatorService
{
    /**
     * Alias method untuk evaluasi margin HPP satu baris produk (TC-ERP-QUO-03).
     */
    public static function calculateHppMargin(
        float $unitPrice,
        float $costPrice,
        int|float $quantity = 1,
        float $discountPercent = 0.0,
        float $discountAmount = 0.0
    ): array {
        return self::calculateItem($quantity, $unitPrice, $costPrice, $discountPercent, $discountAmount);
    }

    /**
     * Alias method untuk kalkulasi akumulasi total penawaran harga.
     */
    public static function calculateTotals(
        array $items,
        float $discountAmount = 0.0,
        float $commissionRate = 0.0,
        ?float $vatRate = null,
        float $exchangeRate = 1.0,
        string $currency = 'IDR',
        ?int $quotationId = null,
        bool $strict = false
    ): array {
        return self::calculate(
            items: $items,
            discountAmount: $discountAmount,
            commissionRate: $commissionRate,
            vatRate: $vatRate,
            exchangeRate: $exchangeRate,
            currency: $currency,
            quotationId: $quotationId,
            strict: $strict
        );
    }
}
