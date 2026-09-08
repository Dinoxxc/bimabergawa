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
}
