<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentNumberGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $targetType,
        public readonly string $documentNumber,
        public readonly ?int $userId = null,
    ) {}
}
