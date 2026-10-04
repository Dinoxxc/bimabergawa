<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalInvoiced = (float) Invoice::sum('grand_total');
        $cashCollected = (float) Invoice::sum('paid_amount');
        $outstandingAr = max(0, $totalInvoiced - $cashCollected);
        $overdueCount = Invoice::where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('due_date', '<', now())->whereNotIn('status', ['paid', 'cancelled']);
            })
            ->count();

        return [
            Stat::make('Total Invoiced', 'Rp '.number_format($totalInvoiced, 0, ',', '.'))
                ->description('Gross commercial billings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart([200, 271, 500, 832]),

            Stat::make('Cash Collected', 'Rp '.number_format($cashCollected, 0, ',', '.'))
                ->description('Settled bank transfer receipts')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([100, 200, 271, 371]),

            Stat::make('Outstanding AR', 'Rp '.number_format($outstandingAr, 0, ',', '.'))
                ->description('Accounts receivable in collection')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Overdue Invoices', (string) $overdueCount)
                ->description('Past due date collection alerts')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($overdueCount > 0 ? 'danger' : 'success'),
        ];
    }
}
