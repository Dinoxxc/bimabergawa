<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InsufficientFulfillmentQuantityException extends Exception
{
    public function __construct(
        public readonly int $salesOrderItemId,
        public readonly int|float $requestedQty,
        public readonly int|float $remainingQty,
        string $message = '',
    ) {
        if ($message === '') {
            $message = sprintf(
                'Kuantitas pengiriman (%s) untuk item SO ID [%d] melebihi sisa kuantitas yang belum terpenuhi (%s).',
                (string) $this->requestedQty,
                $this->salesOrderItemId,
                (string) $this->remainingQty
            );
        }

        parent::__construct($message, 422);
    }
}
