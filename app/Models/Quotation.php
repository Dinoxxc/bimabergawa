<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\AgentTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number',
        'revision_number',
        'inquiry_id',
        'user_id',
        'freelance_sales_id',
        'customer_name',
        'company_name',
        'delivery_terms',
        'validity_days',
        'expires_at',
        'subtotal',
        'discount_amount',
        'dpp',
        'vat_rate_applied',
        'tax_amount',
        'grand_total',
        'currency',
        'total_cost',
        'gross_profit_margin',
        'is_approved_by_director',
        'status',
        'pdf_path',
        'approved_by',
        'approved_at',
        'director_notes',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'validity_days' => 'integer',
            'expires_at' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'dpp' => 'decimal:2',
            'vat_rate_applied' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'gross_profit_margin' => 'decimal:2',
            'is_approved_by_director' => 'boolean',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new AgentTenantScope);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(QuotationPaymentTerm::class)->orderBy('sort_order');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeExpiringInDays(Builder $query, int $days = 3): Builder
    {
        return $query->whereIn('status', ['sent', 'approved'])
            ->whereDate('expires_at', '=', now()->addDays($days)->toDateString());
    }
}
