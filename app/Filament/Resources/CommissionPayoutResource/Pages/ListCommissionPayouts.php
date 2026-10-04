<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommissionPayoutResource\Pages;

use App\Filament\Resources\CommissionPayoutResource;
use App\Filament\Resources\CommissionPayoutResource\Widgets\CommissionPayoutOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPayouts extends ListRecords
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Process Payout')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CommissionPayoutOverviewWidget::class,
        ];
    }
}
