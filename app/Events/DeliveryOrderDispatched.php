<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryOrderDispatched
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $deliveryOrderId,
        public readonly string $doNumber,
        public readonly string $shippingMethod,
    ) {}
}
