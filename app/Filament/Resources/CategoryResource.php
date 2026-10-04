<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Inventory';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-semibold text-slate-900']),

                TextColumn::make('code')
                    ->label('Code')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('parent.name')
                    ->label('Parent Division')
                    ->placeholder('Primary Division')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->placeholder('-'),

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
            'index' => Pages\ListCategories::route('/'),
        ];
    }
}
