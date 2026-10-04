<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Services\DocumentNumberService;
use Filament\Resources\Pages\CreateRecord;

class CreateInquiry extends CreateRecord
{
    protected static string $resource = InquiryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
