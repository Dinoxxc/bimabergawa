<?php

declare(strict_types=1);

namespace App\Events;

use DateTimeInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotationApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $quotationId,
        public readonly int $approverUserId,
        public readonly ?DateTimeInterface $approvedAt = null,
    ) {}
}
