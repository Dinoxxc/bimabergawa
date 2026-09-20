<?php

declare(strict_types=1);

namespace App\DTOs;

use DateTimeInterface;
use InvalidArgumentException;

readonly class DocumentNumberRequestDTO
{
    public const TYPE_INQUIRY = 'inquiry';

    public const TYPE_QUOTATION = 'quotation';

    public const TYPE_SALES_ORDER = 'sales_order';

    public const TYPE_DELIVERY_ORDER = 'delivery_order';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_COMMISSION = 'commission';

    public const VALID_TYPES = [
        self::TYPE_INQUIRY,
        self::TYPE_QUOTATION,
        self::TYPE_SALES_ORDER,
        self::TYPE_DELIVERY_ORDER,
        self::TYPE_INVOICE,
        self::TYPE_COMMISSION,
    ];

    public function __construct(
        public string $targetType,
        public ?DateTimeInterface $date = null,
        public ?int $tenantId = null,
    ) {
        if (! in_array($this->targetType, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid document target type [%s]. Must be one of: %s',
                $this->targetType,
                implode(', ', self::VALID_TYPES)
            ));
        }
    }
}
