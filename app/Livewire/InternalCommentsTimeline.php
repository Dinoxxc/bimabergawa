<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryAssignmentService;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class InternalCommentsTimeline extends Component
{
    public Inquiry $inquiry;

    public string $commentText = '';

    public string $type = 'internal';

    public array $mentionedUserIds = [];

    protected $rules = [
        'commentText' => 'required|string|min:2|max:3000',
        'type' => 'required|in:internal,agent_visible',
    ];

    public function mount(Inquiry $inquiry): void
    {
        $this->inquiry = $inquiry;
    }

    public function getCommentsProperty(): Collection
    {
        return $this->inquiry->comments()
            ->with('user')
            ->latest()
            ->get();
    }

    public function getAvailableStaffProperty(): Collection
    {
        return User::where('is_active', true)
            ->role(['super_admin', 'sales_pic', 'finance', 'logistics'])
            ->get(['id', 'name', 'email']);
    }

    public function submitComment(InquiryAssignmentService $service): void
    {
        $this->validate();

        $service->postComment(
            inquiry: $this->inquiry,
            author: auth()->user(),
            comment: $this->commentText,
            type: $this->type,
            attachmentPath: null,
            mentionedUserIds: $this->mentionedUserIds
        );

        $this->reset(['commentText', 'mentionedUserIds']);
        $this->type = 'internal';

        FilamentNotification::make()
            ->title('Note published')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.internal-comments-timeline', [
            'comments' => $this->comments,
            'availableStaff' => $this->availableStaff,
        ]);
    }
}
