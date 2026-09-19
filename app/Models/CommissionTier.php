<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_turnover_amount',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_turnover_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function freelanceSales(): HasMany
    {
        return $this->hasMany(FreelanceSales::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
