<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CriticalMarginException extends Exception
{
    public function __construct(
        public readonly float $marginPercent,
        public readonly float $threshold = 8.0,
        string $message = '',
    ) {
        if ($message === '') {
            $message = sprintf(
                'Margin penawaran (%.2f%%) berada di bawah batas minimum kritis (%.2f%%). Persetujuan Direktur diperlukan.',
                $this->marginPercent,
                $this->threshold
            );
        }

        parent::__construct($message, 422);
    }
}
