<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'product_id',
        'item_name',
        'model_number',
        'quantity',
        'uom',
        'custom_notes',
        'custom_specifications',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'custom_specifications' => 'array',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
