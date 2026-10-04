<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Supplier;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SuppliersRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliers';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Sourcing Vendors & Purchase Matrix';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Vendor Partner')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->sortable(),

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
                    ->label('Sourcing Status')
                    ->formatStateUsing(fn ($state): string => $state ? 'Primary Sourcing' : 'Alternative')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('PIC Contact')
                    ->url(function (Supplier $record): ?string {
                        if (empty($record->phone)) {
                            return null;
                        }
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62'.substr($phone, 1);
                        }

                        return "https://wa.me/{$phone}";
                    }, shouldOpenInNewTab: true)
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->placeholder('-'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),

                        Forms\Components\TextInput::make('supplier_sku')
                            ->label('Vendor Catalog / Part Number')
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
                            ->helperText('0 = Ready Stock'),

                        Forms\Components\TextInput::make('min_order_qty')
                            ->label('Minimum Order Quantity (MOQ)')
                            ->numeric()
                            ->default(1),

                        Forms\Components\Toggle::make('is_primary')
                            ->label('Mark as Primary Sourcing Vendor')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Vendor Sourcing Notes')
                            ->maxLength(500),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('supplier_sku')
                            ->label('Vendor Catalog / Part Number')
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
                            ->label('Vendor Sourcing Notes')
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
