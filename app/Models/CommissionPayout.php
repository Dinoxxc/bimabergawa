<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommissionPayout extends Model
{
    protected $fillable = [
        'payout_number',
        'freelance_sales_id',
        'sales_order_id',
        'amount',
        'type',
        'status',
        'transfer_proof_path',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
