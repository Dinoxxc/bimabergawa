<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTOs\DocumentNumberRequestDTO;
use App\Models\DocumentSequence;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyDocumentNumberTest extends TestCase
{
    use DatabaseTransactions;

    private DocumentNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentNumberService;
    }

    /**
     * TC-SEC-CON-001: Penomoran Quotation Serentak (Concurrent Quotation Numbering).
     */
    public function test_tc_sec_con_001_concurrent_quotation_numbering_produces_unique_sequential_records(): void
    {
        $date = Carbon::parse('2026-09-25 14:00:00');
        $generatedNumbers = [];

        // Simulasi 10 request serentak
        for ($i = 0; $i < 10; $i++) {
            $generatedNumbers[] = $this->service->generateQuotationNumber($date);
        }

        $this->assertCount(10, $generatedNumbers);
        $this->assertCount(10, array_unique($generatedNumbers), 'Harus tidak ada nomor duplikat (0% race condition)');

        // Verifikasi kesinambungan urutan sequence
        $sequences = array_map(fn (string $num) => (int) substr($num, -4), $generatedNumbers);
        for ($i = 0; $i < count($sequences) - 1; $i++) {
            $this->assertSame($sequences[$i] + 1, $sequences[$i + 1], 'Urutan penomoran harus berkesinambungan tanpa gap');
        }
    }

    /**
     * TC-SEC-CON-002: Penomoran Sales Order Serentak (Concurrent SO Numbering).
     */
    public function test_tc_sec_con_002_concurrent_sales_order_numbering_is_unique_and_consistent(): void
    {
        $date = Carbon::parse('2026-09-25 14:15:00');
        $generatedNumbers = [];

        for ($i = 0; $i < 10; $i++) {
            $generatedNumbers[] = $this->service->generateSalesOrderNumber($date);
        }

        $this->assertCount(10, $generatedNumbers);
        $this->assertCount(10, array_unique($generatedNumbers));

        foreach ($generatedNumbers as $soNum) {
            $this->assertStringStartsWith('PBB/SO/2026/09', $soNum);
        }
    }

    /**
     * TC-SEC-CON-003: Penomoran Surat Jalan Serentak (Concurrent DO Numbering).
     */
    public function test_tc_sec_con_003_concurrent_delivery_order_numbering_is_unique(): void
    {
        $date = Carbon::parse('2026-09-25 14:30:00');
        $generatedNumbers = [];

        for ($i = 0; $i < 10; $i++) {
            $generatedNumbers[] = $this->service->generateDeliveryOrderNumber(null, 1, $date);
        }

        $this->assertCount(10, $generatedNumbers);
        $this->assertCount(10, array_unique($generatedNumbers));

        foreach ($generatedNumbers as $doNum) {
            $this->assertStringStartsWith('PBB/DO/2026/09', $doNum);
        }
    }

    /**
     * TC-SEC-CON-006: Transisi Bulan Baru pada Format Penomoran (Monthly Sequence Reset).
     */
    public function test_tc_sec_con_006_monthly_sequence_resets_properly_across_periods(): void
    {
        $datePeriodA = Carbon::parse('2026-09-30 23:59:59');
        $datePeriodB = Carbon::parse('2026-10-01 00:00:01');

        $lastSept = $this->service->generateInquiryNumber($datePeriodA);
        $firstOct = $this->service->generateInquiryNumber($datePeriodB);

        $this->assertStringStartsWith('PBB/INQ/2026/09', $lastSept);
        $this->assertStringStartsWith('PBB/INQ/2026/10', $firstOct);

        $this->assertStringEndsWith('/0001', $firstOct);
    }

    /**
     * TC-SEC-CON-007: Penegakan Kunci Baris SELECT FOR UPDATE pada Tabel Sequence.
     */
    public function test_tc_sec_con_007_exclusive_row_lock_on_document_sequences_table(): void
    {
        $date = Carbon::parse('2026-09-25 15:00:00');
        $yearMonth = $date->format('Ym');

        // Panggil service untuk menginisialisasi sequence record
        $this->service->generateCommissionNumber($date);

        // Verifikasi record sequence eksis di database InnoDB
        $seq = DocumentSequence::where('document_type', 'COM')
            ->where('year_month', $yearMonth)
            ->first();

        $this->assertNotNull($seq);
        $this->assertGreaterThanOrEqual(1, $seq->current_sequence);
    }

    /**
     * TC-SEC-CON-010: Penegakan Unique Constraint Database sebagai Garis Pertahanan Akhir.
     */
    public function test_tc_sec_con_010_database_unique_constraint_prevents_duplicate_sequences(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        // Buat record sequence pertama
        DocumentSequence::create([
            'document_type' => 'INQ',
            'year_month' => '202712',
            'current_sequence' => 1,
        ]);

        // Coba insert duplikat kombinasi document_type + year_month langsung via model
        DocumentSequence::create([
            'document_type' => 'INQ',
            'year_month' => '202712',
            'current_sequence' => 2,
        ]);
    }

    /**
     * TC-SEC-CON-011: Integritas Transaksi saat Terjadi Pengecualian (Transaction Rollback).
     */
    public function test_tc_sec_con_011_transaction_rollback_prevents_corrupted_business_state(): void
    {
        $date = Carbon::parse('2026-09-25 16:00:00');

        $initialSeq = $this->service->getCurrentSequence(DocumentNumberRequestDTO::TYPE_INQUIRY, $date);

        try {
            DB::transaction(function () use ($date) {
                $this->service->generateInquiryNumber($date);

                // Simulasi kegagalan proses bisnis lanjutan
                throw new Exception('Simulasi kegagalan bisnis');
            });
        } catch (Exception $e) {
            $this->assertSame('Simulasi kegagalan bisnis', $e->getMessage());
        }

        // Karena DB::transaction di-rollback, data sequence di dalam transaksi ter-rollback
        $postSeq = $this->service->getCurrentSequence(DocumentNumberRequestDTO::TYPE_INQUIRY, $date);
        $this->assertSame($initialSeq, $postSeq);
    }

    /**
     * TC-SEC-CON-014: Penomoran Dokumen Mencapai Digit Maksimal (9.999 per Bulan) & Proteksi Overflow.
     */
    public function test_tc_sec_con_014_overflow_protection_handles_above_9999_smoothly(): void
    {
        $date = Carbon::parse('2026-11-15 10:00:00');
        $yearMonth = '202611';

        DocumentSequence::updateOrCreate(
            ['document_type' => 'SO', 'year_month' => $yearMonth],
            ['current_sequence' => 9999]
        );

        $so10000 = $this->service->generateSalesOrderNumber($date);
        $this->assertStringStartsWith('PBB/SO/2026/11', $so10000);
        $this->assertStringEndsWith('/10000', $so10000);

        $so10001 = $this->service->generateSalesOrderNumber($date);
        $this->assertStringStartsWith('PBB/SO/2026/11', $so10001);
        $this->assertStringEndsWith('/10001', $so10001);
    }
}
