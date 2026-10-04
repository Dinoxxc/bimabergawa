<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Widgets;

use App\Models\Inquiry;
use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InquiryOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Inquiry::count();
        $unassigned = Inquiry::whereNull('assigned_user_id')->count();
        $slaAlerts = Inquiry::where('status', 'new')
            ->where('created_at', '<=', now()->subHours(24))
            ->count();
        $totalEstValue = (float) Quotation::whereIn('status', ['draft', 'approved', 'sent'])->sum('grand_total');

        return [
            Stat::make('Total Inquiries', (string) $total)
                ->description('All inbound commercial leads')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('primary')
                ->chart([3, 5, 4, 7, 6, 8]),

            Stat::make('Unassigned Inquiries', (string) $unassigned)
                ->description('Pending Sales PIC assignment')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassigned > 0 ? 'warning' : 'success'),

            Stat::make('SLA Critical Watchlist', (string) $slaAlerts)
                ->description('> 24h lead aging alert')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($slaAlerts > 0 ? 'danger' : 'success'),

            Stat::make('Est. Pipeline Value', 'Rp '.number_format($totalEstValue, 0, ',', '.'))
                ->description('Active qualified deal interest')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([20, 45, 60, 80, 110, 148]),
        ];
    }
}
