<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Filament\Resources\SalesOrderResource\Widgets\SalesOrderOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Sales Order')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesOrderOverviewWidget::class,
        ];
    }
}
