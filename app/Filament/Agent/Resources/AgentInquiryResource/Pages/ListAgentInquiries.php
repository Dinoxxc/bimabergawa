<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources\AgentInquiryResource\Pages;

use App\Filament\Agent\Resources\AgentInquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentInquiries extends ListRecords
{
    protected static string $resource = AgentInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Submit New Inquiry')
                ->icon('heroicon-m-plus'),
        ];
    }
}
