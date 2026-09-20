<?php

declare(strict_types=1);

namespace App\Services;

class CommercialCalculationService extends PricingAndMarginCalculatorService
{
    /**
     * Kompatibilitas langsung dengan matriks QA-Testing-01 (UT-01, UT-02).
     */
    public static function calculateQuotationSummary(
        array $items,
        float $discountAmount = 0.0,
        ?float $vatRate = null,
        float $commissionRate = 0.0
    ): array {
        return parent::calculateQuotationSummary($items, $discountAmount, $vatRate, $commissionRate);
    }
}
