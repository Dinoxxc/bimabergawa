<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use Filament\Widgets\ChartWidget;

class PipelineFunnelChartWidget extends ChartWidget
{
    protected ?string $heading = 'Sales Pipeline Funnel Velocity';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '275px';

    protected function getData(): array
    {
        $newRfq = Inquiry::where('status', 'new_rfq')->count();
        $review = Inquiry::where('status', 'review_sourcing')->count();
        $quoteDraft = Inquiry::where('status', 'quote_draft')->count();
        $negotiation = Inquiry::where('status', 'client_negotiation')->count();
        $deal = Inquiry::where('status', 'deal')->count();
        $lost = Inquiry::where('status', 'lost')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Active Deals / Leads',
                    'data' => [$newRfq, $review, $quoteDraft, $negotiation, $deal, $lost],
                    'backgroundColor' => [
                        'rgba(2, 132, 199, 0.85)',
                        'rgba(217, 119, 6, 0.85)',
                        'rgba(79, 70, 229, 0.85)',
                        'rgba(124, 58, 237, 0.85)',
                        'rgba(5, 150, 105, 0.85)',
                        'rgba(225, 29, 72, 0.85)',
                    ],
                    'borderColor' => [
                        '#0284c7',
                        '#d97706',
                        '#4f46e5',
                        '#7c3aed',
                        '#059669',
                        '#e11d48',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => [
                'New RFQ',
                'Review & Sourcing',
                'Quote Drafting',
                'Client Negotiation',
                'Closed Won (SO)',
                'Closed Lost',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
