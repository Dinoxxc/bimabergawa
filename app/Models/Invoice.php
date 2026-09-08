<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'quotation_id',
        'payment_term_id',
        'customer_name',
        'company_name',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat_rate_applied',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'currency',
        'status',
        'payment_method',
        'payment_proof_path',
        'paid_at',
        'notes',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_rate_applied' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) max(0, (float) $this->grand_total - (float) $this->paid_amount);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(QuotationPaymentTerm::class, 'payment_term_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
