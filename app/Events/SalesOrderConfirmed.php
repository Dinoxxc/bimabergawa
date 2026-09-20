<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $salesOrderId,
        public readonly string $orderNumber,
        public readonly float $grandTotal,
    ) {}
}
