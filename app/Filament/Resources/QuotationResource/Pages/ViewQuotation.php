<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => ! in_array($this->record->status, ['approved', 'accepted', 'won', 'converted_to_so', 'superseded'])),

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

                    $this->fillForm();
                }),

            Action::make('send_to_client')
                ->label('Send to Client')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['draft', 'approved']))
                ->action(function () {
                    if ($this->record->status === 'pending_director_approval') {
                        Notification::make()
                            ->title('Approval Required')
                            ->body('Cannot dispatch quotation while pending Director approval due to critical margin.')
                            ->danger()
                            ->send();

                        return;
                    }

                    PdfExportService::generateQuotationPdf($this->record);

                    $this->record->update(['status' => 'sent']);

                    if ($this->record->inquiry) {
                        $this->record->inquiry->update(['status' => 'quote_sent']);
                    }

                    Notification::make()
                        ->title('Quotation Dispatched')
                        ->body("Quotation {$this->record->quotation_number} dispatched to client.")
                        ->success()
                        ->send();

                    $this->fillForm();
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
        ];
    }
}
