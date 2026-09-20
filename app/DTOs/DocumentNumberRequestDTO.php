<?php

declare(strict_types=1);

namespace App\DTOs;

use DateTimeInterface;

readonly class DocumentNumberRequestDTO
{
    public function __construct(
        public string $targetType,
        public ?DateTimeInterface $date = null,
        public ?int $tenantId = null,
    ) {}
}
