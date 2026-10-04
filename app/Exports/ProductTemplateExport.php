<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * Define the standard column headers for bulk catalog import.
     * (Rujukan: TC-CAT-IMP-001)
     */
    public function headings(): array
    {
        return [
            'brand_name',
            'category_name',
            'sku',
            'name',
            'base_hpp',
            'recommend_price',
            'unit',
            'description',
        ];
    }

    /**
     * Provide realistic industrial guide sample rows.
     */
    public function array(): array
    {
        return [
            [
                'Schneider Electric',
                'Electrical & Power',
                'EZC100F3100',
                'Schneider Electric MCCB Easypact EZC100F 3P 100A 18kA',
                450000,
                650000,
                'pcs',
                'Moulded Case Circuit Breaker 100A 3-Pole',
            ],
            [
                'Philips Lighting',
                'Industrial Lighting',
                'BY698P-LED200',
                'Philips GreenPerform Highbay G4 LED200/CW PSU WB',
                1250000,
                1750000,
                'unit',
                'Highbay LED Luminaire 200W Cool White',
            ],
            [
                'Kitz',
                'Mechanical & Piping',
                '10FCL-50',
                'Kitz Cast Iron Gate Valve 10K 2 Inch Flanged',
                850000,
                1150000,
                'pcs',
                'Cast iron gate valve class 10K 2 inch flanged JIS',
            ],
        ];
    }

    /**
     * Apply minimalist monochrome corporate styling for header row.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '18181B'],
                ],
            ],
        ];
    }
}
