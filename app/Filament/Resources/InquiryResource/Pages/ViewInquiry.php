<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Models\User;
use App\Services\InquiryAssignmentService;
use App\Services\SecureFileUploadService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            // Assign PIC Action (TC-ERP-CRM-006, TC-ERP-CRM-012)
            Actions\Action::make('assign_pic')
                ->label('Assign PIC')
                ->icon('heroicon-m-user-plus')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('assignPic', $this->record) ?? false)
                ->form([
                    Forms\Components\Select::make('assigned_user_id')
                        ->label('Select Sales PIC')
                        ->options(fn () => User::role(['sales_pic', 'super_admin'])->where('is_active', true)->pluck('name', 'id'))
                        ->default($this->record->assigned_user_id)
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data, InquiryAssignmentService $service): void {
                    $targetUser = User::findOrFail($data['assigned_user_id']);
                    $service->assignSalesPic($this->record, $targetUser, auth()->user());

                    Notification::make()
                        ->title('PIC Assigned')
                        ->body("Inquiry {$this->record->inquiry_number} successfully assigned to {$targetUser->name}.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['assigned_user_id']);
                }),

            // Direct WhatsApp Action
            Actions\Action::make('contact_wa')
                ->label('WhatsApp')
                ->icon('heroicon-m-chat-bubble-bottom-center-text')
                ->color('success')
                ->url(function (): string {
                    $cleanPhone = (string) preg_replace('/[^0-9]/', '', $this->record->phone_wa);
                    if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '62'.substr($cleanPhone, 1);
                    }

                    return "https://wa.me/{$cleanPhone}";
                })
                ->openUrlInNewTab()
                ->after(function (InquiryAssignmentService $service): void {
                    if ($this->record->status === 'new') {
                        $service->changeStatus($this->record, 'contacted', null, auth()->user());
                    }
                }),

            // Download BOQ Document Action (TC-ERP-CRM-007)
            Actions\Action::make('download_boq')
                ->label('Download BOQ')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => ! empty($this->record->boq_attachment_path))
                ->url(fn (): string => SecureFileUploadService::generateTemporaryUrl($this->record->boq_attachment_path))
                ->openUrlInNewTab(),
        ];
    }

    protected function getFooterView(): ?string
    {
        return 'filament.resources.inquiry-resource.pages.view-inquiry-footer';
    }
}
