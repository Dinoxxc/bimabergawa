<?php

declare(strict_types=1);

namespace App\Filament\Resources\AgentManagementResource\Pages;

use App\Filament\Resources\AgentManagementResource;
use Filament\Resources\Pages\ListRecords;

class ListAgentManagement extends ListRecords
{
    protected static string $resource = AgentManagementResource::class;
}
