<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Models\DeliveryOrder;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Logistics & Delivery';

    protected static ?string $navigationLabel = 'Delivery Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->label('DO # (Surat Jalan)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('salesOrder.order_number')
                    ->label('SO Ref')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('courier_name')
                    ->label('Carrier / Expedition')
                    ->description(fn (DeliveryOrder $record): ?string => $record->driver_name ? "Driver: {$record->driver_name} ({$record->vehicle_plate_number})" : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tracking_number')
                    ->label('Waybill / AWB')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-500']),

                TextColumn::make('recipient_pic_name')
                    ->label('Recipient')
                    ->description(fn (DeliveryOrder $record): ?string => $record->recipient_phone)
                    ->searchable(),

                TextColumn::make('total_packages')
                    ->label('Packages')
                    ->alignment('center')
                    ->formatStateUsing(fn ($state): string => "{$state} Pkgs")
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'preparing' => 'Preparing',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'in_transit' => 'info',
                        'preparing' => 'warning',
                        'returned' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('shipped_at')
                    ->label('Dispatch Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'preparing' => 'Preparing',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryOrders::route('/'),
        ];
    }
}
