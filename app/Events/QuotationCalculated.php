<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotationCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $quotationId,
        public readonly float $subtotal,
        public readonly float $marginPercent,
        public readonly float $vatAmount,
    ) {}
}
