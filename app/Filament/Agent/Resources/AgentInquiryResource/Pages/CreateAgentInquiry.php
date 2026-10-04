<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources\AgentInquiryResource\Pages;

use App\Filament\Agent\Resources\AgentInquiryResource;
use App\Models\FreelanceSales;
use App\Services\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateAgentInquiry extends CreateRecord
{
    protected static string $resource = AgentInquiryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Anti-Spoofing: Force agent assignment from verified session (TC-AGN-INQ-011)
        $agentId = $user->freelance_sales_id
            ?? FreelanceSales::where('user_id', $user->id)->value('id');

        $data['freelance_sales_id'] = $agentId;
        $data['source'] = 'referral';
        $data['status'] = 'new';

        if (empty($data['inquiry_number'])) {
            $data['inquiry_number'] = DocumentNumberService::generateNext('INQ');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
