<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'logo_path',
        'website_url',
        'description',
        'is_active',
        'sort_order',
    ];

    public function getEffectiveCode(): string
    {
        if (! empty($this->code)) {
            return strtoupper(trim((string) preg_replace('/[^A-Za-z0-9]/', '', $this->code)));
        }

        $clean = (string) preg_replace('/[^A-Za-z0-9]/', '', $this->name);

        return strtoupper(substr($clean !== '' ? $clean : 'GEN', 0, 4));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
