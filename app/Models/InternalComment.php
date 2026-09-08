<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalComment extends Model
{
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
