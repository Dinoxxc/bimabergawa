<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\DeliveryOrder;
use App\Services\DeliveryOrderFulfillmentService;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryOrder extends ViewRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (DeliveryOrder $record): bool => ! in_array($record->status, ['delivered', 'partially_delivered', 'cancelled'], true)),

            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (DeliveryOrder $record) {
                    $pdf = PdfExportService::generateDeliveryOrderPdf($record);
                    $safeNumber = str_replace(['/', '\\'], '-', $record->delivery_number);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        "DeliveryOrder-{$safeNumber}.pdf"
                    );
                }),

            Action::make('confirm_delivery')
                ->label('Confirm Delivery (POD)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (DeliveryOrder $record): bool => in_array($record->status, ['prepared', 'shipped'], true))
                ->form([
                    Forms\Components\FileUpload::make('proof_of_delivery')
                        ->label('Proof of Delivery (Signed DO & Client Stamp)')
                        ->disk('local_private')
                        ->directory('delivery_orders/pod')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->required(),

                    Forms\Components\Toggle::make('has_discrepancy')
                        ->label('Record Discrepancy / Damaged Goods (Cacat / Hilang)')
                        ->default(false)
                        ->live(),

                    Forms\Components\Textarea::make('discrepancy_notes')
                        ->label('Discrepancy Notes')
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy'))
                        ->required(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy')),

                    Forms\Components\FileUpload::make('return_proof')
                        ->label('Photo of Damaged Goods / Return Slip')
                        ->disk('local_private')
                        ->directory('delivery_orders/returns')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy')),
                ])
                ->action(function (DeliveryOrder $record, array $data) {
                    $service = app(DeliveryOrderFulfillmentService::class);
                    $podPath = $data['proof_of_delivery'] ?? null;
                    $hasDiscrepancy = (bool) ($data['has_discrepancy'] ?? false);

                    if ($hasDiscrepancy) {
                        $discrepancyNotes = $data['discrepancy_notes'] ?? null;
                        $returnProofPath = $data['return_proof'] ?? null;

                        // Default discrepancy list across all items
                        $itemDiscrepancies = [];
                        foreach ($record->items as $item) {
                            $itemDiscrepancies[] = [
                                'delivery_order_item_id' => $item->id,
                                'quantity_received' => $item->quantity_shipped,
                                'quantity_defective' => 0,
                                'defect_notes' => null,
                            ];
                        }

                        $service->confirmDeliveryWithDiscrepancy(
                            $record,
                            $itemDiscrepancies,
                            $podPath,
                            $returnProofPath,
                            $discrepancyNotes
                        );
                    } else {
                        $service->confirmDelivery($record, $podPath);
                    }

                    Notification::make()
                        ->title('Delivery Confirmed')
                        ->body("Delivery Order {$record->delivery_number} has been recorded as delivered.")
                        ->success()
                        ->send();
                }),

            Action::make('cancel_do')
                ->label('Cancel DO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (DeliveryOrder $record): bool => in_array($record->status, ['prepared', 'shipped'], true))
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required(),
                ])
                ->action(function (DeliveryOrder $record, array $data) {
                    $service = app(DeliveryOrderFulfillmentService::class);
                    $service->cancelDeliveryOrder($record, $data['cancellation_reason']);

                    Notification::make()
                        ->title('Delivery Order Cancelled')
                        ->body("Delivery Order {$record->delivery_number} cancelled and items restored to Sales Order.")
                        ->warning()
                        ->send();
                }),
        ];
    }
}
