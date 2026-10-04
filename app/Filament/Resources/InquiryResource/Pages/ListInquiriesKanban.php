<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryAssignmentService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class ListInquiriesKanban extends Page
{
    protected static string $resource = InquiryResource::class;

    protected string $view = 'filament.resources.inquiry-resource.pages.list-inquiries-kanban';

    protected static ?string $title = 'Inquiries';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public ?int $lostInquiryId = null;

    public string $lostReason = '';

    public ?int $assigningInquiryId = null;

    public ?int $selectedSalesUserId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_inquiry')
                ->label('New Inquiry')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }

    public function getStatuses(): array
    {
        return [
            'new' => [
                'title' => 'New Lead',
                'badge_class' => 'pbb-badge-sky',
            ],
            'contacted' => [
                'title' => 'Contacted',
                'badge_class' => 'pbb-badge-amber',
            ],
            'quote_draft' => [
                'title' => 'Quote Draft',
                'badge_class' => 'pbb-badge-indigo',
            ],
            'quote_sent' => [
                'title' => 'Negotiation',
                'badge_class' => 'pbb-badge-purple',
            ],
            'deal' => [
                'title' => 'Won',
                'badge_class' => 'pbb-badge-emerald',
            ],
            'lost' => [
                'title' => 'Lost',
                'badge_class' => 'pbb-badge-rose',
            ],
        ];
    }

    public function getInquiriesGroupedProperty(): BaseCollection
    {
        // TC-ERP-CRM-005: Urutkan kartu pada kolom 'new' dengan yang paling tua (SLA Stale) di posisi teratas
        return Inquiry::with(['assignedUser', 'items'])
            ->orderByRaw("CASE WHEN status = 'new' THEN created_at END ASC")
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('status');
    }

    public function getActiveSalesStaffProperty(): Collection
    {
        return User::role(['sales_pic', 'super_admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Move inquiry status on Kanban board (TC-ERP-CRM-003).
     */
    public function moveCard(int $inquiryId, string $targetStatus): void
    {
        $inquiry = Inquiry::findOrFail($inquiryId);

        // Verifikasi izin akses (TC-ERP-CRM-011, TC-ERP-CRM-013)
        $this->authorize('update', $inquiry);

        // Jika dipindah ke 'lost', buka modal untuk alasan pembatalan wajib (TC-ERP-CRM-009)
        if ($targetStatus === 'lost') {
            $this->lostInquiryId = $inquiryId;
            $this->lostReason = '';

            return;
        }

        $service = app(InquiryAssignmentService::class);
        $service->changeStatus($inquiry, $targetStatus, null, auth()->user());

        Notification::make()
            ->title('Status Updated')
            ->body("Inquiry {$inquiry->inquiry_number} moved to {$targetStatus}.")
            ->success()
            ->send();
    }

    public function openLostModal(int $inquiryId): void
    {
        $this->lostInquiryId = $inquiryId;
        $this->lostReason = '';
    }

    public function cancelLost(): void
    {
        $this->lostInquiryId = null;
        $this->lostReason = '';
    }

    /**
     * Konfirmasi pembatalan status lost dengan alasan wajib (TC-ERP-CRM-009, TC-ERP-CRM-010).
     */
    public function confirmLost(): void
    {
        $this->validate([
            'lostReason' => 'required|string|min:3|max:1000',
        ], [
            'lostReason.required' => 'Cancellation reason is required.',
            'lostReason.min' => 'Cancellation reason must be at least 3 characters.',
        ]);

        if (! $this->lostInquiryId) {
            return;
        }

        $inquiry = Inquiry::findOrFail($this->lostInquiryId);
        $this->authorize('update', $inquiry);

        $service = app(InquiryAssignmentService::class);
        $service->changeStatus($inquiry, 'lost', $this->lostReason, auth()->user());

        $this->lostInquiryId = null;
        $this->lostReason = '';

        Notification::make()
            ->title('Inquiry Closed as Lost')
            ->warning()
            ->send();
    }

    public function openAssignModal(int $inquiryId): void
    {
        $inquiry = Inquiry::findOrFail($inquiryId);
        $this->authorize('assignPic', $inquiry);

        $this->assigningInquiryId = $inquiryId;
        $this->selectedSalesUserId = $inquiry->assigned_user_id;
    }

    public function cancelAssign(): void
    {
        $this->assigningInquiryId = null;
        $this->selectedSalesUserId = null;
    }

    /**
     * Konfirmasi penugasan PIC sales (TC-ERP-CRM-006, TC-ERP-CRM-012).
     */
    public function confirmAssign(): void
    {
        $this->validate([
            'selectedSalesUserId' => 'required|exists:users,id',
        ]);

        if (! $this->assigningInquiryId) {
            return;
        }

        $inquiry = Inquiry::findOrFail($this->assigningInquiryId);
        $this->authorize('assignPic', $inquiry);

        $salesUser = User::findOrFail($this->selectedSalesUserId);
        $service = app(InquiryAssignmentService::class);
        $service->assignSalesPic($inquiry, $salesUser, auth()->user());

        $this->assigningInquiryId = null;
        $this->selectedSalesUserId = null;

        Notification::make()
            ->title('PIC Assigned')
            ->body("Inquiry {$inquiry->inquiry_number} assigned to {$salesUser->name}.")
            ->success()
            ->send();
    }
}
