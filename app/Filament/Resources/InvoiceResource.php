<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Services\DocumentVerificationService;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance & Commercial';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('faktur_pajak_number')
                    ->label('Faktur Pajak #')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-500']),

                TextColumn::make('company_name')
                    ->label('Billed Entity')
                    ->description(fn (Invoice $record): ?string => $record->customer_name)
                    ->searchable(['company_name', 'customer_name'])
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Invoice Amount')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                TextColumn::make('paid_amount')
                    ->label('Settled Amount')
                    ->money('IDR')
                    ->alignment('right')
                    ->extraAttributes(['class' => 'font-mono text-xs text-emerald-600']),

                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid in Full',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'issued', 'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid in Full',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (Invoice $record) {
                        $pdf = PdfExportService::generateInvoicePdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->invoice_number);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "Invoice-{$safeNumber}.pdf"
                        );
                    }),

                Action::make('dispatch_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (Invoice $record): string {
                        $phone = preg_replace('/[^0-9]/', '', (string) ($record->phone_wa ?? $record->salesOrder?->phone_wa ?? '08123456789'));
                        if (str_starts_with($phone, '0')) {
                            $phone = '62'.substr($phone, 1);
                        }
                        $verifyUrl = DocumentVerificationService::getVerificationUrl('invoice', $record->invoice_number, (string) $record->created_at);
                        $remaining = number_format((float) ($record->grand_total - $record->paid_amount), 0, ',', '.');
                        $dueDate = $record->due_date ? $record->due_date->format('d/m/Y') : '-';
                        $msg = rawurlencode("Kepada Yth. Keuangan {$record->company_name}, berikut Faktur Tagihan No. {$record->invoice_number} senilai Rp {$remaining} (Jatuh Tempo: {$dueDate}) dari PT. Panca Bima Bergawa. Tautan verifikasi digital: {$verifyUrl}");

                        ActivityLog::create([
                            'log_name' => 'whatsapp_dispatch_executed',
                            'description' => "WhatsApp invoice dispatch executed for {$record->invoice_number} to {$phone}.",
                            'causer_type' => auth()->user()?->getMorphClass(),
                            'causer_id' => auth()->id(),
                            'subject_type' => $record->getMorphClass(),
                            'subject_id' => $record->id,
                            'properties' => ['phone' => $phone, 'document' => $record->invoice_number],
                        ]);

                        return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                    }, shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
