<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\UnitOfMeasure;

class UnitOfMeasureObserver
{
    /**
     * Handle the UnitOfMeasure "deleting" event.
     */
    public function deleting(UnitOfMeasure $unitOfMeasure): void
    {
        if (! $unitOfMeasure->isForceDeleting()) {
            // Hindari duplikasi sufiks jika telah bertanda -del-
            if (! preg_match('/-del-\d+$/', $unitOfMeasure->code)) {
                $base = substr($unitOfMeasure->code, 0, 8);
                $unitOfMeasure->code = $base.'-del-'.time();
                $unitOfMeasure->saveQuietly();
            }
        }
    }

    /**
     * Handle the UnitOfMeasure "restoring" event.
     */
    public function restoring(UnitOfMeasure $unitOfMeasure): void
    {
        // Bersihkan sufiks -del-{timestamp}
        $cleanCode = (string) preg_replace('/-del-\d+$/', '', $unitOfMeasure->code);

        // Loop deterministik jika kode yang sama telah aktif kembali
        $candidate = $cleanCode;
        $counter = 1;
        while (UnitOfMeasure::withTrashed()->where('code', $candidate)->where('id', '!=', $unitOfMeasure->id)->exists()) {
            $counter++;
            $candidate = substr("{$cleanCode}{$counter}", 0, 20);
        }
        $unitOfMeasure->code = $candidate;
    }
}
