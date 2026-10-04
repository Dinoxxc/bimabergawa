<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Models\Quotation;
use App\Services\CommissionPayoutService;
use App\Services\DocumentNumberService;
use App\Services\SystemSettingService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['order_number'])) {
            $data['order_number'] = DocumentNumberService::generateNext('SOR');
        }

        $quotation = Quotation::find($data['quotation_id']);
        if ($quotation) {
            $data['vat_rate_applied'] = $quotation->vat_rate_applied;
            $data['total_amount'] = $quotation->grand_total;
            $data['currency'] = $quotation->currency ?? 'IDR';

            if (empty($data['project_name'])) {
                $data['project_name'] = $quotation->project_name;
            }

            // Evaluasi limit otorisasi Direktur: >= Rp 100 Juta atau margin kritis < 8%
            $approvalLimit = SystemSettingService::getFloat('commercial_tax.director_approval_limit', 100000000.00);
            $grandTotal = (float) $quotation->grand_total;
            $margin = (float) $quotation->gross_profit_margin;

            $requiresDirectorApproval = ($grandTotal >= $approvalLimit) || ($margin < 8.0);
            $data['status'] = $requiresDirectorApproval ? 'pending_director_approval' : 'approved';
            $data['is_approved_by_director'] = false;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $salesOrder = $this->record;
        if ($salesOrder->status === 'approved') {
            app(CommissionPayoutService::class)->calculateCommission($salesOrder);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
