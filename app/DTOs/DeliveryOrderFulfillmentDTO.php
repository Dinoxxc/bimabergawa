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
        public string $shippingMethod = 'Internal Fleet',
        public ?string $driverNotes = null,
        public ?string $driverName = null,
        public ?string $vehiclePlateNumber = null,
        public ?string $courierName = null,
        public ?string $trackingNumber = null,
        public ?string $recipientPicName = null,
        public ?string $recipientPhone = null,
        public ?string $shippingAddress = null,
        public ?int $totalPackages = null,
        public ?float $totalWeightKg = null,
    ) {}
}
