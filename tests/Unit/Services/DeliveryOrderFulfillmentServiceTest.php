<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\DeliveryOrderFulfillmentDTO;
use App\Events\DeliveryOrderDispatched;
use App\Exceptions\InsufficientFulfillmentQuantityException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Services\DeliveryOrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class DeliveryOrderFulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryOrderFulfillmentService $service;

    private SalesOrder $salesOrder;

    private QuotationItem $item1;

    private QuotationItem $item2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DeliveryOrderFulfillmentService;

        $brand = Brand::factory()->create(['name' => 'Schneider Electric', 'code' => 'SCH']);
        $category = Category::factory()->create(['name' => 'Electrical', 'code' => 'ELE']);

        $product1 = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Schneider MCB 16A Acti9',
            'model_number' => 'SCH-MCB-16A',
        ]);

        $product2 = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Schneider Kontaktor LC1D09',
            'model_number' => 'SCH-LC1D09',
        ]);

        $quotation = Quotation::factory()->create([
            'status' => 'approved',
            'dpp' => 10000000.00,
            'vat_rate_applied' => 11.00,
            'grand_total' => 11100000.00,
        ]);

        $this->item1 = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product1->id,
            'item_name' => 'Schneider MCB 16A Acti9',
            'model_number' => 'SCH-MCB-16A',
            'quantity' => 100,
            'uom' => 'pcs',
            'unit_price' => 50000.00,
            'total_price' => 5000000.00,
        ]);

        $this->item2 = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product2->id,
            'item_name' => 'Schneider Kontaktor LC1D09',
            'model_number' => 'SCH-LC1D09',
            'quantity' => 50,
            'uom' => 'pcs',
            'unit_price' => 100000.00,
            'total_price' => 5000000.00,
        ]);

        $this->salesOrder = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'order_number' => 'PBB/SOR/2026/09/0001',
            'status' => 'processing',
            'total_amount' => 11100000.00,
        ]);
    }

    public function test_get_remaining_quantities_calculates_correctly_on_fresh_sales_order(): void
    {
        $remaining = $this->service->getRemainingQuantities($this->salesOrder);

        $this->assertCount(2, $remaining);

        $this->assertSame(100, $remaining[0]['ordered_qty']);
        $this->assertSame(0, $remaining[0]['shipped_qty']);
        $this->assertSame(100, $remaining[0]['remaining_qty']);
        $this->assertFalse($remaining[0]['is_fulfilled']);

        $this->assertSame(50, $remaining[1]['ordered_qty']);
        $this->assertSame(0, $remaining[1]['shipped_qty']);
        $this->assertSame(50, $remaining[1]['remaining_qty']);
        $this->assertFalse($remaining[1]['is_fulfilled']);
    }

    public function test_create_full_delivery_order_completes_sales_order_and_dispatches_event(): void
    {
        Event::fake([DeliveryOrderDispatched::class]);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 100],
                ['quotation_item_id' => $this->item2->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal PBB',
            driverName: 'Budi Santoso',
            vehiclePlateNumber: 'B 9123 PBB'
        );

        $do = $this->service->createFulfillment($dto);

        $this->assertInstanceOf(DeliveryOrder::class, $do);
        $this->assertSame('shipped', $do->status);
        $this->assertStringContainsString('PBB/DOR/', $do->delivery_number);
        $this->assertCount(2, $do->items);

        // Sales order harus berstatus completed karena 100% barang terkirim
        $this->salesOrder->refresh();
        $this->assertSame('completed', $this->salesOrder->status);

        $remaining = $this->service->getRemainingQuantities($this->salesOrder);
        $this->assertSame(0, $remaining[0]['remaining_qty']);
        $this->assertTrue($remaining[0]['is_fulfilled']);
        $this->assertSame(0, $remaining[1]['remaining_qty']);
        $this->assertTrue($remaining[1]['is_fulfilled']);

        Event::assertDispatched(DeliveryOrderDispatched::class, function ($e) use ($do) {
            return $e->deliveryOrderId === $do->id && $e->doNumber === $do->delivery_number;
        });
    }

    public function test_create_partial_delivery_order_updates_status_to_partially_fulfilled(): void
    {
        // Batch 1: Kirim 40 pcs item1 saja
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 40],
            ],
            shippingMethod: 'Kurir Ekspedisi',
            courierName: 'Baraka Sarana Tama',
            trackingNumber: 'BRK-99123'
        );

        $do1 = $this->service->createFulfillment($dto);

        $this->assertSame('PBB/DOR/2026/09/0001/01', $do1->delivery_number);

        $this->salesOrder->refresh();
        $this->assertSame('partially_fulfilled', $this->salesOrder->status);

        $remaining = $this->service->getRemainingQuantities($this->salesOrder);
        $this->assertSame(40, $remaining[0]['shipped_qty']);
        $this->assertSame(60, $remaining[0]['remaining_qty']);
        $this->assertFalse($remaining[0]['is_fulfilled']);

        $this->assertSame(0, $remaining[1]['shipped_qty']);
        $this->assertSame(50, $remaining[1]['remaining_qty']);
    }

    public function test_create_second_partial_delivery_order_closes_sales_order(): void
    {
        // Batch 1: 40 pcs item1
        $dto1 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 40],
            ],
            shippingMethod: 'Armada Internal'
        );
        $do1 = $this->service->createFulfillment($dto1);
        $this->assertSame('PBB/DOR/2026/09/0001/01', $do1->delivery_number);

        // Batch 2: Sisa 60 pcs item1 + 50 pcs item2
        $dto2 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 60],
                ['quotation_item_id' => $this->item2->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal'
        );
        $do2 = $this->service->createFulfillment($dto2);
        $this->assertSame('PBB/DOR/2026/09/0001/02', $do2->delivery_number);

        $this->salesOrder->refresh();
        $this->assertSame('completed', $this->salesOrder->status);
    }

    public function test_validation_rejects_quantity_exceeding_remaining_order(): void
    {
        $this->expectException(InsufficientFulfillmentQuantityException::class);
        $this->expectExceptionMessage('tidak boleh melebihi sisa pesanan');

        // Item 1 memiliki sisa 100 pcs, coba kirim 105 pcs
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 105],
            ],
            shippingMethod: 'Armada Internal'
        );

        $this->service->createFulfillment($dto);
    }

    public function test_validation_rejects_delivery_for_completed_sales_order(): void
    {
        $this->salesOrder->update(['status' => 'completed']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telah terkirim lengkap');

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 10],
            ],
            shippingMethod: 'Armada Internal'
        );

        $this->service->createFulfillment($dto);
    }

    public function test_confirm_delivery_updates_status_and_pod_path(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal'
        );

        $do = $this->service->createFulfillment($dto);
        $this->assertSame('shipped', $do->status);

        $confirmedDo = $this->service->confirmDelivery($do, 'pod/pod-signed-001.pdf');

        $this->assertSame('delivered', $confirmedDo->status);
        $this->assertSame('pod/pod-signed-001.pdf', $confirmedDo->proof_of_delivery_path);
    }

    public function test_cancel_delivery_order_restores_remaining_quantity(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal'
        );

        $do = $this->service->createFulfillment($dto);
        $this->salesOrder->refresh();
        $this->assertSame('partially_fulfilled', $this->salesOrder->status);

        // Batalkan DO
        $this->service->cancelDeliveryOrder($do, 'Pengiriman gagal, proyek tutup');

        $this->salesOrder->refresh();
        $this->assertSame('processing', $this->salesOrder->status);

        $remaining = $this->service->getRemainingQuantities($this->salesOrder);
        $this->assertSame(100, $remaining[0]['remaining_qty']);
        $this->assertSame(0, $remaining[0]['shipped_qty']);
    }
}
