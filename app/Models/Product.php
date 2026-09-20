<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'category_id',
        'successor_product_id',
        'name',
        'slug',
        'model_number',
        'sku',
        'cost_price',
        'currency',
        'uom',
        'moq',
        'warranty_period',
        'description',
        'datasheet_path',
        'availability_status',
        'is_featured',
        'is_published',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'moq' => 'integer',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'successor_product_id')->withTrashed();
    }

    public function predecessors(): HasMany
    {
        return $this->hasMany(Product::class, 'successor_product_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function inquiryItems(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function deliveryOrderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
