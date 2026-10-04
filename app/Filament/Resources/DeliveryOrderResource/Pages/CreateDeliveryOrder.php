<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\DTOs\DeliveryOrderFulfillmentDTO;
use App\Filament\Resources\DeliveryOrderResource;
use App\Models\SalesOrder;
use App\Services\DeliveryOrderFulfillmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($soId = request()->query('sales_order_id')) {
            $salesOrder = SalesOrder::with(['quotation.items.product', 'quotation.inquiry'])->find($soId);
            if ($salesOrder) {
                $service = app(DeliveryOrderFulfillmentService::class);
                $remaining = $service->getRemainingQuantities($salesOrder);

                $items = [];
                foreach ($remaining as $r) {
                    if ($r['remaining_qty'] > 0) {
                        $items[] = [
                            'quotation_item_id' => $r['quotation_item_id'],
                            'product_id' => $r['product_id'],
                            'item_name' => $r['item_name'],
                            'model_number' => $r['model_number'],
                            'remaining_qty' => $r['remaining_qty'],
                            'quantity_shipped' => $r['remaining_qty'],
                            'uom' => $r['uom'],
                        ];
                    }
                }

                $this->form->fill([
                    'sales_order_id' => $salesOrder->id,
                    'recipient_pic_name' => $salesOrder->quotation?->customer_name ?? $salesOrder->quotation?->inquiry?->pic_name ?? 'Client PIC',
                    'recipient_phone' => $salesOrder->quotation?->customer_phone ?? $salesOrder->quotation?->inquiry?->company_phone ?? '-',
                    'shipping_address' => $salesOrder->shipping_address ?? $salesOrder->quotation?->inquiry?->delivery_address ?? 'Project Delivery Address',
                    'courier_name' => 'Armada Internal PBB',
                    'total_packages' => 1,
                    'items' => $items,
                ]);
            }
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $salesOrderId = (int) $data['sales_order_id'];
        $rawItems = $data['items'] ?? [];

        $itemsToShip = [];
        foreach ($rawItems as $item) {
            $qty = (int) ($item['quantity_shipped'] ?? 0);
            if ($qty > 0) {
                $itemsToShip[] = [
                    'quotation_item_id' => $item['quotation_item_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'item_name' => $item['item_name'] ?? null,
                    'model_number' => $item['model_number'] ?? null,
                    'quantity_shipped' => $qty,
                    'uom' => $item['uom'] ?? 'pcs',
                ];
            }
        }

        if (empty($itemsToShip)) {
            Notification::make()
                ->title('Validation Error')
                ->body('At least one item must have a quantity greater than zero to dispatch.')
                ->danger()
                ->send();

            $this->halt();
        }

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $salesOrderId,
            items: $itemsToShip,
            shippingAddress: $data['shipping_address'] ?? null,
            recipientPicName: $data['recipient_pic_name'] ?? null,
            recipientPhone: $data['recipient_phone'] ?? null,
            courierName: $data['courier_name'] ?? 'Armada Internal PBB',
            driverName: $data['driver_name'] ?? null,
            vehiclePlateNumber: $data['vehicle_plate_number'] ?? null,
            trackingNumber: $data['tracking_number'] ?? null,
            totalPackages: (int) ($data['total_packages'] ?? 1),
            totalWeightKg: isset($data['total_weight_kg']) && $data['total_weight_kg'] !== '' ? (float) $data['total_weight_kg'] : null,
        );

        $service = app(DeliveryOrderFulfillmentService::class);

        return $service->createFulfillment($dto, auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
