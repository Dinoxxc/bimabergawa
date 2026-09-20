<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class DuplicateDocumentNumberException extends Exception
{
    public function __construct(
        public readonly string $documentNumber,
        public readonly string $targetType,
        string $message = '',
    ) {
        if ($message === '') {
            $message = sprintf(
                'Nomor dokumen duplikat terdeteksi untuk tipe [%s]: %s. Operasi dibatalkan demi integritas urutan atomik.',
                $this->targetType,
                $this->documentNumber
            );
        }

        parent::__construct($message, 409);
    }
}
