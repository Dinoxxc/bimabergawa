<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Inventory';

    protected static ?string $navigationLabel = 'Brands & Principals';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Brand Principal')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-semibold text-slate-900']),

                TextColumn::make('code')
                    ->label('SKU Prefix')
                    ->placeholder(fn (Brand $record) => $record->getEffectiveCode())
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('description')
                    ->label('Scope & Line')
                    ->limit(50)
                    ->placeholder('-'),

                TextColumn::make('website_url')
                    ->label('Principal Portal')
                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'text-xs text-sky-600 underline']),

                IconColumn::make('is_active')
                    ->label('Active Partnership')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignment('center'),

                TextColumn::make('sort_order')
                    ->label('Display Order')
                    ->alignment('center')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
        ];
    }
}
