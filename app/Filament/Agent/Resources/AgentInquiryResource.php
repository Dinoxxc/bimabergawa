<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources;

use App\Filament\Agent\Resources\AgentInquiryResource\Pages;
use App\Models\Inquiry;
use App\Services\DocumentNumberService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentInquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Deal Pipeline';

    protected static ?string $navigationLabel = 'My Inquiries';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Client & Project Information')
                    ->description('Provide your prospective client entity and project details.')
                    ->schema([
                        Forms\Components\TextInput::make('inquiry_number')
                            ->label('Inquiry #')
                            ->default(fn () => DocumentNumberService::previewNext('INQ'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(200)
                            ->placeholder('e.g. PT Mega Konstruksi Nusantara'),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('e.g. Budi Pratama'),

                        Forms\Components\TextInput::make('phone_wa')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. 081234567890'),

                        Forms\Components\TextInput::make('email')
                            ->label('Corporate Email')
                            ->email()
                            ->maxLength(150)
                            ->placeholder('e.g. procurement@megakonstruksi.com'),

                        Forms\Components\TextInput::make('project_location')
                            ->label('Project Location')
                            ->maxLength(200)
                            ->placeholder('e.g. Cikarang Industrial Estate, Bekasi'),

                        Forms\Components\Select::make('type')
                            ->label('Inquiry Scope')
                            ->options([
                                'single_direct' => 'Standard Item Request',
                                'bulk_cart' => 'Multi-SKU BOQ Project',
                                'manual_offline' => 'Ad-hoc Sourcing',
                            ])
                            ->default('bulk_cart')
                            ->required(),

                        Forms\Components\FileUpload::make('boq_attachment_path')
                            ->label('Project BOQ / SLD Specification Document')
                            ->disk('local')
                            ->directory('private/boq')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload project Bill of Quantities (PDF, XLSX, max 5 MB).'),
                    ])
                    ->columns(2),

                Section::make('Requested Product Items')
                    ->description('Specify required electrical, mechanical, or automation SKUs.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU / Catalog Part #')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g. EZC250F3200 / A9F74106'),

                                Forms\Components\TextInput::make('product_name')
                                    ->label('Item Description')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. MCCB Easypact 3P 200A 18kA'),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->default('PCS')
                                    ->required()
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('target_price')
                                    ->label('Client Target Budget (Optional)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->nullable()
                                    ->helperText('Target unit budget indicated by client (before VAT).'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->addActionLabel('Add Item to Inquiry'),
                    ]),

                Section::make('Commercial Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Project Context & Specifications')
                            ->rows(3)
                            ->placeholder('Specific technical requirements, delivery deadlines, brand preferences...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inquiry_number')
                    ->label('Inquiry #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Client Entity')
                    ->description(fn (Inquiry $record): string => $record->customer_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_wa')
                    ->label('Contact')
                    ->searchable()
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                Tables\Columns\TextColumn::make('status')
                    ->label('Pipeline Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Submitted / Lead',
                        'contacted' => 'In Qualification',
                        'quote_draft' => 'Quotation In Progress',
                        'quote_sent', 'quoted' => 'Quotation Issued',
                        'deal', 'won' => 'Deal Won / PO Received',
                        'lost' => 'Deal Closed / Lost',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted', 'quote_draft' => 'warning',
                        'quote_sent', 'quoted' => 'primary',
                        'deal', 'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->alignment('center')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Sales PIC')
                    ->placeholder('Pending Assignment')
                    ->description(fn (Inquiry $record): ?string => $record->assignedUser?->email),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => false), // Agents cannot bulk delete inquiries
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // AgentTenantScope is automatically attached on Inquiry model
        return parent::getEloquentQuery()
            ->with(['items', 'assignedUser', 'quotations']);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        // Inquiries can only be edited while in draft/new status. Once quoted/won, read-only (TC-AGN-INQ-008)
        return in_array($record->status, ['new', 'contacted'], true);
    }

    public static function canDelete(Model $record): bool
    {
        // Deletion disabled for in-progress inquiries (TC-AGN-INQ-009)
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentInquiries::route('/'),
            'create' => Pages\CreateAgentInquiry::route('/create'),
            'view' => Pages\ViewAgentInquiry::route('/{record}'),
        ];
    }
}
