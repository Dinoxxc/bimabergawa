<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'quotation_id',
        'customer_po_number',
        'customer_po_date',
        'customer_po_file_path',
        'status',
        'vat_rate_applied',
        'total_amount',
        'currency',
        'is_approved_by_director',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_po_date' => 'date',
            'vat_rate_applied' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_approved_by_director' => 'boolean',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function commissionPayout(): HasOne
    {
        return $this->hasOne(CommissionPayout::class);
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
