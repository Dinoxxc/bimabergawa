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

    protected static ?string $title = 'CRM Inquiries Kanban Board';

    public ?int $lostInquiryId = null;

    public string $lostReason = '';

    public ?int $assigningInquiryId = null;

    public ?int $selectedSalesUserId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('table_view')
                ->label('Table View')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
            Actions\Action::make('create_inquiry')
                ->label('New Inquiry')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }

    public function getStatuses(): array
    {
        return [
            'new' => [
                'title' => 'New Lead',
                'badge_color' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900',
            ],
            'contacted' => [
                'title' => 'Contacted',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900',
            ],
            'quote_draft' => [
                'title' => 'Quote Draft',
                'badge_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-900',
            ],
            'quote_sent' => [
                'title' => 'Negotiation',
                'badge_color' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900',
            ],
            'deal' => [
                'title' => 'Won',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900',
            ],
            'lost' => [
                'title' => 'Lost',
                'badge_color' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900',
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
     * Memindahkan status kartu kanban inkuiri (TC-ERP-CRM-003).
     */
    public function moveCard(int $inquiryId, string $targetStatus, InquiryAssignmentService $service): void
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
    public function confirmLost(InquiryAssignmentService $service): void
    {
        $this->validate([
            'lostReason' => 'required|string|min:3|max:1000',
        ], [
            'lostReason.required' => 'Alasan pembatalan/kegagalan inkuiri wajib diisi.',
            'lostReason.min' => 'Alasan pembatalan/kegagalan inkuiri wajib diisi minimal 3 karakter.',
        ]);

        if (! $this->lostInquiryId) {
            return;
        }

        $inquiry = Inquiry::findOrFail($this->lostInquiryId);
        $this->authorize('update', $inquiry);

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
    public function confirmAssign(InquiryAssignmentService $service): void
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
