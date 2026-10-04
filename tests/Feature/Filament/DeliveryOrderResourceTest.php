<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\DeliveryOrderFulfillmentDTO;
use App\Exceptions\InsufficientFulfillmentQuantityException;
use App\Filament\Resources\DeliveryOrderResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryOrder;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\DeliveryOrderFulfillmentService;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeliveryOrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $logisticsUser;

    protected User $salesUser;

    protected Brand $brand;

    protected Category $category;

    protected Product $productA;

    protected Product $productB;

    protected SalesOrder $approvedSalesOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->logisticsUser = User::factory()->create([
            'email' => 'logistics@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->logisticsUser->assignRole('logistics');

        $this->salesUser = User::factory()->create([
            'email' => 'sales@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesUser->assignRole('sales_pic');

        $this->brand = Brand::create([
            'name' => 'Schneider Electric',
            'slug' => 'schneider-electric',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Circuit Breaker',
            'slug' => 'circuit-breaker',
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Acti9 iC60N MCB 16A 3P',
            'slug' => 'acti9-ic60n-mcb-16a-3p',
            'sku' => 'A9F74316',
            'model_number' => 'A9F74316',
            'base_price' => 150000.00,
            'cost_price' => 100000.00,
            'stock_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Compact NSX100N MCCB 100A',
            'slug' => 'compact-nsx100n-mccb-100a',
            'sku' => 'LV429840',
            'model_number' => 'LV429840',
            'base_price' => 1200000.00,
            'cost_price' => 900000.00,
            'stock_status' => 'in_stock',
            'is_active' => true,
        ]);

        $this->approvedSalesOrder = $this->createSalesOrderFixture(status: 'approved');
    }

    private function createSalesOrderFixture(string $status = 'approved', float $total = 50000000.00): SalesOrder
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/10/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_name' => 'Budi Santoso',
            'company_name' => 'PT Mega Sarana MEP',
            'phone_wa' => '081234567890',
            'email' => 'procurement@megasarana.co.id',
            'status' => 'deal',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/10/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'inquiry_id' => $inquiry->id,
            'user_id' => $this->superAdmin->id,
            'company_name' => 'PT Mega Sarana MEP',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => 'procurement@megasarana.co.id',
            'status' => 'approved',
            'subtotal' => $total / 1.11,
            'vat_rate' => 11.00,
            'vat_amount' => $total - ($total / 1.11),
            'grand_total' => $total,
            'margin_percentage' => 25.00,
            'currency' => 'IDR',
            'payment_terms' => 'NET 30',
        ]);

        $item1 = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->productA->id,
            'item_name' => $this->productA->name,
            'model_number' => $this->productA->sku,
            'quantity' => 100,
            'unit_price' => 150000.00,
            'cost_price' => 100000.00,
            'total_price' => 15000000.00,
            'uom' => 'pcs',
        ]);

        $item2 = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->productB->id,
            'item_name' => $this->productB->name,
            'model_number' => $this->productB->sku,
            'quantity' => 20,
            'unit_price' => 1200000.00,
            'cost_price' => 900000.00,
            'total_price' => 24000000.00,
            'uom' => 'unit',
        ]);

        return SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/10/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'quotation_id' => $quotation->id,
            'customer_po_number' => 'PO-MEP-2026-099',
            'customer_po_date' => now()->toDateString(),
            'status' => $status,
            'vat_rate_applied' => 11.00,
            'total_amount' => $total,
            'currency' => 'IDR',
            'is_approved_by_director' => true,
        ]);
    }

    public function test_full_delivery_order_completes_sales_order(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $this->assertCount(2, $remaining);
        $this->assertEquals(100, $remaining[0]['remaining_qty']);
        $this->assertEquals(20, $remaining[1]['remaining_qty']);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 100],
                ['quotation_item_id' => $remaining[1]['quotation_item_id'], 'quantity_shipped' => 20],
            ],
            courierName: 'Armada Internal PBB',
            driverName: 'Pak Supri',
            vehiclePlateNumber: 'B 9876 PBB',
            totalPackages: 5,
            totalWeightKg: 125.50
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);

        $this->assertInstanceOf(DeliveryOrder::class, $do);
        $this->assertStringStartsWith('PBB/DOR/', $do->delivery_number);
        $this->assertEquals('shipped', $do->status);
        $this->assertEquals(5, $do->total_packages);
        $this->assertEquals(125.50, (float) $do->total_weight_kg);

        // Sales Order status becomes completed because all remaining quantities are 0
        $this->approvedSalesOrder->refresh();
        $this->assertEquals('completed', $this->approvedSalesOrder->status);

        $updatedRemaining = $service->getRemainingQuantities($this->approvedSalesOrder);
        $this->assertEquals(0, $updatedRemaining[0]['remaining_qty']);
        $this->assertEquals(0, $updatedRemaining[1]['remaining_qty']);
    }

    public function test_partial_delivery_order_updates_status_to_partially_fulfilled(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        // Batch 1: Ship 40 pcs out of 100 pcs for Item 1, 0 pcs for Item 2
        $dto1 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 40],
            ],
            courierName: 'Indah Cargo',
            trackingNumber: 'IC-99281726'
        );

        $do1 = $service->createFulfillment($dto1, $this->superAdmin->id);

        $this->approvedSalesOrder->refresh();
        $this->assertEquals('partially_fulfilled', $this->approvedSalesOrder->status);

        $remAfterBatch1 = $service->getRemainingQuantities($this->approvedSalesOrder);
        $this->assertEquals(60, $remAfterBatch1[0]['remaining_qty']);
        $this->assertEquals(20, $remAfterBatch1[1]['remaining_qty']);

        // Batch 2: Ship remaining 60 pcs and 20 pcs
        $dto2 = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remAfterBatch1[0]['quotation_item_id'], 'quantity_shipped' => 60],
                ['quotation_item_id' => $remAfterBatch1[1]['quotation_item_id'], 'quantity_shipped' => 20],
            ],
            courierName: 'Armada Internal PBB'
        );

        $do2 = $service->createFulfillment($dto2, $this->superAdmin->id);

        $this->approvedSalesOrder->refresh();
        $this->assertEquals('completed', $this->approvedSalesOrder->status);

        $remAfterBatch2 = $service->getRemainingQuantities($this->approvedSalesOrder);
        $this->assertEquals(0, $remAfterBatch2[0]['remaining_qty']);
        $this->assertEquals(0, $remAfterBatch2[1]['remaining_qty']);
    }

    public function test_delivery_order_saves_expedition_and_fleet_details(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 10],
            ],
            courierName: 'Baraka Sarana Tama',
            driverName: 'Ahmad Dani',
            vehiclePlateNumber: 'B 1234 XYZ',
            trackingNumber: 'BST-JKT-889900',
            totalPackages: 2,
            totalWeightKg: 45.00
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $do->id,
            'courier_name' => 'Baraka Sarana Tama',
            'driver_name' => 'Ahmad Dani',
            'vehicle_plate_number' => 'B 1234 XYZ',
            'tracking_number' => 'BST-JKT-889900',
            'total_packages' => 2,
        ]);
    }

    public function test_delivery_order_pdf_export_has_zero_prices_and_kop(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 50],
            ],
            courierName: 'Armada Internal PBB',
            driverName: 'Pak Joko',
            vehiclePlateNumber: 'B 5555 PBB'
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);

        $pdf = PdfExportService::generateDeliveryOrderPdf($do, saveToStorage: false);
        $output = $pdf->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);

        // Verify HTML template does not render prices or costs (Zero Price Rule)
        $view = view('pdf.delivery-order', ['deliveryOrder' => $do])->render();
        $this->assertStringContainsString('PT. Panca Bima Bergawa', $view);
        $this->assertStringContainsString('Surat Jalan', $view);
        $this->assertStringContainsString('A9F74316', $view);
        $this->assertStringNotContainsString('Rp ', $view);
        $this->assertStringNotContainsString('Subtotal', $view);
        $this->assertStringNotContainsString('Total Amount', $view);
        $this->assertStringNotContainsString('Cost Price', $view);
        $this->assertStringNotContainsString('Margin', $view);
    }

    public function test_confirm_delivery_updates_status_and_timestamps(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 10],
            ],
            courierName: 'Armada Internal PBB'
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);
        $this->assertNull($do->delivered_at);

        $updatedDo = $service->confirmDelivery($do, 'delivery_orders/pod/sample_signed_pod.pdf');

        $this->assertEquals('delivered', $updatedDo->status);
        $this->assertNotNull($updatedDo->delivered_at);
        $this->assertEquals('delivery_orders/pod/sample_signed_pod.pdf', $updatedDo->proof_of_delivery_path);
    }

    public function test_validation_rejects_quantity_exceeding_remaining_order(): void
    {
        $this->expectException(InsufficientFulfillmentQuantityException::class);

        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        // Remaining is 100 pcs, attempt to ship 150 pcs
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 150],
            ],
            courierName: 'Armada Internal PBB'
        );

        $service->createFulfillment($dto, $this->superAdmin->id);
    }

    public function test_validation_rejects_delivery_for_unapproved_sales_order(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $unapprovedSO = $this->createSalesOrderFixture(status: 'pending_director_approval');
        $unapprovedSO->is_approved_by_director = false;
        $unapprovedSO->total_amount = 150000000.00;
        $unapprovedSO->save();

        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($unapprovedSO);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $unapprovedSO->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 10],
            ],
            courierName: 'Armada Internal PBB'
        );

        $service->createFulfillment($dto, $this->superAdmin->id);
    }

    public function test_cancel_delivery_order_restores_remaining_quantity(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 40],
            ],
            courierName: 'Armada Internal PBB'
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);

        $this->approvedSalesOrder->refresh();
        $this->assertEquals('partially_fulfilled', $this->approvedSalesOrder->status);

        // Cancel the DO
        $service->cancelDeliveryOrder($do, 'Customer project site closed');

        $this->assertSoftDeleted('delivery_orders', ['id' => $do->id]);

        $this->approvedSalesOrder->refresh();
        $this->assertEquals('processing', $this->approvedSalesOrder->status);

        $restored = $service->getRemainingQuantities($this->approvedSalesOrder);
        $this->assertEquals(100, $restored[0]['remaining_qty']);
    }

    public function test_cancel_delivery_order_rejects_already_delivered_order(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 10],
            ],
            courierName: 'Armada Internal PBB'
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);
        $service->confirmDelivery($do);

        // Cancellation must be rejected once delivered
        $service->cancelDeliveryOrder($do);
    }

    public function test_confirm_delivery_with_discrepancy_records_defects(): void
    {
        $service = app(DeliveryOrderFulfillmentService::class);
        $remaining = $service->getRemainingQuantities($this->approvedSalesOrder);

        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: $this->approvedSalesOrder->id,
            items: [
                ['quotation_item_id' => $remaining[0]['quotation_item_id'], 'quantity_shipped' => 50],
            ],
            courierName: 'Armada Internal PBB'
        );

        $do = $service->createFulfillment($dto, $this->superAdmin->id);
        $doItem = $do->items->first();

        // 45 received in good condition, 5 defective
        $service->confirmDeliveryWithDiscrepancy(
            $do,
            [
                [
                    'delivery_order_item_id' => $doItem->id,
                    'quantity_received' => 45,
                    'quantity_defective' => 5,
                    'defect_notes' => '5 pcs cracked body during transport',
                ],
            ],
            podPath: 'delivery_orders/pod/sample_pod.pdf',
            returnProofPath: 'delivery_orders/returns/defect_photo.jpg',
            discrepancyNotes: '5 units damaged, replacement required'
        );

        $do->refresh();
        $this->assertEquals('partially_delivered', $do->status);
        $this->assertEquals('5 units damaged, replacement required', $do->discrepancy_notes);

        // The 5 defective items are restored to SO remaining quantity!
        $this->approvedSalesOrder->refresh();
        $this->assertEquals('partially_fulfilled', $this->approvedSalesOrder->status);

        $remAfterDefect = $service->getRemainingQuantities($this->approvedSalesOrder);
        // Original 100 - 45 received = 55 remaining
        $this->assertEquals(55, $remAfterDefect[0]['remaining_qty']);
    }

    public function test_document_number_service_generates_unique_delivery_order_numbers(): void
    {
        $docService = app(DocumentNumberService::class);

        $num1 = $docService->generateDeliveryOrderNumber('PBB/SOR/2026/10/0001', 1);
        $num2 = $docService->generateDeliveryOrderNumber('PBB/SOR/2026/10/0001', 2);

        $this->assertNotEquals($num1, $num2);
        $this->assertStringStartsWith('PBB/DOR/', $num1);
        $this->assertStringStartsWith('PBB/DOR/', $num2);
    }

    public function test_delivery_orders_pages_render_successfully(): void
    {
        $this->actingAs($this->logisticsUser);

        $response = $this->get(DeliveryOrderResource::getUrl('index'));
        $response->assertSuccessful();

        $createResponse = $this->get(DeliveryOrderResource::getUrl('create', [
            'sales_order_id' => $this->approvedSalesOrder->id,
        ]));
        $createResponse->assertSuccessful();
    }
}
