<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'item_name',
        'model_number',
        'lead_time',
        'quantity',
        'uom',
        'unit_price',
        'cost_price',
        'discount_percent',
        'total_price',
        'exchange_rate_applied',
        'base_currency',
        'warranty_period',
        'custom_specifications',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_price' => 'decimal:2',
            'exchange_rate_applied' => 'decimal:4',
            'custom_specifications' => 'array',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     * Melindungi modal HPP per item dari kebocoran ke agen atau publik.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'cost_price',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
