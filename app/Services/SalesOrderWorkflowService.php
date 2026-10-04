<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderWorkflowService
{
    /**
     * Konversi penawaran harga resmi (Quotation) berstatus disetujui menjadi Sales Order.
     *
     * @throws InvalidArgumentException
     */
    public function convertFromQuotation(Quotation $quotation, array $attributes = []): SalesOrder
    {
        $eligibleStatuses = ['approved', 'won', 'accepted'];
        if (! in_array($quotation->status, $eligibleStatuses, true)) {
            throw new InvalidArgumentException(
                'Hanya penawaran berstatus disetujui/won yang dapat dikonversi ke Sales Order.'
            );
        }

        $existingOrder = SalesOrder::where('quotation_id', $quotation->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingOrder) {
            return $existingOrder;
        }

        return DB::transaction(function () use ($quotation, $attributes) {
            $orderNumber = DocumentNumberService::generateNext('SOR');

            // Evaluasi limit otorisasi Direktur: >= Rp 100 Juta atau margin kritis < 8%
            $approvalLimit = SystemSettingService::getFloat('commercial_tax.director_approval_limit', 100000000.00);
            $grandTotal = (float) $quotation->grand_total;
            $margin = (float) $quotation->gross_profit_margin;

            $requiresDirectorApproval = ($grandTotal >= $approvalLimit) || ($margin < 8.0);
            $initialStatus = $requiresDirectorApproval ? 'pending_director_approval' : 'approved';

            $salesOrder = SalesOrder::create([
                'order_number' => $orderNumber,
                'quotation_id' => $quotation->id,
                'customer_po_number' => $attributes['customer_po_number'] ?? null,
                'customer_po_date' => $attributes['customer_po_date'] ?? now()->toDateString(),
                'customer_po_file_path' => $attributes['customer_po_file_path'] ?? null,
                'project_name' => $attributes['project_name'] ?? $quotation->project_name,
                'status' => $initialStatus,
                'vat_rate_applied' => $quotation->vat_rate_applied,
                'total_amount' => $quotation->grand_total,
                'currency' => $quotation->currency ?? 'IDR',
                'is_approved_by_director' => false,
            ]);

            // Jika status langsung approved (nilai < 100 Jt & margin aman), aktifkan komisi agen
            if ($initialStatus === 'approved') {
                $commissionService = app(CommissionPayoutService::class);
                $commissionService->calculateCommission($salesOrder);
            }

            // Catat ke log aktivitas
            $actorId = auth()->id() ?? $quotation->user_id;
            ActivityLog::create([
                'log_name' => 'sales_order_converted',
                'causer_type' => User::class,
                'causer_id' => $actorId,
                'subject_type' => SalesOrder::class,
                'subject_id' => $salesOrder->id,
                'description' => "Sales Order {$orderNumber} converted from Quotation {$quotation->quotation_number}.",
                'properties' => [
                    'order_number' => $orderNumber,
                    'quotation_number' => $quotation->quotation_number,
                    'total_amount' => $grandTotal,
                    'status' => $initialStatus,
                ],
            ]);

            return $salesOrder;
        });
    }

    /**
     * Persetujuan resmi Sales Order oleh Direktur / Super Admin.
     *
     * @throws InvalidArgumentException
     */
    public function approveSalesOrder(SalesOrder $salesOrder, int $approverId, ?string $directorNotes = null): SalesOrder
    {
        if ($salesOrder->status !== 'pending_director_approval') {
            throw new InvalidArgumentException(
                "Sales Order tidak dalam status menunggu persetujuan (Status saat ini: {$salesOrder->status})."
            );
        }

        $salesOrder->update([
            'status' => 'approved',
            'is_approved_by_director' => true,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'director_notes' => $directorNotes,
        ]);

        // Kunci komisi agen freelance
        $commissionService = app(CommissionPayoutService::class);
        $commissionService->calculateCommission($salesOrder);

        ActivityLog::create([
            'log_name' => 'sales_order_approved',
            'causer_type' => User::class,
            'causer_id' => $approverId,
            'subject_type' => SalesOrder::class,
            'subject_id' => $salesOrder->id,
            'description' => "Sales Order {$salesOrder->order_number} approved by Director.",
            'properties' => [
                'total_amount' => (float) $salesOrder->total_amount,
                'director_notes' => $directorNotes,
            ],
        ]);

        return $salesOrder;
    }

    /**
     * Batalkan Sales Order dengan alasan wajib.
     *
     * @throws InvalidArgumentException
     */
    public function cancelSalesOrder(SalesOrder $salesOrder, string $reason, int $userId): SalesOrder
    {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new InvalidArgumentException('Alasan pembatalan Sales Order wajib diisi.');
        }

        if (in_array($salesOrder->status, ['completed', 'shipped'], true)) {
            throw new InvalidArgumentException('Sales Order yang sudah selesai atau terkirim tidak dapat dibatalkan.');
        }

        $salesOrder->update([
            'status' => 'cancelled',
            'cancellation_reason' => $trimmedReason,
        ]);

        ActivityLog::create([
            'log_name' => 'sales_order_cancelled',
            'causer_type' => User::class,
            'causer_id' => $userId,
            'subject_type' => SalesOrder::class,
            'subject_id' => $salesOrder->id,
            'description' => "Sales Order {$salesOrder->order_number} cancelled: {$trimmedReason}",
            'properties' => [
                'cancellation_reason' => $trimmedReason,
            ],
        ]);

        return $salesOrder;
    }

    /**
     * Generate berkas PDF Sales Order resmi.
     */
    public function generateSalesOrderPdf(SalesOrder $salesOrder): DomPdfInstance
    {
        return PdfExportService::generateSalesOrderPdf($salesOrder);
    }
}
