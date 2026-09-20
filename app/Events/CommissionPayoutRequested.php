<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommissionPayoutRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $payoutId,
        public readonly int $agentId,
        public readonly float $amount,
        public readonly ?string $bankTarget = null,
    ) {}
}
