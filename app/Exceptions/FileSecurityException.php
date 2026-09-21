<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class FileSecurityException extends Exception
{
    public function __construct(
        string $message = 'Operasi berkas ditolak karena potensi pelanggaran keamanan.',
        public readonly string $fileName = '',
        public readonly string $violationType = 'general',
        int $code = 422,
    ) {
        parent::__construct($message, $code);
    }
}
