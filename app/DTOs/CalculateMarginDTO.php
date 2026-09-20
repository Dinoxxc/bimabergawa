<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class CalculateMarginDTO
{
    public function __construct(
        public float $costPrice,
        public float $unitPrice,
        public int|float $quantity = 1,
        public float $discountPercent = 0.0,
        public float $vatRate = 11.0,
        public float $exchangeRate = 1.0,
        public string $currency = 'IDR',
    ) {}
}
