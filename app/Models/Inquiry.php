<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\AgentTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inquiry_number',
        'assigned_user_id',
        'freelance_sales_id',
        'customer_name',
        'company_name',
        'phone_wa',
        'email',
        'project_location',
        'type',
        'source',
        'status',
        'notes',
        'boq_attachment_path',
        'first_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'first_contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new AgentTenantScope);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function telemetry(): HasOne
    {
        return $this->hasOne(InquiryTelemetry::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->latestOfMany();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query->where('status', 'new')
            ->where('created_at', '<=', now()->subHours(2));
    }
}
