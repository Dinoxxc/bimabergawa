<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Brand;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSkus = Product::count();
        $publishedCount = Product::where('is_published', true)->count();
        $readyStock = Product::whereIn('availability_status', ['in_stock', 'ready_stock'])->count();
        $brandCount = Brand::where('is_active', true)->count();

        return [
            Stat::make('Master Catalog SKUs', (string) $totalSkus)
                ->description('Configured engineering items')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->chart([10, 12, 15, 18]),

            Stat::make('Live on bimabergawa.com', (string) $publishedCount)
                ->description('Zero-price public showcase active')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

            Stat::make('Ready Stock Items', (string) $readyStock)
                ->description('Available for immediate delivery')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Active Brand Principals', (string) $brandCount)
                ->description('Authorized OEM distributor lines')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),
        ];
    }
}
