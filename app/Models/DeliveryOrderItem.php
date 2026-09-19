<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'product_id',
        'item_name',
        'model_number',
        'quantity_shipped',
    ];

    protected function casts(): array
    {
        return [
            'quantity_shipped' => 'integer',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
