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

    public function test_fifo_remaining_quantity_calculation_prevents_double_counting_across_identical_products(): void
    {
        $product = Product::first() ?? Product::factory()->create();

        $quotation = Quotation::factory()->create(['status' => 'approved']);
        $line1 = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'item_name' => 'Schneider MCB 16A (Floor 1)',
            'quantity' => 10,
            'unit_price' => 100000,
            'total_price' => 1000000,
        ]);
        $line2 = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'item_name' => 'Schneider MCB 16A (Floor 2)',
            'quantity' => 20,
            'unit_price' => 100000,
            'total_price' => 2000000,
        ]);

        $so = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'order_number' => 'PBB/SOR/2026/09/'.rand(1000, 9999),
            'status' => 'processing',
            'total_amount' => 3330000.00,
        ]);

        // Kirim 10 unit untuk Line 1
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $so->id,
            items: [
                ['quotation_item_id' => $line1->id, 'quantity_delivered' => 10],
            ],
            shippingMethod: 'Armada Internal'
        );
        $this->service->createFulfillment($dto);

        $remaining = $this->service->getRemainingQuantities($so);

        // Line 1 harus sisa 0, Line 2 harus tetap sisa 20 (bukan berkurang jadi 10)
        $this->assertSame(0, $remaining[0]['remaining_qty']);
        $this->assertSame(10, $remaining[0]['shipped_qty']);
        $this->assertSame(20, $remaining[1]['remaining_qty']);
        $this->assertSame(0, $remaining[1]['shipped_qty']);
        $this->assertSame(20, $remaining[0]['remaining_qty'] + $remaining[1]['remaining_qty']);
    }

    public function test_validation_rejects_unapproved_sales_order_and_large_order_without_director_approval(): void
    {
        // 1. Uji penolakan SO dengan penawaran masih draft
        $this->salesOrder->quotation->update(['status' => 'draft']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Surat Jalan hanya dapat diterbitkan untuk Sales Order yang telah disetujui resmi');

        $this->service->validateShippedQuantities($this->salesOrder, [
            ['quotation_item_id' => $this->item1->id, 'quantity' => 10],
        ]);
    }

    public function test_validation_rejects_large_order_without_director_approval(): void
    {
        $this->salesOrder->update([
            'status' => 'processing',
            'total_amount' => 150000000.00,
            'is_approved_by_director' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('wajib disetujui oleh Direktur sebelum pengiriman');

        $this->service->validateShippedQuantities($this->salesOrder, [
            ['quotation_item_id' => $this->item1->id, 'quantity' => 10],
        ]);
    }

    public function test_confirm_delivery_rejects_trashed_delivery_order(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 10],
            ],
            shippingMethod: 'Armada Internal'
        );
        $do = $this->service->createFulfillment($dto);
        $do->delete();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telah dibatalkan tidak dapat dikonfirmasi');

        $this->service->confirmDelivery($do);
    }

    public function test_cancel_delivery_order_rejects_already_delivered_order(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 10],
            ],
            shippingMethod: 'Armada Internal'
        );
        $do = $this->service->createFulfillment($dto);
        $this->service->confirmDelivery($do);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telah berstatus delivered tidak dapat dibatalkan');

        $this->service->cancelDeliveryOrder($do);
    }

    public function test_validation_rejects_cumulative_quantity_exceeding_remaining_in_single_batch(): void
    {
        // Item 1 memiliki kuantitas pesanan 100
        // Kirim batch dengan 2 baris item yang merujuk item yang sama (60 pcs + 50 pcs = 110 pcs)
        $this->expectException(InsufficientFulfillmentQuantityException::class);
        $this->expectExceptionMessage('Akumulasi kuantitas pengiriman (110) untuk item');

        $this->service->validateShippedQuantities($this->salesOrder, [
            ['quotation_item_id' => $this->item1->id, 'quantity' => 60],
            ['quotation_item_id' => $this->item1->id, 'quantity' => 50],
        ]);
    }

    public function test_confirm_delivery_with_discrepancy_records_defects_and_restores_remaining_quantity_for_replacement(): void
    {
        // 1. Terbitkan DO Tahap 1 untuk seluruh 100 pcs Item 1
        $dto1 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 100],
            ],
            shippingMethod: 'Armada Internal PBB'
        );
        $do1 = $this->service->createFulfillment($dto1);

        $do1Item = $do1->items()->first();

        // 2. Di lapangan, 90 pcs diterima baik, 10 pcs rusak/cacat bodi
        $confirmedDo = $this->service->confirmDeliveryWithDiscrepancy(
            deliveryOrder: $do1,
            itemDiscrepancies: [
                [
                    'delivery_order_item_id' => $do1Item->id,
                    'quantity_received' => 90,
                    'quantity_defective' => 10,
                    'defect_notes' => 'Bodi MCB retak terbanting saat pengiriman',
                ],
            ],
            podPath: 'pod/do-001-signed.jpg',
            returnProofPath: 'returns/defect-mcb-photo.jpg',
            discrepancyNotes: '10 unit MCB dikembalikan ke supir PBB untuk diganti'
        );

        // Verifikasi status DO dan field barang cacat
        $this->assertSame('partially_delivered', $confirmedDo->status);
        $this->assertSame('pod/do-001-signed.jpg', $confirmedDo->proof_of_delivery_path);
        $this->assertSame('returns/defect-mcb-photo.jpg', $confirmedDo->return_proof_path);
        $this->assertStringContainsString('dikembalikan ke supir PBB', $confirmedDo->discrepancy_notes);

        $refreshedItem = $do1Item->fresh();
        $this->assertSame(90, $refreshedItem->quantity_received);
        $this->assertSame(10, $refreshedItem->quantity_defective);
        $this->assertSame('Bodi MCB retak terbanting saat pengiriman', $refreshedItem->defect_notes);

        // 3. Sisa pesanan pada Sales Order otomatis kembali menjadi 10 pcs (karena 10 pcs cacat belum terpenuhi)
        $remaining = $this->service->getRemainingQuantities($this->salesOrder);
        $item1Remaining = collect($remaining)->firstWhere('quotation_item_id', $this->item1->id);
        $this->assertSame(10, $item1Remaining['remaining_qty']);
        $this->assertFalse($item1Remaining['is_fulfilled']);

        $this->salesOrder->refresh();
        $this->assertSame('partially_fulfilled', $this->salesOrder->status);

        // 4. Staf logistik dapat langsung menerbitkan Surat Jalan Pengganti (DO 2) untuk 10 unit pengganti
        $dto2 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 10],
            ],
            shippingMethod: 'Armada Internal PBB'
        );
        $do2 = $this->service->createFulfillment($dto2);
        $this->assertStringEndsWith('/02', $do2->delivery_number);

        // 5. Konfirmasi serah terima DO 2 dengan 10 pcs diterima baik 100%
        $this->service->confirmDelivery($do2);

        // Sisa pesanan Item 1 sekarang 0 pcs
        $finalRemaining = $this->service->getRemainingQuantities($this->salesOrder);
        $finalItem1 = collect($finalRemaining)->firstWhere('quotation_item_id', $this->item1->id);
        $this->assertSame(0, $finalItem1['remaining_qty']);
        $this->assertTrue($finalItem1['is_fulfilled']);
    }

    public function test_confirm_delivery_with_discrepancy_rejects_sum_exceeding_shipped_quantity(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal PBB'
        );
        $do = $this->service->createFulfillment($dto);
        $doItem = $do->items()->first();

        // Shipped = 50, coba klaim received (45) + defective (10) = 55 (> 50)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('melebihi kuantitas awal yang dikirim');

        $this->service->confirmDeliveryWithDiscrepancy(
            deliveryOrder: $do,
            itemDiscrepancies: [
                [
                    'delivery_order_item_id' => $doItem->id,
                    'quantity_received' => 45,
                    'quantity_defective' => 10,
                ],
            ]
        );
    }

    public function test_confirm_delivery_with_discrepancy_handles_missing_goods_short_shipment_without_defect(): void
    {
        // TC-ERP-DO-018: Kirim 50 pcs Item 1, kurir kehilangan 5 pcs di perjalanan (45 pcs baik, 0 cacat)
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Ekspedisi Baraka'
        );
        $do = $this->service->createFulfillment($dto);
        $doItem = $do->items()->first();

        $confirmedDo = $this->service->confirmDeliveryWithDiscrepancy(
            deliveryOrder: $do,
            itemDiscrepancies: [
                [
                    'delivery_order_item_id' => $doItem->id,
                    'quantity_received' => 45,
                    'quantity_defective' => 0,
                    'defect_notes' => '1 dus kecil (5 unit) hilang/tercecer di pihak ekspedisi',
                ],
            ],
            discrepancyNotes: 'Klaim asuransi ekspedisi untuk 5 unit'
        );

        // Status DO harus partially_delivered karena penerimaan < kuantitas kirim (short shipment)
        $this->assertSame('partially_delivered', $confirmedDo->status);
        $this->assertSame(45, $doItem->fresh()->quantity_received);
        $this->assertSame(0, $doItem->fresh()->quantity_defective);

        // Sisa pesanan Item 1 pada Sales Order harus otomatis menjadi 55 (100 pesanan - 45 diterima baik)
        $remaining = $this->service->getRemainingQuantities($this->salesOrder);
        $item1Remaining = collect($remaining)->firstWhere('quotation_item_id', $this->item1->id);
        $this->assertSame(55, $item1Remaining['remaining_qty']);
        $this->assertSame(45, $item1Remaining['shipped_qty']);
        $this->assertFalse($item1Remaining['is_fulfilled']);
        $this->assertSame('partially_fulfilled', $this->salesOrder->fresh()->status);
    }

    public function test_cancel_delivery_order_rejects_partially_delivered_order(): void
    {
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal PBB'
        );
        $do = $this->service->createFulfillment($dto);
        $doItem = $do->items()->first();

        $this->service->confirmDeliveryWithDiscrepancy(
            deliveryOrder: $do,
            itemDiscrepancies: [
                [
                    'delivery_order_item_id' => $doItem->id,
                    'quantity_received' => 45,
                    'quantity_defective' => 5,
                ],
            ]
        );

        // DO yang sudah diserahterimakan sebagian dilarang dibatalkan
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telah berstatus partially_delivered tidak dapat dibatalkan');

        $this->service->cancelDeliveryOrder($do);
    }

    public function test_confirm_delivery_finalizes_sales_order_status_to_completed(): void
    {
        // Kirim seluruh sisa pesanan (100 pcs item1 dan 50 pcs item2)
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->salesOrder->id,
            items: [
                ['quotation_item_id' => $this->item1->id, 'quantity_delivered' => 100],
                ['quotation_item_id' => $this->item2->id, 'quantity_delivered' => 50],
            ],
            shippingMethod: 'Armada Internal PBB'
        );
        $do = $this->service->createFulfillment($dto);

        // Konfirmasi serah terima fisik 100% diterima baik
        $this->service->confirmDelivery($do, 'pod/final-receipt.pdf');

        $this->assertSame('completed', $this->salesOrder->fresh()->status);
    }

    public function test_validation_and_fulfillment_matches_custom_non_catalog_item_by_name(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'approved']);
        $customItem = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => null, // Non-katalog / ad-hoc sourcing
            'item_name' => 'Kabel Tray P31 Legrand 100x50',
            'quantity' => 20,
            'uom' => 'batang',
            'unit_price' => 250000,
            'total_price' => 5000000,
        ]);

        $so = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'order_number' => 'PBB/SOR/2026/09/9876',
            'status' => 'processing',
            'total_amount' => 5550000.00,
        ]);

        // Kirim barang menggunakan pencocokan item_name
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $so->id,
            items: [
                [
                    'item_name' => 'kabel tray p31 legrand 100x50',
                    'quantity_delivered' => 15,
                ],
            ],
            shippingMethod: 'Truk Logistik'
        );

        $do = $this->service->createFulfillment($dto);
        $this->assertCount(1, $do->items);
        $this->assertSame('Kabel Tray P31 Legrand 100x50', $do->items->first()->item_name);
        $this->assertSame(15, $do->items->first()->quantity_shipped);

        $remaining = $this->service->getRemainingQuantities($so);
        $this->assertSame(5, $remaining[0]['remaining_qty']);
        $this->assertSame(15, $remaining[0]['shipped_qty']);
    }
}
