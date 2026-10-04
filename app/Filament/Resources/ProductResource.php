<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Inventory';

    protected static ?string $navigationLabel = 'Products & SKUs';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('name')
                    ->label('Product Title')
                    ->description(fn (Product $record): ?string => $record->model_number ? "Model: {$record->model_number}" : null)
                    ->searchable(['name', 'model_number'])
                    ->sortable()
                    ->wrap(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('uom')
                    ->label('UOM')
                    ->alignment('center')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('availability_status')
                    ->label('Stock Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock', 'ready_stock' => 'Ready Stock',
                        'indent' => 'Indent Lead-Time',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock', 'ready_stock' => 'success',
                        'indent' => 'warning',
                        'out_of_stock', 'discontinued' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_published')
                    ->label('Live Web')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignment('center'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('brand')
                    ->relationship('brand', 'name'),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
        ];
    }
}
