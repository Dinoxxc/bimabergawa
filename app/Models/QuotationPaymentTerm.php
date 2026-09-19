<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationPaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'phase_name',
        'percentage',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'payment_term_id');
    }
}
