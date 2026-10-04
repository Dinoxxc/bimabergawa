<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SuppliedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliedProducts';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Supplied Products & Sourcing Matrix';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('pivot.supplier_sku')
                    ->label('Vendor SKU')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pivot.purchase_price')
                    ->label('Purchase Price (HPP)')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.currency')
                    ->label('Cur')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pivot.lead_time_days')
                    ->label('Lead Time')
                    ->formatStateUsing(fn ($state): string => (int) $state === 0 ? 'Ready Stock' : "{$state} Days")
                    ->badge()
                    ->color(fn ($state): string => (int) $state === 0 ? 'success' : 'warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.min_order_qty')
                    ->label('MOQ')
                    ->alignment('center')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pivot.is_primary')
                    ->label('Sourcing Role')
                    ->formatStateUsing(fn ($state): string => $state ? 'Primary' : 'Alternative')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),

                        Forms\Components\TextInput::make('supplier_sku')
                            ->label('Supplier Part Number / SKU')
                            ->placeholder('e.g. SCH-ACTI9-001')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Purchase Price / Base HPP')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0.00)
                            ->required(),

                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'IDR' => 'IDR (Indonesian Rupiah)',
                                'USD' => 'USD (US Dollar)',
                                'EUR' => 'EUR (Euro)',
                            ])
                            ->default('IDR')
                            ->required(),

                        Forms\Components\TextInput::make('lead_time_days')
                            ->label('Lead Time (Days)')
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = Ready Stock at vendor warehouse'),

                        Forms\Components\TextInput::make('min_order_qty')
                            ->label('Minimum Order Quantity (MOQ)')
                            ->numeric()
                            ->default(1),

                        Forms\Components\Toggle::make('is_primary')
                            ->label('Mark as Primary Sourcing Vendor')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Procurement Notes')
                            ->maxLength(500),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('supplier_sku')
                            ->label('Supplier Part Number / SKU')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Purchase Price / Base HPP')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'IDR' => 'IDR (Indonesian Rupiah)',
                                'USD' => 'USD (US Dollar)',
                                'EUR' => 'EUR (Euro)',
                            ])
                            ->default('IDR')
                            ->required(),

                        Forms\Components\TextInput::make('lead_time_days')
                            ->label('Lead Time (Days)')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('min_order_qty')
                            ->label('Minimum Order Quantity (MOQ)')
                            ->numeric()
                            ->default(1),

                        Forms\Components\Toggle::make('is_primary')
                            ->label('Mark as Primary Sourcing Vendor'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Procurement Notes')
                            ->maxLength(500),
                    ]),

                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
