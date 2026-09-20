<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\DocumentNumberRequestDTO;
use App\Events\DocumentNumberGenerated;
use App\Models\DocumentSequence;
use App\Models\User;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DocumentNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentNumberService;
    }

    public function test_it_generates_sequential_inquiry_numbers(): void
    {
        $date = Carbon::parse('2026-09-15 10:00:00');

        $first = $this->service->generateInquiryNumber($date);
        $second = $this->service->generateInquiryNumber($date);

        $this->assertMatchesRegularExpression('/^INQ-202609-\d{4}$/', $first);
        $this->assertMatchesRegularExpression('/^INQ-202609-\d{4}$/', $second);

        $firstSeq = (int) substr($first, -4);
        $secondSeq = (int) substr($second, -4);

        $this->assertSame($firstSeq + 1, $secondSeq);
    }

    public function test_it_generates_official_prefixes_for_all_document_types(): void
    {
        $date = Carbon::parse('2026-09-15 10:00:00');

        $inq = $this->service->generateInquiryNumber($date);
        $quo = $this->service->generateQuotationNumber($date);
        $so = $this->service->generateSalesOrderNumber($date);
        $do = $this->service->generateDeliveryOrderNumber(null, 1, $date);
        $inv = $this->service->generateInvoiceNumber($date);
        $com = $this->service->generateCommissionNumber($date);

        $this->assertStringStartsWith('INQ-202609-', $inq);
        $this->assertStringStartsWith('QUO-202609-', $quo);
        $this->assertStringStartsWith('SO-202609-', $so);
        $this->assertStringStartsWith('DO-202609-', $do);
        $this->assertStringStartsWith('INV-202609-', $inv);
        $this->assertStringStartsWith('COM-202609-', $com);
    }

    public function test_it_supports_generation_via_dto(): void
    {
        $date = Carbon::parse('2026-09-20 12:00:00');
        $dto = new DocumentNumberRequestDTO(
            targetType: DocumentNumberRequestDTO::TYPE_QUOTATION,
            date: $date
        );

        $number = $this->service->generate($dto);

        $this->assertStringStartsWith('QUO-202609-', $number);
    }

    public function test_monthly_sequence_resets_on_new_period(): void
    {
        $septemberDate = Carbon::parse('2026-09-30 23:59:59');
        $octoberDate = Carbon::parse('2026-10-01 00:00:01');

        $septemberNum = $this->service->generateQuotationNumber($septemberDate);
        $this->assertStringStartsWith('QUO-202609-', $septemberNum);

        $octoberNum = $this->service->generateQuotationNumber($octoberDate);
        $this->assertStringStartsWith('QUO-202610-', $octoberNum);

        // Sequence in new month should start from 1
        $this->assertStringEndsWith('0001', $octoberNum);
    }

    public function test_it_formats_quotation_revision_numbers(): void
    {
        $base = 'QUO-202609-0001';

        $rev1 = $this->service->formatQuotationRevision($base, 1);
        $rev2 = $this->service->formatQuotationRevision($base, 2);
        $rev0 = $this->service->formatQuotationRevision($base, 0);

        $this->assertSame('QUO-202609-0001-R1', $rev1);
        $this->assertSame('QUO-202609-0001-R2', $rev2);
        $this->assertSame('QUO-202609-0001', $rev0);

        // If already has revision, cleanly replaces it
        $rev3 = $this->service->formatQuotationRevision('QUO-202609-0001-R1', 3);
        $this->assertSame('QUO-202609-0001-R3', $rev3);
    }

    public function test_it_formats_delivery_order_partial_from_sales_order(): void
    {
        $soNumber = 'SO-202609-0005';

        $doPartial1 = $this->service->formatDeliveryOrderPartial($soNumber, 1);
        $doPartial2 = $this->service->formatDeliveryOrderPartial($soNumber, 2);

        $this->assertSame('DO-202609-0005/01', $doPartial1);
        $this->assertSame('DO-202609-0005/02', $doPartial2);

        // Direct generateDeliveryOrderNumber with soNumber parameter
        $directDO = $this->service->generateDeliveryOrderNumber($soNumber, 1);
        $this->assertSame('DO-202609-0005/01', $directDO);
    }

    public function test_it_handles_sequence_overflow_above_9999_seamlessly(): void
    {
        $date = Carbon::parse('2026-11-01 10:00:00');
        $yearMonth = '202611';

        // Pre-set sequence to 9999
        DocumentSequence::updateOrCreate(
            ['document_type' => 'QUO', 'year_month' => $yearMonth],
            ['current_sequence' => 9999]
        );

        $num10000 = $this->service->generateQuotationNumber($date);
        $this->assertSame('QUO-202611-10000', $num10000);

        $num10001 = $this->service->generateQuotationNumber($date);
        $this->assertSame('QUO-202611-10001', $num10001);
    }

    public function test_it_dispatches_document_number_generated_event(): void
    {
        Event::fake([DocumentNumberGenerated::class]);

        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $date = Carbon::parse('2026-09-20 15:30:00');
        $generated = $this->service->generateInquiryNumber($date);

        Event::assertDispatched(DocumentNumberGenerated::class, function (DocumentNumberGenerated $event) use ($generated, $user) {
            return $event->targetType === DocumentNumberRequestDTO::TYPE_INQUIRY
                && $event->documentNumber === $generated
                && $event->userId === $user->id;
        });
    }

    public function test_it_throws_exception_on_invalid_document_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipe dokumen tidak valid: [invalid_type]');

        $this->service->generateNextNumber('invalid_type');
    }

    public function test_get_current_sequence_returns_accurate_value(): void
    {
        $date = Carbon::parse('2026-12-01 10:00:00');

        $initialSeq = $this->service->getCurrentSequence(DocumentNumberRequestDTO::TYPE_INVOICE, $date);
        $this->assertSame(0, $initialSeq);

        $this->service->generateInvoiceNumber($date);

        $afterSeq = $this->service->getCurrentSequence(DocumentNumberRequestDTO::TYPE_INVOICE, $date);
        $this->assertSame(1, $afterSeq);
    }
}
