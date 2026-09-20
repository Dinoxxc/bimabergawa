<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\CalculateMarginDTO;
use App\DTOs\CommissionCalculationDTO;
use App\DTOs\DeliveryOrderFulfillmentDTO;
use App\DTOs\DocumentNumberRequestDTO;
use App\DTOs\FileUploadRequestDTO;
use App\DTOs\ParseSkuRequestDTO;
use App\Events\CommissionPayoutRequested;
use App\Events\DeliveryOrderDispatched;
use App\Events\DocumentNumberGenerated;
use App\Events\QuotationApproved;
use App\Events\QuotationCalculated;
use App\Events\SalesOrderConfirmed;
use App\Exceptions\CriticalMarginException;
use App\Exceptions\DuplicateDocumentNumberException;
use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InsufficientFulfillmentQuantityException;
use App\Exceptions\InvalidFileMagicBytesException;
use App\Models\Category;
use App\Models\CommissionPayout;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceFoundationContractsTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_number_request_dto(): void
    {
        $date = new DateTimeImmutable('2026-09-20');
        $dto = new DocumentNumberRequestDTO('quotation', $date, 5);

        $this->assertSame('quotation', $dto->targetType);
        $this->assertSame($date, $dto->date);
        $this->assertSame(5, $dto->tenantId);

        $dtoDefault = new DocumentNumberRequestDTO('inquiry');
        $this->assertSame('inquiry', $dtoDefault->targetType);
        $this->assertNull($dtoDefault->date);
        $this->assertNull($dtoDefault->tenantId);
    }

    public function test_calculate_margin_dto(): void
    {
        $dto = new CalculateMarginDTO(
            costPrice: 80000.0,
            unitPrice: 100000.0,
            quantity: 10,
            discountPercent: 5.0,
            vatRate: 12.0,
            exchangeRate: 1.0,
            currency: 'IDR'
        );

        $this->assertSame(80000.0, $dto->costPrice);
        $this->assertSame(100000.0, $dto->unitPrice);
        $this->assertSame(10, $dto->quantity);
        $this->assertSame(5.0, $dto->discountPercent);
        $this->assertSame(12.0, $dto->vatRate);
        $this->assertSame(1.0, $dto->exchangeRate);
        $this->assertSame('IDR', $dto->currency);
    }

    public function test_parse_sku_request_dto(): void
    {
        $dto = new ParseSkuRequestDTO("SCH-123\nABB-456", "\n", true);

        $this->assertSame("SCH-123\nABB-456", $dto->rawContent);
        $this->assertSame("\n", $dto->delimiter);
        $this->assertTrue($dto->strictBrandCheck);
    }

    public function test_file_upload_request_dto(): void
    {
        $dto = new FileUploadRequestDTO(
            originalName: 'boq_project.xlsx',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            sizeBytes: 204800,
            tmpPath: '/tmp/php12345',
            allowedExtensions: ['xlsx', 'pdf'],
            isPublic: false
        );

        $this->assertSame('boq_project.xlsx', $dto->originalName);
        $this->assertSame(204800, $dto->sizeBytes);
        $this->assertFalse($dto->isPublic);
        $this->assertSame(['xlsx', 'pdf'], $dto->allowedExtensions);
    }

    public function test_delivery_order_fulfillment_dto(): void
    {
        $items = [['sales_order_item_id' => 1, 'quantity_delivered' => 5]];
        $dto = new DeliveryOrderFulfillmentDTO(
            salesOrderId: 10,
            items: $items,
            shippingMethod: 'Kurir Internal PBB',
            driverNotes: 'Antar sebelum jam 15:00 WIB'
        );

        $this->assertSame(10, $dto->salesOrderId);
        $this->assertSame($items, $dto->items);
        $this->assertSame('Kurir Internal PBB', $dto->shippingMethod);
        $this->assertSame('Antar sebelum jam 15:00 WIB', $dto->driverNotes);
    }

    public function test_commission_calculation_dto(): void
    {
        $dto = new CommissionCalculationDTO(
            salesOrderId: 15,
            agentId: 3,
            subtotalEligible: 50000000.0,
            tierRatePercent: 2.5,
            calculatedAmount: 1250000.0
        );

        $this->assertSame(15, $dto->salesOrderId);
        $this->assertSame(3, $dto->agentId);
        $this->assertSame(50000000.0, $dto->subtotalEligible);
        $this->assertSame(2.5, $dto->tierRatePercent);
        $this->assertSame(1250000.0, $dto->calculatedAmount);
    }

    public function test_custom_domain_exceptions(): void
    {
        $criticalEx = new CriticalMarginException(5.5, 8.0);
        $this->assertSame(422, $criticalEx->getCode());
        $this->assertSame(5.5, $criticalEx->marginPercent);
        $this->assertStringContainsString('5.50%', $criticalEx->getMessage());

        $dupEx = new DuplicateDocumentNumberException('QUO-202609-0001', 'quotation');
        $this->assertSame(409, $dupEx->getCode());
        $this->assertSame('QUO-202609-0001', $dupEx->documentNumber);
        $this->assertStringContainsString('QUO-202609-0001', $dupEx->getMessage());

        $idempEx = new IdempotencyConflictException('key-abc-123', 'quotation_submit');
        $this->assertSame(409, $idempEx->getCode());
        $this->assertSame('key-abc-123', $idempEx->idempotencyKey);
        $this->assertStringContainsString('key-abc-123', $idempEx->getMessage());

        $magicEx = new InvalidFileMagicBytesException('application/x-dosexec', ['application/pdf'], 'boq.pdf');
        $this->assertSame(422, $magicEx->getCode());
        $this->assertSame('application/x-dosexec', $magicEx->detectedMime);
        $this->assertStringContainsString('boq.pdf', $magicEx->getMessage());

        $fulfillEx = new InsufficientFulfillmentQuantityException(101, 15, 10);
        $this->assertSame(422, $fulfillEx->getCode());
        $this->assertSame(101, $fulfillEx->salesOrderItemId);
        $this->assertStringContainsString('melebihi', $fulfillEx->getMessage());
    }

    public function test_domain_events_dispatchable(): void
    {
        Event::fake();

        DocumentNumberGenerated::dispatch('quotation', 'QUO-202609-0001', 1);
        QuotationCalculated::dispatch(1, 10000000.0, 18.5, 1100000.0);
        QuotationApproved::dispatch(1, 2, now());
        SalesOrderConfirmed::dispatch(1, 'SO-202609-0001', 11100000.0);
        DeliveryOrderDispatched::dispatch(1, 'DO-202609-0001', 'Kurir Internal');
        CommissionPayoutRequested::dispatch(1, 3, 500000.0, 'BCA 1234567890');

        Event::assertDispatched(DocumentNumberGenerated::class);
        Event::assertDispatched(QuotationCalculated::class);
        Event::assertDispatched(QuotationApproved::class);
        Event::assertDispatched(SalesOrderConfirmed::class);
        Event::assertDispatched(DeliveryOrderDispatched::class);
        Event::assertDispatched(CommissionPayoutRequested::class);
    }

    public function test_migration_schema_b2b_service_foundation_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'spec_template'));
        $this->assertTrue(Schema::hasColumn('quotations', 'exchange_rate_applied'));
        $this->assertTrue(Schema::hasColumn('quotations', 'base_currency'));
        $this->assertTrue(Schema::hasColumn('quotations', 'idempotency_key'));
        $this->assertTrue(Schema::hasColumn('quotation_items', 'exchange_rate_applied'));
        $this->assertTrue(Schema::hasColumn('quotation_items', 'base_currency'));
        $this->assertTrue(Schema::hasColumn('sales_orders', 'idempotency_key'));
        $this->assertTrue(Schema::hasColumn('commission_payouts', 'idempotency_key'));
    }

    public function test_model_casts_and_fillable(): void
    {
        $category = new Category([
            'name' => 'Panel Listrik',
            'slug' => 'panel-listrik',
            'spec_template' => [['name' => 'IP Rating', 'unit' => '', 'type' => 'string']],
        ]);
        $this->assertIsArray($category->spec_template);

        $quotation = new Quotation([
            'exchange_rate_applied' => '15500.2500',
            'base_currency' => 'USD',
            'idempotency_key' => 'idemp-12345',
        ]);
        $this->assertSame('15500.2500', (string) $quotation->exchange_rate_applied);
        $this->assertSame('USD', $quotation->base_currency);
        $this->assertSame('idemp-12345', $quotation->idempotency_key);

        $quotationItem = new QuotationItem([
            'exchange_rate_applied' => '1.0000',
            'base_currency' => 'IDR',
        ]);
        $this->assertSame('1.0000', (string) $quotationItem->exchange_rate_applied);
        $this->assertSame('IDR', $quotationItem->base_currency);

        $so = new SalesOrder(['idempotency_key' => 'so-idemp-1']);
        $this->assertSame('so-idemp-1', $so->idempotency_key);

        $payout = new CommissionPayout(['idempotency_key' => 'payout-idemp-1']);
        $this->assertSame('payout-idemp-1', $payout->idempotency_key);
    }
}
