<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryOrderResource\Widgets;

use App\Models\DeliveryOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeliveryOrderOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDO = DeliveryOrder::count();
        $delivered = DeliveryOrder::where('status', 'delivered')->count();
        $inTransit = DeliveryOrder::where('status', 'shipped')->count();
        $totalWeight = (float) DeliveryOrder::sum('total_weight_kg');
        $totalPkgs = (int) DeliveryOrder::sum('total_packages');

        return [
            Stat::make('Dispatched Surat Jalan', (string) $totalDO)
                ->description('Logistics delivery releases')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart([1, 2, 1, 3, 2]),

            Stat::make('Delivered to Site', (string) $delivered)
                ->description('Proof of delivery received')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Active In-Transit', (string) $inTransit)
                ->description('Fleet or courier waybill active')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($inTransit > 0 ? 'info' : 'gray'),

            Stat::make('Freight Handled', number_format($totalWeight, 0, ',', '.').' Kg')
                ->description("{$totalPkgs} packages total shipped")
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
        ];
    }
}
