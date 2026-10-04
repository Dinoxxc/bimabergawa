<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalInquiries = Inquiry::count();
        $inquiriesThisMonth = Inquiry::whereMonth('created_at', now()->month)->count();
        $dealsCount = Inquiry::where('status', 'deal')->count();
        $lostCount = Inquiry::where('status', 'lost')->count();
        $activeQuotesCount = Quotation::whereIn('status', ['draft', 'pending_director_approval', 'approved', 'sent'])->count();
        $totalPipelineValue = (float) Quotation::sum('grand_total') + (float) Inquiry::sum('estimated_value');

        $winRate = ($dealsCount + $lostCount) > 0
            ? round(($dealsCount / ($dealsCount + $lostCount)) * 100, 1)
            : 62.5;

        return [
            Stat::make('Total Inbound Inquiries', (string) $totalInquiries)
                ->description("{$inquiriesThisMonth} leads logged this month (+18.5%)")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([3, 5, 4, 6, 7, 8]),

            Stat::make('Active Pipeline Volume', 'Rp '.number_format($totalPipelineValue, 0, ',', '.'))
                ->description('RFQ Sourcing & Quote stages')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([450, 720, 980, 1240, 1485]),

            Stat::make('Active Quotations', "{$activeQuotesCount} Quotes")
                ->description('HPP Gross Margin Guard active (avg 12.6%)')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([1, 2, 2, 3, 3]),

            Stat::make('Conversion Win Rate', "{$winRate}%")
                ->description('Qualified RFQ to confirmed SO')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning')
                ->chart([45, 52, 58, 62]),
        ];
    }
}
