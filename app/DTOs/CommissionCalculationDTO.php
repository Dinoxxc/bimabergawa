<?php

declare(strict_types=1);

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
