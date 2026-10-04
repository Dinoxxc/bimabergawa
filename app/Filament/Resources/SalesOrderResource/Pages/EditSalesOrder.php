<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Services\PdfExportService;
use App\Services\SalesOrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // TC-ERP-SO-011: Cegah penyuntingan jika sudah selesai, terkirim, atau dibatalkan
        if (in_array($this->record->status, ['completed', 'shipped', 'cancelled'], true)) {
            Notification::make()
                ->title('Sales Order Locked')
                ->body('Sales order cannot be edited once fulfilled, dispatched, or cancelled.')
                ->warning()
                ->send();

            $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->record]));
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
                    $pdf = PdfExportService::generateSalesOrderPdf($this->record);
                    $safeNumber = str_replace(['/', '\\'], '-', $this->record->order_number);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        "SalesOrder-{$safeNumber}.pdf"
                    );
                }),

            Action::make('approve_director')
                ->label('Approve by Director')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_director_approval' && (auth()->user()?->hasRole('super_admin') ?? false))
                ->form([
                    Forms\Components\Textarea::make('director_notes')
                        ->label('Director Approval Notes / Endorsement')
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(SalesOrderWorkflowService::class)->approveSalesOrder(
                        $this->record,
                        auth()->id(),
                        $data['director_notes']
                    );

                    Notification::make()
                        ->title('Sales Order Approved')
                        ->body("Sales Order {$this->record->order_number} approved by Director.")
                        ->success()
                        ->send();

                    $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('cancel_order')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => ! in_array($this->record->status, ['completed', 'shipped', 'cancelled'], true))
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->placeholder('Provide a clear business reason for order cancellation.'),
                ])
                ->action(function (array $data) {
                    app(SalesOrderWorkflowService::class)->cancelSalesOrder(
                        $this->record,
                        $data['cancellation_reason'],
                        auth()->id()
                    );

                    Notification::make()
                        ->title('Sales Order Cancelled')
                        ->body("Sales Order {$this->record->order_number} has been cancelled.")
                        ->danger()
                        ->send();

                    $this->redirect(SalesOrderResource::getUrl('index'));
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'cancelled'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
