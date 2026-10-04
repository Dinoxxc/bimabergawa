<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\PricingAndMarginCalculatorService;
use App\Services\SystemSettingService;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['quotation_number'])) {
            $data['quotation_number'] = DocumentNumberService::generateNext('QUO');
        }

        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id() ?? User::role('sales_pic')->first()?->id ?? 1;
        }

        $validityDays = max(1, (int) ($data['validity_days'] ?? 14));
        $data['validity_days'] = $validityDays;
        if (empty($data['expires_at'])) {
            $data['expires_at'] = now()->addDays($validityDays)->format('Y-m-d');
        }

        $vatRate = (float) ($data['vat_rate_applied'] ?? SystemSettingService::getFloat('commercial_tax.tax_vat_rate', 11.00));
        $data['vat_rate_applied'] = $vatRate;

        // Recalculate using server-side service if items are provided in mutation data
        $items = $data['items'] ?? [];
        if (! empty($items)) {
            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $exchangeRate = (float) ($data['exchange_rate_applied'] ?? 1.0);
            $currency = (string) ($data['currency'] ?? 'IDR');

            $calc = PricingAndMarginCalculatorService::calculate(
                items: $items,
                discountAmount: $discountAmount,
                vatRate: $vatRate,
                exchangeRate: $exchangeRate,
                currency: $currency,
                strict: false
            );

            $data['subtotal'] = $calc['subtotal'];
            $data['discount_amount'] = $calc['discount_amount'];
            $data['dpp'] = $calc['dpp'];
            $data['tax_amount'] = $calc['tax_amount'];
            $data['grand_total'] = $calc['grand_total'];
            $data['total_cost'] = $calc['total_cost'];
            $data['gross_profit_margin'] = $calc['margin_percentage'];

            // Live Margin Guard: evaluate approval requirement
            if ($calc['margin_tier'] === 'critical' || ($calc['requires_director_approval'] ?? false)) {
                if (empty($data['is_approved_by_director'])) {
                    $data['status'] = 'pending_director_approval';
                    $data['is_approved_by_director'] = false;
                }
            } else {
                $data['status'] = $data['status'] ?? 'draft';
            }
        } else {
            $data['status'] = $data['status'] ?? 'draft';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $quotation = $this->record;
        $quotation->load('items');

        if ($quotation->items->isNotEmpty()) {
            $itemsData = [];
            foreach ($quotation->items as $item) {
                $itemsData[] = [
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'cost_price' => (float) $item->cost_price,
                    'discount_percent' => (float) $item->discount_percent,
                ];
            }

            $calc = PricingAndMarginCalculatorService::calculate(
                items: $itemsData,
                discountAmount: (float) $quotation->discount_amount,
                vatRate: (float) $quotation->vat_rate_applied,
                exchangeRate: (float) $quotation->exchange_rate_applied,
                currency: (string) $quotation->currency,
                strict: false
            );

            $quotation->subtotal = $calc['subtotal'];
            $quotation->discount_amount = $calc['discount_amount'];
            $quotation->dpp = $calc['dpp'];
            $quotation->tax_amount = $calc['tax_amount'];
            $quotation->grand_total = $calc['grand_total'];
            $quotation->total_cost = $calc['total_cost'];
            $quotation->gross_profit_margin = $calc['margin_percentage'];

            if ($calc['margin_tier'] === 'critical' || ($calc['requires_director_approval'] ?? false)) {
                if (! $quotation->is_approved_by_director) {
                    $quotation->status = 'pending_director_approval';
                }
            } else {
                if ($quotation->status === 'pending_director_approval' && ! $quotation->is_approved_by_director) {
                    $quotation->status = 'draft';
                }
            }

            $quotation->saveQuietly();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
