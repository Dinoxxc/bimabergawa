<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidFileMagicBytesException extends Exception
{
    /**
     * @param  array<int, string>  $expectedMimes
     */
    public function __construct(
        public readonly string $detectedMime,
        public readonly array $expectedMimes,
        public readonly string $fileName = '',
        string $message = '',
    ) {
        if ($message === '') {
            $message = sprintf(
                'Berkas [%s] ditolak karena MIME magic bytes (%s) tidak cocok dengan tipe yang diizinkan (%s).',
                $this->fileName,
                $this->detectedMime,
                implode(', ', $this->expectedMimes)
            );
        }

        parent::__construct($message, 422);
    }
}
