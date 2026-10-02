<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'comment',
        'type',
        'mentioned_user_ids',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'mentioned_user_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('agent_comment_visibility', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // Staf internal memiliki akses penuh ke seluruh komentar (termasuk modal HPP & direksi)
                if ($user->hasRole('super_admin') || $user->hasRole('sales_pic') || $user->hasRole('finance') || $user->hasRole('logistics')) {
                    return;
                }

                // Seluruh pengguna non-staf internal (termasuk agen freelance) hanya dapat melihat komentar publik agen
                $builder->where($builder->getModel()->getTable().'.type', 'agent_visible');
            } else {
                // Pengguna tamu (guest / unauthenticated) jika ada kueri dibatasi ke komentar publik agen
                $builder->where($builder->getModel()->getTable().'.type', 'agent_visible');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeAgentVisible(Builder $query): Builder
    {
        return $query->where('type', 'agent_visible');
    }

    public function scopeInternalOnly(Builder $query): Builder
    {
        return $query->where('type', 'internal');
    }
}
