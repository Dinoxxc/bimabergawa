<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationResource\Widgets;

use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuotationOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCount = Quotation::whereIn('status', ['draft', 'pending_director_approval', 'approved', 'sent'])->count();
        $totalQuoted = (float) Quotation::sum('grand_total');
        $pendingDirector = Quotation::where('status', 'pending_director_approval')->count();
        $avgMargin = (float) Quotation::avg('gross_profit_margin');

        return [
            Stat::make('Active Quotations', (string) $activeCount)
                ->description('Commercial offers in circulation')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([2, 3, 2, 4, 3, 5]),

            Stat::make('Total Quoted Value', 'Rp '.number_format($totalQuoted, 0, ',', '.'))
                ->description('Aggregate grand total (inc. PPN)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([150, 245, 520, 765, 929]),

            Stat::make('Pending Director Review', (string) $pendingDirector)
                ->description('Margin < 8% HPP guard escalation')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($pendingDirector > 0 ? 'danger' : 'success'),

            Stat::make('Avg. Gross Margin', number_format($avgMargin, 1).'%')
                ->description('Target floor threshold >= 15.0%')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($avgMargin >= 15.0 ? 'success' : 'warning'),
        ];
    }
}
