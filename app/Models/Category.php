<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'slug',
        'icon_name',
        'description',
        'spec_template',
        'sort_order',
    ];

    public function getEffectiveCode(): string
    {
        if (! empty($this->code)) {
            return strtoupper(trim((string) preg_replace('/[^A-Za-z0-9]/', '', $this->code)));
        }

        $clean = (string) preg_replace('/[^A-Za-z0-9]/', '', $this->name);

        return strtoupper(substr($clean !== '' ? $clean : 'GEN', 0, 3));
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'spec_template' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
