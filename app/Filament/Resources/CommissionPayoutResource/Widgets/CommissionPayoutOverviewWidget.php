<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommissionPayoutResource\Widgets;

use App\Models\CommissionPayout;
use App\Models\FreelanceSales;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommissionPayoutOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAccrued = (float) CommissionPayout::sum('amount');
        $totalPaid = (float) CommissionPayout::where('status', 'paid')->sum('amount');
        $totalPayable = (float) CommissionPayout::whereIn('status', ['pending_payment', 'payable'])->sum('amount');
        $agentCount = FreelanceSales::count();

        return [
            Stat::make('Accrued Commissions', 'Rp '.number_format($totalAccrued, 0, ',', '.'))
                ->description('Total earned sales incentives')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary')
                ->chart([5, 8, 12, 16]),

            Stat::make('Disbursed & Settled', 'Rp '.number_format($totalPaid, 0, ',', '.'))
                ->description('Verified bank transfers sent')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Payable / Under Review', 'Rp '.number_format($totalPayable, 0, ',', '.'))
                ->description('Awaiting Finance / Director approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Freelance Network', (string) $agentCount.' Partners')
                ->description('Active registered freelance agents')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
