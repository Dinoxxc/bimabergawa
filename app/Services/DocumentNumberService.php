<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DocumentNumberRequestDTO;
use App\Events\DocumentNumberGenerated;
use App\Models\DocumentSequence;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    /**
     * Peta awalan prefix dokumen resmi PT. Panca Bima Bergawa.
     */
    public const PREFIX_MAP = [
        DocumentNumberRequestDTO::TYPE_INQUIRY => 'INQ',
        DocumentNumberRequestDTO::TYPE_QUOTATION => 'QUO',
        DocumentNumberRequestDTO::TYPE_SALES_ORDER => 'SOR',
        DocumentNumberRequestDTO::TYPE_DELIVERY_ORDER => 'DOR',
        DocumentNumberRequestDTO::TYPE_INVOICE => 'INV',
        DocumentNumberRequestDTO::TYPE_COMMISSION => 'COM',
        'inquiry' => 'INQ',
        'quotation' => 'QUO',
        'sales_order' => 'SOR',
        'delivery_order' => 'DOR',
        'invoice' => 'INV',
        'commission' => 'COM',
        'INQ' => 'INQ',
        'QUO' => 'QUO',
        'SOR' => 'SOR',
        'DOR' => 'DOR',
        'INV' => 'INV',
        'COM' => 'COM',
        'SO' => 'SOR',
        'DO' => 'DOR',
    ];

    /**
     * Generate nomor dokumen atomik berikutnya dengan proteksi race condition pesimistis.
     */
    public function generateNextNumber(
        DocumentNumberRequestDTO|string $request,
        ?DateTimeInterface $date = null,
        ?int $userId = null
    ): string {
        $effectiveDate = $date;
        $effectiveUserId = $userId;

        if ($request instanceof DocumentNumberRequestDTO) {
            $targetType = $request->targetType;
            $effectiveDate = $request->date ?? $date;
        } else {
            $targetType = $request;
        }

        $normalizedType = trim($targetType);
        $lookupKey = strtolower($normalizedType);

        if (! isset(self::PREFIX_MAP[$lookupKey]) && ! isset(self::PREFIX_MAP[$normalizedType])) {
            throw new InvalidArgumentException(sprintf(
                'Tipe dokumen tidak valid: [%s]. Harus salah satu dari tipe terdaftar.',
                $targetType
            ));
        }

        $prefix = self::PREFIX_MAP[$lookupKey] ?? self::PREFIX_MAP[$normalizedType];
        $targetDate = $effectiveDate ?? now();
        $year = $targetDate->format('Y');
        $month = $targetDate->format('m');
        $yearMonth = $year.$month;

        $sequenceNumber = DB::transaction(function () use ($prefix, $yearMonth) {
            $sequenceRecord = DocumentSequence::where('document_type', $prefix)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->first();

            if (! $sequenceRecord) {
                try {
                    $sequenceRecord = DocumentSequence::create([
                        'document_type' => $prefix,
                        'year_month' => $yearMonth,
                        'current_sequence' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $sequenceRecord = DocumentSequence::where('document_type', $prefix)
                        ->where('year_month', $yearMonth)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $sequenceRecord = DocumentSequence::where('id', $sequenceRecord->id)
                    ->lockForUpdate()
                    ->first();
            }

            $sequenceRecord->increment('current_sequence');

            return (int) $sequenceRecord->current_sequence;
        }, 3);

        // Hasilkan 4-karakter cryptographic security salt deterministik berbasis APP_KEY
        $saltKey = sprintf('%s/%s/%s/%d', $prefix, $year, $month, $sequenceNumber);
        $salt = strtoupper(substr(hash_hmac('sha256', $saltKey, config('app.key') ?: 'pbb-salt-seed'), 0, 4));

        if ($sequenceNumber > 9999) {
            $documentNumber = sprintf('PBB/%s/%s/%s%s/%d', $prefix, $year, $month, $salt, $sequenceNumber);
        } else {
            $documentNumber = sprintf('PBB/%s/%s/%s%s/%04d', $prefix, $year, $month, $salt, $sequenceNumber);
        }

        $resolvedUserId = $effectiveUserId ?? (auth()->check() ? auth()->id() : null);

        DocumentNumberGenerated::dispatch($targetType, $documentNumber, $resolvedUserId);

        return $documentNumber;
    }

    /**
     * Alias kompatibilitas via DocumentNumberRequestDTO.
     */
    public function generate(DocumentNumberRequestDTO $dto, ?int $userId = null): string
    {
        return $this->generateNextNumber($dto, $dto->date, $userId);
    }

    /**
     * Alias generate berdasarkan tipe dokumen.
     */
    public function generateForType(string $targetType, ?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber($targetType, $date, $userId);
    }

    public function generateInquiryNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_INQUIRY, $date, $userId);
    }

    public function generateQuotationNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_QUOTATION, $date, $userId);
    }

    public function generateSalesOrderNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_SALES_ORDER, $date, $userId);
    }

    public function generateInvoiceNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_INVOICE, $date, $userId);
    }

    public function generateCommissionNumber(?DateTimeInterface $date = null, ?int $userId = null): string
    {
        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_COMMISSION, $date, $userId);
    }

    public function generateDeliveryOrderNumber(
        ?string $soNumber = null,
        int $partialIndex = 1,
        ?DateTimeInterface $date = null,
        ?int $userId = null
    ): string {
        if ($soNumber !== null && trim($soNumber) !== '') {
            return $this->formatDeliveryOrderPartial($soNumber, $partialIndex);
        }

        return $this->generateNextNumber(DocumentNumberRequestDTO::TYPE_DELIVERY_ORDER, $date, $userId);
    }

    /**
     * Format revisi penawaran resmi (misal: PBB/QUO/2026/092F9C/0001-R1).
     */
    public function formatQuotationRevision(string $baseQuotationNumber, int $revision): string
    {
        $cleanBase = preg_replace('/-R\d+$/i', '', trim($baseQuotationNumber));
        if ($revision <= 0) {
            return (string) $cleanBase;
        }

        return sprintf('%s-R%d', $cleanBase, $revision);
    }

    /**
     * Alias method revisi penawaran.
     */
    public function generateQuotationRevisionNumber(string $baseQuotationNumber, int $revisionIndex = 1): string
    {
        return $this->formatQuotationRevision($baseQuotationNumber, $revisionIndex);
    }

    /**
     * Format surat jalan parsial berdasar sales order (misal: PBB/DOR/2026/092F9C/0001/01).
     */
    public function formatDeliveryOrderPartial(string $soNumber, int $partialIndex = 1): string
    {
        $cleanSo = trim($soNumber);

        if (str_contains($cleanSo, '/SOR/')) {
            $cleanDo = str_replace('/SOR/', '/DOR/', $cleanSo);
        } elseif (str_contains($cleanSo, '/SO/')) {
            $cleanDo = str_replace('/SO/', '/DOR/', $cleanSo);
        } else {
            $cleanDo = preg_replace('/^(SO|SOR)-/i', 'DOR-', $cleanSo);
        }

        return sprintf('%s/%02d', $cleanDo, max(1, $partialIndex));
    }

    /**
     * Ambil urutan terakhir untuk kombinasi tipe dokumen dan periode tahun-bulan.
     */
    public function getCurrentSequence(string $targetType, ?DateTimeInterface $date = null): int
    {
        $normalizedType = trim($targetType);
        $lookupKey = strtolower($normalizedType);

        if (! isset(self::PREFIX_MAP[$lookupKey]) && ! isset(self::PREFIX_MAP[$normalizedType])) {
            throw new InvalidArgumentException(sprintf(
                'Tipe dokumen tidak valid: [%s]',
                $targetType
            ));
        }

        $prefix = self::PREFIX_MAP[$lookupKey] ?? self::PREFIX_MAP[$normalizedType];
        $yearMonth = ($date ?? now())->format('Ym');

        $record = DocumentSequence::where('document_type', $prefix)
            ->where('year_month', $yearMonth)
            ->first();

        return $record ? (int) $record->current_sequence : 0;
    }
}
