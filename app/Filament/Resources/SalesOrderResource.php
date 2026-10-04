<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\SalesOrder;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM & Sales';

    protected static ?string $navigationLabel = 'Sales Orders';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('SO #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('customer_po_number')
                    ->label('Client PO #')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('quotation.company_name')
                    ->label('Client Entity')
                    ->description(fn (SalesOrder $record): ?string => $record->project_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Value')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                IconColumn::make('is_approved_by_director')
                    ->label('Dir Approval')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->alignment('center'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'indent_principal' => 'Indent Principal',
                        'ready_to_ship' => 'Ready to Ship',
                        'shipped' => 'Dispatched / Shipped',
                        'partially_fulfilled', 'partially_delivered' => 'Partially Fulfilled',
                        'completed', 'fully_delivered' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed', 'ready_to_ship', 'completed', 'fully_delivered' => 'success',
                        'processing', 'shipped', 'partially_fulfilled', 'partially_delivered' => 'info',
                        'indent_principal' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'partially_delivered' => 'Partially Delivered',
                        'fully_delivered' => 'Delivered',
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
            'index' => Pages\ListSalesOrders::route('/'),
        ];
    }
}
