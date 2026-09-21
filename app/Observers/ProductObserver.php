<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\BulkSkuNormalizationService;
use App\Services\SkuGeneratorService;
use Illuminate\Support\Str;

class ProductObserver
{
    /**
     * Handle the Product "creating" event.
     */
    public function creating(Product $product): void
    {
        if (empty($product->slug) && ! empty($product->name)) {
            $baseSlug = Str::slug($product->name);
            $candidate = $baseSlug;
            $counter = 1;
            while (Product::withTrashed()->where('slug', $candidate)->exists()) {
                $counter++;
                $candidate = "{$baseSlug}-{$counter}";
            }
            $product->slug = $candidate;
        }

        if (empty($product->sku)) {
            $product->sku = SkuGeneratorService::generate($product);
        } else {
            $product->sku = BulkSkuNormalizationService::normalize($product->sku);
        }
    }

    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        if (empty($product->sku)) {
            $originalSku = $product->getOriginal('sku');
            if (! empty($originalSku)) {
                // Cegah penghapusan tidak sengaja, pulihkan SKU awal
                $product->sku = (string) $originalSku;
            } else {
                // Produk warisan lama tanpa SKU (null) dibangkitkan otomatis
                $product->sku = SkuGeneratorService::generate($product);
            }
        } elseif ($product->isDirty('sku')) {
            $product->sku = BulkSkuNormalizationService::normalize($product->sku);
        }
    }

    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        if (! $product->isForceDeleting() && ! empty($product->slug)) {
            // Hindari duplikasi sufiks jika telah bertanda -deleted-
            if (! preg_match('/-deleted-\d+$/', (string) $product->slug)) {
                $base = substr((string) $product->slug, 0, 220);
                $product->slug = $base.'-deleted-'.time();
                $product->saveQuietly();
            }
        }
    }

    /**
     * Handle the Product "restoring" event.
     */
    public function restoring(Product $product): void
    {
        if (! empty($product->slug)) {
            // Bersihkan sufiks -deleted-{timestamp}
            $cleanSlug = (string) preg_replace('/-deleted-\d+$/', '', (string) $product->slug);

            // Loop deterministik: cari kandidat slug unik tanpa risiko benturan MySQL Unique Key
            $candidate = $cleanSlug;
            $counter = 1;
            while (Product::withTrashed()->where('slug', $candidate)->where('id', '!=', $product->id)->exists()) {
                $counter++;
                $candidate = "{$cleanSlug}-{$counter}";
            }
            $product->slug = $candidate;
        }
    }
}
