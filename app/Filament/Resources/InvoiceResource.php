<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
