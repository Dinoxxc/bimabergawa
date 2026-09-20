<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class DeliveryOrderFulfillmentDTO
{
    /**
     * @param  array<int, array{sales_order_item_id: int, quantity_delivered: int}>  $items
     */
    public function __construct(
        public int $salesOrderId,
        public array $items,
        public string $shippingMethod,
        public ?string $driverNotes = null,
    ) {}
}
