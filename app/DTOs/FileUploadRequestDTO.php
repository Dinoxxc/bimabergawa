<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class FileUploadRequestDTO
{
    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public function __construct(
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public string $tmpPath,
        public array $allowedExtensions = ['pdf', 'xlsx', 'jpg', 'png'],
        public bool $isPublic = false,
    ) {}
}
