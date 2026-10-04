<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\InternalComment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InquiryAssignmentService
{
    /**
     * Menugaskan inkuiri ke Sales PIC aktif dan memicu notifikasi serta jejak audit.
     * (TC-ERP-CRM-006, TC-ERP-CRM-012, TC-ERP-CRM-014)
     */
    public function assignSalesPic(Inquiry $inquiry, User $salesUser, ?User $assignedBy = null): Inquiry
    {
        // TC-ERP-CRM-012: Penugasan ke user non-aktif / akun dihapus dilarang
        if (! $salesUser->is_active) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'Cannot assign inquiry to an inactive user account.',
            ]);
        }

        // Pastikan user memiliki peran yang diizinkan (sales_pic atau super_admin)
        if (! $salesUser->hasRole('sales_pic') && ! $salesUser->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'Assigned user must possess Sales PIC or Super Admin role.',
            ]);
        }

        $oldUserId = $inquiry->assigned_user_id;
        $oldUserName = $inquiry->assignedUser?->name ?? 'Unassigned';

        return DB::transaction(function () use ($inquiry, $salesUser, $assignedBy, $oldUserId, $oldUserName) {
            $inquiry->update([
                'assigned_user_id' => $salesUser->id,
            ]);

            // Catat jejak audit Spatie ActivityLog (TC-ERP-CRM-014)
            ActivityLog::log(
                logName: 'inquiry_assignment',
                description: "Inquiry {$inquiry->inquiry_number} assigned from {$oldUserName} to {$salesUser->name}",
                subject: $inquiry,
                causer: $assignedBy ?? auth()->user(),
                properties: [
                    'old_assigned_user_id' => $oldUserId,
                    'new_assigned_user_id' => $salesUser->id,
                    'assigned_to_name' => $salesUser->name,
                ]
            );

            // Buat notifikasi lonceng in-app untuk sales yang ditugaskan (TC-ERP-CRM-006, TC-ERP-CRM-017)
            Notification::create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\InquiryAssignedNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $salesUser->id,
                'data' => [
                    'title' => 'New Inquiry Assigned',
                    'message' => "You have been assigned to inquiry {$inquiry->inquiry_number} ({$inquiry->company_name}).",
                    'inquiry_id' => $inquiry->id,
                    'inquiry_number' => $inquiry->inquiry_number,
                    'action_url' => "/admin/inquiries/{$inquiry->id}",
                ],
                'read_at' => null,
            ]);

            return $inquiry->fresh(['assignedUser']);
        });
    }

    /**
     * Memperbarui status inkuiri dengan validasi transisi, penanganan lost_reason, dan jejak audit.
     * (TC-ERP-CRM-003, TC-ERP-CRM-009, TC-ERP-CRM-010, TC-ERP-CRM-014, TC-ERP-CRM-016)
     */
    public function changeStatus(
        Inquiry $inquiry,
        string $newStatus,
        ?string $reason = null,
        ?User $actor = null,
        ?string $expectedUpdatedAt = null
    ): Inquiry {
        $allowedStatuses = ['new', 'contacted', 'quote_draft', 'quote_sent', 'deal', 'lost'];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid inquiry status '{$newStatus}'.",
            ]);
        }

        // TC-ERP-CRM-016: Optimistic locking concurrency check jika disediakan expectedUpdatedAt
        if ($expectedUpdatedAt !== null && $inquiry->updated_at?->toISOString() !== $expectedUpdatedAt) {
            throw ValidationException::withMessages([
                'concurrency' => 'This inquiry record has been modified by another user. Please refresh and review latest changes.',
            ]);
        }

        // TC-ERP-CRM-009 & TC-ERP-CRM-010: Validasi alasan kegagalan status lost
        if ($newStatus === 'lost') {
            $trimmedReason = trim((string) $reason);
            if (empty($trimmedReason)) {
                throw ValidationException::withMessages([
                    'lost_reason' => 'Alasan pembatalan/kegagalan inkuiri wajib diisi.',
                ]);
            }
        }

        $oldStatus = $inquiry->status;
        if ($oldStatus === $newStatus) {
            return $inquiry;
        }

        return DB::transaction(function () use ($inquiry, $newStatus, $reason, $actor, $oldStatus) {
            $updates = [
                'status' => $newStatus,
            ];

            // Jika status menjadi contacted dan belum ada timestamp first_contacted_at
            if ($newStatus === 'contacted' && $inquiry->first_contacted_at === null) {
                $updates['first_contacted_at'] = now();
            }

            // Jika ada alasan pembatalan lost, tambahkan ke catatan
            if ($newStatus === 'lost' && ! empty($reason)) {
                $timestampNote = '['.now()->format('Y-m-d H:i').'] Lost Reason: '.$reason;
                $updates['notes'] = $inquiry->notes ? $inquiry->notes."\n".$timestampNote : $timestampNote;
            }

            $inquiry->update($updates);

            // Catat jejak audit Spatie ActivityLog (TC-ERP-CRM-003, TC-ERP-CRM-014)
            ActivityLog::log(
                logName: 'inquiry_status_change',
                description: "Inquiry {$inquiry->inquiry_number} status changed from {$oldStatus} to {$newStatus}",
                subject: $inquiry,
                causer: $actor ?? auth()->user(),
                properties: [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                ]
            );

            return $inquiry->fresh();
        });
    }

    /**
     * Menghitung penuaan waktu respon SLA dan mengembalikan status badge.
     * (TC-ERP-CRM-004, TC-ERP-CRM-005)
     */
    public function calculateSlaAging(Inquiry $inquiry): array
    {
        $createdAt = $inquiry->created_at ?? now();
        $hoursElapsed = (int) $createdAt->diffInHours(now());

        // SLA hanya aktif untuk lead baru yang belum direspons / dikontak
        if (in_array($inquiry->status, ['new', 'lead'], true)) {
            // TC-ERP-CRM-005: SLA Kritis > 48 Jam
            if ($hoursElapsed >= 48) {
                return [
                    'level' => 'danger',
                    'hours' => $hoursElapsed,
                    'badge' => 'SLA Danger: > 48 Jam Belum Diproses',
                    'badge_short' => ">48h ({$hoursElapsed}h)",
                    'is_stale' => true,
                    'color' => 'danger',
                ];
            }

            // TC-ERP-CRM-004: SLA Peringatan > 24 Jam
            if ($hoursElapsed >= 24) {
                return [
                    'level' => 'warning',
                    'hours' => $hoursElapsed,
                    'badge' => "SLA Warning: {$hoursElapsed} Jam Belum Direspons",
                    'badge_short' => "{$hoursElapsed}h",
                    'is_stale' => true,
                    'color' => 'warning',
                ];
            }

            return [
                'level' => 'normal',
                'hours' => $hoursElapsed,
                'badge' => "{$hoursElapsed} Jam",
                'badge_short' => "{$hoursElapsed}h",
                'is_stale' => false,
                'color' => 'info',
            ];
        }

        return [
            'level' => 'resolved',
            'hours' => $hoursElapsed,
            'badge' => ucfirst(str_replace('_', ' ', $inquiry->status)),
            'badge_short' => ucfirst($inquiry->status),
            'is_stale' => false,
            'color' => 'gray',
        ];
    }

    /**
     * Memposting komentar/catatan koordinasi internal dengan dukungan mention dan dual-visibility.
     * (TC-ERP-CRM-018, TC-ERP-CRM-019, TC-ERP-CRM-020)
     */
    public function postComment(
        Inquiry $inquiry,
        User $author,
        string $comment,
        string $type = 'internal',
        ?string $attachmentPath = null,
        array $mentionedUserIds = []
    ): InternalComment {
        // Ekstrak otomatis mention @username jika mentionedUserIds kosong
        if (empty($mentionedUserIds) && str_contains($comment, '@')) {
            preg_match_all('/@([a-zA-Z0-9_\.\-]+)/', $comment, $matches);
            if (! empty($matches[1])) {
                $usernames = $matches[1];
                $mentionedUserIds = User::where(function ($q) use ($usernames) {
                    $q->whereIn('name', $usernames)
                        ->orWhereIn('email', $usernames);
                })->pluck('id')->toArray();
            }
        }

        return DB::transaction(function () use ($inquiry, $author, $comment, $type, $attachmentPath, $mentionedUserIds) {
            $internalComment = InternalComment::create([
                'user_id' => $author->id,
                'commentable_type' => Inquiry::class,
                'commentable_id' => $inquiry->id,
                'comment' => $comment,
                'type' => $type,
                'attachment_path' => $attachmentPath,
                'mentioned_user_ids' => array_values(array_unique($mentionedUserIds)),
            ]);

            // Kirim notifikasi ke user yang dimention (TC-ERP-CRM-019)
            $this->dispatchMentionNotifications($inquiry, $author, $internalComment, $mentionedUserIds);

            return $internalComment->fresh(['user']);
        });
    }

    /**
     * Mengirimkan notifikasi in-app ke pengguna yang dimention.
     * (TC-ERP-CRM-019)
     */
    public function dispatchMentionNotifications(
        Inquiry $inquiry,
        User $author,
        InternalComment $comment,
        array $mentionedUserIds
    ): void {
        foreach ($mentionedUserIds as $userId) {
            if ((int) $userId === (int) $author->id) {
                continue; // Jangan notifikasi diri sendiri
            }

            Notification::create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\UserMentionedNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $userId,
                'data' => [
                    'title' => 'You were mentioned in a note',
                    'message' => "{$author->name} mentioned you on inquiry {$inquiry->inquiry_number}.",
                    'inquiry_id' => $inquiry->id,
                    'inquiry_number' => $inquiry->inquiry_number,
                    'comment_id' => $comment->id,
                    'action_url' => "/admin/inquiries/{$inquiry->id}",
                ],
                'read_at' => null,
            ]);
        }
    }
}
