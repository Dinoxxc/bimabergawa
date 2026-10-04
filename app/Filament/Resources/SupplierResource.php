<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers\SuppliedProductsRelationManager;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Inventory';

    protected static ?string $navigationLabel = 'Suppliers & Vendors';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'sales_pic', 'finance']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'sales_pic']);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'sales_pic', 'finance']);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole('super_admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Vendor & Corporate Identity')
                    ->description('Official vendor legal entity and associated brand principal.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Vendor Company Name')
                            ->placeholder('e.g. PT. Schneider Prima Distributor')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\TextInput::make('code')
                            ->label('Vendor Code (Unique)')
                            ->placeholder('e.g. SUP-SCH')
                            ->required()
                            ->maxLength(50)
                            ->unique(Supplier::class, 'code', ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state))),

                        Forms\Components\Select::make('brand_id')
                            ->label('Brand Principal Partnership')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Select if vendor is an authorized distributor of a specific principal.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Cooperation Status')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Section::make('Contact PIC & Communication Channels')
                    ->description('Direct contact details for quotation, stock inquiries, and PO execution.')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('PIC / Sales Representative Name')
                            ->placeholder('e.g. Hendra Wijaya')
                            ->maxLength(150),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone / WhatsApp Number')
                            ->tel()
                            ->placeholder('e.g. 08119876543')
                            ->maxLength(50)
                            ->helperText('Enables one-click WhatsApp inquiries for stock and pricing.'),

                        Forms\Components\TextInput::make('email')
                            ->label('Purchasing / Quotation Email')
                            ->email()
                            ->placeholder('e.g. sales@vendor.co.id')
                            ->maxLength(150),
                    ])
                    ->columns(3),

                Section::make('Warehouse & Operational Logistics')
                    ->description('Physical origin address for pickup and shipping cost estimation.')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Warehouse / Head Office Address')
                            ->placeholder('Complete origin address including city and postal code')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('Commercial & Payment Terms')
                    ->description('Payment conditions and vendor discount structures.')
                    ->schema([
                        Forms\Components\Select::make('payment_terms')
                            ->label('Payment Terms')
                            ->options([
                                'CBD' => '100% Cash Before Delivery (CBD)',
                                'COD' => 'Cash on Delivery (COD)',
                                'Net 14' => 'Net 14 Days',
                                'Net 30' => 'Net 30 Days',
                                'Net 45' => 'Net 45 Days',
                                'Net 60' => 'Net 60 Days',
                            ])
                            ->default('Net 30')
                            ->searchable(),

                        Forms\Components\TextInput::make('discount_structure')
                            ->label('Principal Discount Structure')
                            ->placeholder('e.g. Pricelist - 35% + Project 5%')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Procurement & SLA Notes')
                            ->placeholder('Reliability assessment, lead time history, and warranty policies')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vendor Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->placeholder('Multi-Brand'),

                TextColumn::make('contact_person')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('phone')
                    ->label('Phone / WA')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                TextColumn::make('payment_terms')
                    ->label('Terms')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Standard'),

                TextColumn::make('products_count')
                    ->label('Primary SKUs')
                    ->counts('products')
                    ->alignment('center')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignment('center'),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->label('Brand Principal'),

                SelectFilter::make('payment_terms')
                    ->options([
                        'CBD' => 'Cash Before Delivery (CBD)',
                        'COD' => 'Cash on Delivery (COD)',
                        'Net 14' => 'Net 14 Days',
                        'Net 30' => 'Net 30 Days',
                        'Net 45' => 'Net 45 Days',
                        'Net 60' => 'Net 60 Days',
                    ])
                    ->label('Payment Terms'),

                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Supplier $record): bool => ! empty($record->phone))
                    ->url(function (Supplier $record): string {
                        $phone = preg_replace('/[^0-9]/', '', $record->phone ?? '');
                        if (str_starts_with($phone, '0')) {
                            $phone = '62'.substr($phone, 1);
                        }
                        $msg = rawurlencode("Halo {$record->contact_person}, kami dari PT. Panca Bima Bergawa ingin menanyakan ketersediaan stok & penawaran harga.");

                        return "https://wa.me/{$phone}?text={$msg}";
                    }, shouldOpenInNewTab: true),

                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SuppliedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
