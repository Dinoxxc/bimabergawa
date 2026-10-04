<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use App\Services\PricingAndMarginCalculatorService;
use App\Services\SystemSettingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // TC-ERP-QUO-011: Lock form if quotation has been approved or won
        if (in_array($this->record->status, ['approved', 'accepted', 'won', 'converted_to_so', 'superseded'])) {
            Notification::make()
                ->title('Quotation Locked')
                ->body('This quotation is approved or closed and cannot be modified. Create a revision instead.')
                ->warning()
                ->send();

            $this->redirect(QuotationResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $validityDays = max(1, (int) ($data['validity_days'] ?? 14));
        $data['validity_days'] = $validityDays;
        if (empty($data['expires_at'])) {
            $data['expires_at'] = now()->addDays($validityDays)->format('Y-m-d');
        }

        $vatRate = (float) ($data['vat_rate_applied'] ?? $this->record->vat_rate_applied ?? SystemSettingService::getFloat('commercial_tax.tax_vat_rate', 11.00));
        $data['vat_rate_applied'] = $vatRate;

        // Recalculate using server-side service
        $items = $data['items'] ?? [];
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
        }

        return $data;
    }

    protected function afterSave(): void
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
            }

            $quotation->saveQuietly();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $pdf = PdfExportService::generateQuotationPdf($this->record);
                    $safeNumber = str_replace(['/', '\\'], '-', $this->record->quotation_number);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        "Quotation-{$safeNumber}.pdf"
                    );
                }),

            Action::make('approve_director')
                ->label('Approve by Director')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_director_approval' && (auth()->user()?->hasRole('super_admin') ?? false))
                ->form([
                    Forms\Components\Textarea::make('director_notes')
                        ->label('Approval Notes / Commercial Justification')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'approved',
                        'is_approved_by_director' => true,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'director_notes' => $data['director_notes'],
                    ]);

                    Notification::make()
                        ->title('Quotation Approved')
                        ->body("Quotation {$this->record->quotation_number} has been approved.")
                        ->success()
                        ->send();

                    $this->redirect(QuotationResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('create_revision')
                ->label('Create Revision')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Create Quotation Revision')
                ->modalDescription('This will create a new draft quotation with an incremented revision number (e.g. -R1) and mark this quotation as superseded.')
                ->action(function () {
                    $newRevision = $this->record->revision_number + 1;
                    $docService = app(DocumentNumberService::class);
                    $revisedNumber = $docService->formatQuotationRevision($this->record->quotation_number, $newRevision);

                    $newQuotation = $this->record->replicate([
                        'id', 'created_at', 'updated_at', 'deleted_at', 'pdf_path',
                    ]);
                    $newQuotation->quotation_number = $revisedNumber;
                    $newQuotation->revision_number = $newRevision;
                    $newQuotation->status = 'draft';
                    $newQuotation->is_approved_by_director = false;
                    $newQuotation->approved_by = null;
                    $newQuotation->approved_at = null;
                    $newQuotation->director_notes = null;
                    $newQuotation->save();

                    foreach ($this->record->items as $item) {
                        $newItem = $item->replicate(['id', 'quotation_id', 'created_at', 'updated_at']);
                        $newItem->quotation_id = $newQuotation->id;
                        $newItem->save();
                    }

                    $this->record->status = 'superseded';
                    $this->record->save();

                    Notification::make()
                        ->title('Revision Created')
                        ->body("New revision {$revisedNumber} created successfully.")
                        ->success()
                        ->send();

                    $this->redirect(QuotationResource::getUrl('edit', ['record' => $newQuotation]));
                })
                ->visible(fn () => in_array($this->record->status, ['approved', 'sent', 'sent_to_client', 'rejected', 'expired'])),

            DeleteAction::make()
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending_director_approval'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
