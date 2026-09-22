<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'sales_order_id',
        'courier_name',
        'driver_name',
        'vehicle_plate_number',
        'tracking_number',
        'tracking_url',
        'shipping_address',
        'recipient_pic_name',
        'recipient_phone',
        'total_packages',
        'total_weight_kg',
        'status',
        'proof_of_delivery_path',
        'discrepancy_notes',
        'return_proof_path',
        'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'total_packages' => 'integer',
            'total_weight_kg' => 'decimal:2',
            'shipped_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
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
