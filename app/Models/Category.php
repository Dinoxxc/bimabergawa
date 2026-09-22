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

    public function getEffectiveCode(array $visitedIds = []): string
    {
        if ($this->id !== null) {
            $visitedIds[] = (int) $this->id;
        }

        if (! empty($this->code)) {
            $cleanCode = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $this->code));
            if ($cleanCode !== '') {
                return substr($cleanCode, 0, 10);
            }
        }

        // Wariskan kode divisi dari kategori induk jika ada (kebal siklus/rekursi tak hingga)
        if ($this->relationLoaded('parent') && $this->parent !== null) {
            $parentId = (int) ($this->parent->id ?? $this->parent_id);
            if (! in_array($parentId, $visitedIds, true)) {
                return $this->parent->getEffectiveCode($visitedIds);
            }
        } elseif ($this->parent_id !== null && ! in_array((int) $this->parent_id, $visitedIds, true)) {
            $parent = Category::find($this->parent_id);
            if ($parent !== null) {
                return $parent->getEffectiveCode($visitedIds);
            }
        }

        $cleanName = (string) preg_replace('/[^A-Za-z0-9]/', '', $this->name ?? '');

        return strtoupper(substr($cleanName !== '' ? $cleanName : 'GEN', 0, 3));
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
