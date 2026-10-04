<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrderResource\Widgets;

use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOrderOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOrders = SalesOrder::count();
        $totalAmount = (float) SalesOrder::sum('total_amount');
        $readyToShip = SalesOrder::where('status', 'ready_to_ship')->count();
        $approvedCount = SalesOrder::where('is_approved_by_director', true)->count();

        return [
            Stat::make('Active Sales Orders', (string) $totalOrders)
                ->description('Binding client purchase orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->chart([1, 2, 2, 3, 2]),

            Stat::make('Order Book Total', 'Rp '.number_format($totalAmount, 0, ',', '.'))
                ->description('Confirmed contract revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([271, 450, 600, 832]),

            Stat::make('Ready for Logistics', (string) $readyToShip)
                ->description('Awaiting Surat Jalan (DO) issuance')
                ->descriptionIcon('heroicon-m-truck')
                ->color($readyToShip > 0 ? 'warning' : 'gray'),

            Stat::make('Director Approval Gate', "{$approvedCount} / {$totalOrders}")
                ->description('Executive governance compliance')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
