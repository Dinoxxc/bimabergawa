<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\DeliveryOrder;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

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
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var DeliveryOrder $record */
        $record = $this->getRecord();

        if (in_array($record->status, ['delivered', 'partially_delivered', 'cancelled'], true)) {
            Notification::make()
                ->title('Editing Locked')
                ->body("Delivery Order with status '{$record->status}' cannot be modified.")
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
