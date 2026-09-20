<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\SkuGeneratorService;

class ProductObserver
{
    /**
     * Handle the Product "creating" event.
     */
    public function creating(Product $product): void
    {
        if (empty($product->sku)) {
            $product->sku = SkuGeneratorService::generate($product);
        }
    }

    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        if (! $product->isForceDeleting()) {
            // Hindari duplikasi sufiks jika telah bertanda -deleted-
            if (! preg_match('/-deleted-\d+$/', $product->slug)) {
                $base = substr($product->slug, 0, 220);
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
        // Bersihkan sufiks -deleted-{timestamp}
        $cleanSlug = (string) preg_replace('/-deleted-\d+$/', '', $product->slug);

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
