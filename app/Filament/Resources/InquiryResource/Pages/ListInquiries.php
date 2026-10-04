<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    protected static ?string $title = 'Inquiries (Table View)';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kanban_view')
                ->label('Kanban Board')
                ->icon('heroicon-o-view-columns')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('kanban')),
            Actions\CreateAction::make()
                ->label('New Inquiry')
                ->icon('heroicon-o-plus')
                ->color('gray'),
        ];
    }
}
