<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\SuppliersRelationManager;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
        return $schema
            ->schema([
                Section::make('Basic Identification & Classification')
                    ->description('Master product catalog metadata, manufacturer brand, and hierarchy.')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU Code')
                            ->placeholder('e.g. SCH-ACTI9-C60N-1P-16A')
                            ->required()
                            ->maxLength(50)
                            ->unique(Product::class, 'sku', ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state))),

                        Forms\Components\TextInput::make('name')
                            ->label('Product Title / Commercial Description')
                            ->placeholder('e.g. Acti9 C60N MCB 1-Pole 16A 6kA Curve C')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('model_number')
                            ->label('Part / Model Number')
                            ->placeholder('e.g. A9F74116')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\Select::make('brand_id')
                            ->label('Brand Principal')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Product Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('uom')
                            ->label('Unit of Measure (UOM)')
                            ->default('pcs')
                            ->maxLength(20)
                            ->required(),

                        Forms\Components\TextInput::make('moq')
                            ->label('Minimum Order Qty (MOQ)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Procurement Sourcing & Costing Guard')
                    ->description('Primary supplier link, protected base HPP cost price, and stock status.')
                    ->schema([
                        Forms\Components\Select::make('primary_supplier_id')
                            ->label('Primary Sourcing Partner (Supplier)')
                            ->relationship('primarySupplier', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Preferred tier-1 distributor or partner supplying this product SKU.'),

                        Forms\Components\TextInput::make('cost_price')
                            ->label('Base HPP Cost Price')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0.00)
                            ->helperText('Protected internal modal cost price (Rule 3.3).')
                            ->visible(fn (): bool => auth()->user()?->can('view_cost_price') ?? false),

                        Forms\Components\Select::make('availability_status')
                            ->label('Stock Availability Status')
                            ->options([
                                'ready_stock' => 'Ready Stock',
                                'indent_principal' => 'Indent Lead-Time',
                                'discontinued' => 'Discontinued',
                            ])
                            ->default('ready_stock')
                            ->required(),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published to Public Web Catalog')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Product Showcase')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2),

                Section::make('Technical Specifications & Warranty')
                    ->description('Technical description and manufacturer warranty details.')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Technical Description & Features')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('warranty_period')
                            ->label('Warranty Period')
                            ->placeholder('e.g. 1 Year Official Principal Warranty')
                            ->maxLength(50)
                            ->columnSpanFull(),
                    ]),
            ]);
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

                TextColumn::make('primarySupplier.name')
                    ->label('Primary Vendor')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
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
                        'indent', 'indent_principal' => 'Indent Lead-Time',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock', 'ready_stock' => 'success',
                        'indent', 'indent_principal' => 'warning',
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
                SelectFilter::make('primarySupplier')
                    ->relationship('primarySupplier', 'name')
                    ->label('Primary Vendor'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SuppliersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
