<?php

declare(strict_types=1);

namespace App\Filament\Agent\Widgets;

use App\Models\FreelanceSales;
use App\Services\CommissionPayoutService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgentCommissionOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $agentId = (int) ($user->freelance_sales_id
            ?? FreelanceSales::where('user_id', $user->id)->value('id')
            ?? 0);

        if ($agentId === 0) {
            return [];
        }

        $service = app(CommissionPayoutService::class);
        $balance = $service->getAgentBalance($agentId);

        return [
            Stat::make('Available to Withdraw', 'Rp '.number_format($balance['payable_balance'], 0, ',', '.'))
                ->description('Ready for payout request')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending / In Review', 'Rp '.number_format($balance['total_requested'], 0, ',', '.'))
                ->description('Awaiting Finance disbursement')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Disbursed', 'Rp '.number_format($balance['total_paid'], 0, ',', '.'))
                ->description('Successfully transferred to bank')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Accumulated Incentives', 'Rp '.number_format($balance['total_earned'], 0, ',', '.'))
                ->description('Lifetime commission earnings')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
        ];
    }
}
