<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Widgets\ProductOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Product SKU')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductOverviewWidget::class,
        ];
    }
}
