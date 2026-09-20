<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ParseSkuRequestDTO
{
    public function __construct(
        public string $rawContent,
        public string $delimiter = "\n",
        public bool $strictBrandCheck = false,
    ) {}
}
