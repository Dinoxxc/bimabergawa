<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class IdempotencyConflictException extends Exception
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly string $action = 'transaksi',
        string $message = '',
    ) {
        if ($message === '') {
            $message = sprintf(
                'Permintaan duplikat terdeteksi dengan kunci idempotensi [%s] pada aksi [%s]. Operasi sedang diproses atau telah selesai.',
                $this->idempotencyKey,
                $this->action
            );
        }

        parent::__construct($message, 409);
    }
}
