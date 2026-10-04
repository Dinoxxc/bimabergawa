<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources\AgentInquiryResource\Pages;

use App\Filament\Agent\Resources\AgentInquiryResource;
use App\Services\SecureFileUploadService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAgentInquiry extends ViewRecord
{
    protected static string $resource = AgentInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Download BOQ Document Action
            Action::make('download_boq')
                ->label('Download BOQ')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => ! empty($this->record->boq_attachment_path))
                ->url(fn (): string => SecureFileUploadService::generateTemporaryUrl($this->record->boq_attachment_path))
                ->openUrlInNewTab(),

            // WhatsApp Direct Contact Action
            Action::make('contact_wa')
                ->label('WhatsApp Client')
                ->icon('heroicon-m-chat-bubble-bottom-center-text')
                ->color('success')
                ->url(function (): string {
                    $cleanPhone = (string) preg_replace('/[^0-9]/', '', $this->record->phone_wa);
                    if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '62'.substr($cleanPhone, 1);
                    }

                    return "https://wa.me/{$cleanPhone}";
                })
                ->openUrlInNewTab(),
        ];
    }

    protected function getFooterView(): ?string
    {
        return 'filament.resources.inquiry-resource.pages.view-inquiry-footer';
    }
}
