<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        if (! $product->isForceDeleting()) {
            $product->slug = $product->slug.'-deleted-'.time();
            $product->saveQuietly();
        }
    }

    /**
     * Handle the Product "restoring" event.
     */
    public function restoring(Product $product): void
    {
        // Bersihkan sufiks -deleted-{timestamp}
        $cleanSlug = (string) preg_replace('/-deleted-\d+$/', '', $product->slug);

        // Jika slug original telah dipakai produk aktif lain, berikan sufiks angka unik
        $count = Product::where('slug', $cleanSlug)->where('id', '!=', $product->id)->count();
        $product->slug = $count > 0 ? "{$cleanSlug}-".($count + 1) : $cleanSlug;
    }
}
