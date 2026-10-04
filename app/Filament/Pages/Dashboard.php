<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ChannelAttributionChartWidget;
use App\Filament\Widgets\ExecutiveStatsOverviewWidget;
use App\Filament\Widgets\PipelineFunnelChartWidget;
use App\Filament\Widgets\PrioritySlaAlertsTableWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Commercial Intelligence & Operations Overview';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            ExecutiveStatsOverviewWidget::class,
            PipelineFunnelChartWidget::class,
            ChannelAttributionChartWidget::class,
            PrioritySlaAlertsTableWidget::class,
        ];
    }
}
