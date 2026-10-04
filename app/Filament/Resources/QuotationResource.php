<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use App\Services\SystemSettingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM & Sales';

    protected static ?string $navigationLabel = 'Quotations';

    protected static ?int $navigationSort = 2;

    public static function canEdit(Model $record): bool
    {
        /** @var Quotation $record */
        return ! in_array($record->status, ['approved', 'accepted', 'won', 'converted_to_so', 'superseded'], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Client & Quotation Reference')
                    ->description('Prospect entity details, validity duration, and currency.')
                    ->schema([
                        Forms\Components\TextInput::make('quotation_number')
                            ->label('Quotation #')
                            ->default(fn () => DocumentNumberService::previewNext('QUO'))
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'font-mono font-bold']),

                        Forms\Components\Select::make('inquiry_id')
                            ->label('Reference Inquiry')
                            ->relationship('inquiry', 'inquiry_number')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $inquiry = Inquiry::find($state);
                                    if ($inquiry) {
                                        $set('company_name', $inquiry->company_name);
                                        $set('customer_name', $inquiry->customer_name);
                                        $set('project_name', $inquiry->project_name ?? $inquiry->project_location ?? '');
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('project_name')
                            ->label('Project Title / Description')
                            ->maxLength(200)
                            ->placeholder('e.g. Panel Assembly Project Phase 2'),

                        Forms\Components\Select::make('delivery_terms')
                            ->label('Delivery Terms')
                            ->options([
                                'Loco Warehouse PBB Jakarta' => 'Loco Warehouse PBB Jakarta',
                                'Franco Project Site (Jabodetabek)' => 'Franco Project Site (Jabodetabek)',
                                'Franco Project Site (Java Island)' => 'Franco Project Site (Java Island)',
                                'FOB Tanjung Priok Port' => 'FOB Tanjung Priok Port',
                                'Ex-Works Manufacturer' => 'Ex-Works Manufacturer',
                            ])
                            ->default('Loco Warehouse PBB Jakarta')
                            ->required(),

                        Forms\Components\TextInput::make('validity_days')
                            ->label('Validity (Days)')
                            ->numeric()
                            ->default(14)
                            ->minValue(1)
                            ->maxValue(90)
                            ->required(),

                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                                'SGD' => 'SGD - Singapore Dollar',
                                'EUR' => 'EUR - Euro',
                            ])
                            ->default('IDR')
                            ->required(),

                        Forms\Components\TextInput::make('exchange_rate_applied')
                            ->label('Exchange Rate (to IDR)')
                            ->numeric()
                            ->default(1.0000)
                            ->step('0.0001')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Line Items & Specifications')
                    ->description('Add multi-brand industrial equipment line items with quantity and pricing.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product / Master SKU')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('item_name', $product->name);
                                                $set('model_number', $product->model_number);
                                                $set('uom', $product->uom ?? 'pcs');
                                                $set('cost_price', $product->cost_price ?? 0);
                                                $set('unit_price', $product->unit_price ?? $product->cost_price ?? 0);
                                                $set('lead_time', 'Ready Stock');
                                                $set('warranty_period', $product->warranty_period ?? '12 Months Official');

                                                $qty = max(1, (float) ($get('quantity') ?: 1));
                                                $unitPrice = (float) ($product->unit_price ?? 0);
                                                $disc = (float) ($get('discount_percent') ?: 0);
                                                $total = ($qty * $unitPrice) * (1 - ($disc / 100));
                                                $set('total_price', round($total, 2));
                                            }
                                        }
                                    })
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('item_name')
                                    ->label('Item Name / Description')
                                    ->required()
                                    ->columnSpan(5),

                                Forms\Components\TextInput::make('model_number')
                                    ->label('Part / Model #')
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $qty = max(1, (float) $state);
                                        $unitPrice = (float) ($get('unit_price') ?: 0);
                                        $disc = (float) ($get('discount_percent') ?: 0);
                                        $total = ($qty * $unitPrice) * (1 - ($disc / 100));
                                        $set('total_price', round($total, 2));
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('uom')
                                    ->label('UOM')
                                    ->default('pcs')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $qty = max(1, (float) ($get('quantity') ?: 1));
                                        $unitPrice = (float) $state;
                                        $disc = (float) ($get('discount_percent') ?: 0);
                                        $total = ($qty * $unitPrice) * (1 - ($disc / 100));
                                        $set('total_price', round($total, 2));
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Base HPP')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'sales_pic', 'finance']) ?? true)
                                    ->live(onBlur: true)
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Disc (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $qty = max(1, (float) ($get('quantity') ?: 1));
                                        $unitPrice = (float) ($get('unit_price') ?: 0);
                                        $disc = (float) $state;
                                        $total = ($qty * $unitPrice) * (1 - ($disc / 100));
                                        $set('total_price', round($total, 2));
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('lead_time')
                                    ->label('Lead Time')
                                    ->placeholder('e.g. Ready Stock / 2 Weeks')
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('warranty_period')
                                    ->label('Warranty')
                                    ->placeholder('e.g. 12 Months Official')
                                    ->columnSpan(5),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Add Quotation Item')
                            ->reorderableWithButtons()
                            ->collapsible(),
                    ]),

                Section::make('Commercial Calculation & Tax Snapshot')
                    ->description('Aggregated financial summary and statutory VAT application.')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal (Gross)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Global Discount')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0.00)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('dpp')
                            ->label('Net Tax Base (DPP)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),

                        Forms\Components\TextInput::make('vat_rate_applied')
                            ->label('Statutory VAT Rate (%)')
                            ->suffix('%')
                            ->default(fn () => SystemSettingService::getFloat('commercial_tax.tax_vat_rate', 11.00))
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('VAT / PPN Amount')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),

                        Forms\Components\TextInput::make('grand_total')
                            ->label('Grand Total (Inc. VAT)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00)
                            ->extraAttributes(['class' => 'font-bold']),
                    ])
                    ->columns(3),

                Section::make('Live Margin HPP Guard (Internal Confidential)')
                    ->description('Real-time margin evaluation against HPP base cost. Visible strictly to authorized commercial personnel.')
                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'sales_pic', 'finance']) ?? true)
                    ->schema([
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Total Base HPP (Modal Beli)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00),

                        Forms\Components\TextInput::make('gross_profit_margin')
                            ->label('Gross Margin (%)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->default(0.00)
                            ->helperText('Ideal: >= 15% (Safe) | 8% - 15% (Warning) | < 8% (Critical / Requires Director Approval)'),

                        Forms\Components\Placeholder::make('margin_status_badge')
                            ->label('Margin Health Tier')
                            ->content(function (?Quotation $record): string {
                                if (! $record) {
                                    return '[ New Quotation Draft ]';
                                }
                                $margin = (float) $record->gross_profit_margin;
                                if ($margin >= 15.0) {
                                    return '[ Safe / Ideal ] Direct dispatch permitted (Margin >= 15%).';
                                }
                                if ($margin >= 8.0) {
                                    return '[ Warning ] Thin margin (8% - 15%). Consider reviewing discount structure.';
                                }

                                return '[ Critical ] Margin < 8% or price below HPP. Director Approval required before dispatch.';
                            }),
                    ])
                    ->columns(3),

                Section::make('Approval & Commercial Workflow')
                    ->description('Review status, escalation flags, and executive director notes.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Quotation Status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_director_approval' => 'Pending Director Approval',
                                'approved' => 'Approved',
                                'sent' => 'Sent to Client',
                                'expired' => 'Expired',
                                'rejected' => 'Rejected',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Toggle::make('is_approved_by_director')
                            ->label('Director Approval Granted')
                            ->disabled(fn () => ! (auth()->user()?->hasRole('super_admin') ?? false))
                            ->helperText('Only Super Admin / Directors may grant exception approval for critical margin quotations.'),

                        Forms\Components\Textarea::make('director_notes')
                            ->label('Director Approval Notes / Commercial Exceptions')
                            ->placeholder('Commercial justification or reason for exception approval.')
                            ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'sales_pic', 'finance']) ?? true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('inquiry.inquiry_number')
                    ->label('Inq Ref')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-500']),

                TextColumn::make('company_name')
                    ->label('Client Entity')
                    ->description(fn (Quotation $record): ?string => $record->customer_name)
                    ->searchable(['company_name', 'customer_name'])
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Total (Inc. VAT)')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                TextColumn::make('gross_profit_margin')
                    ->label('Margin Guard')
                    ->alignment('center')
                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'sales_pic', 'finance']) ?? false)
                    ->formatStateUsing(function ($state): string {
                        $margin = (float) $state;

                        return number_format($margin, 1).'%';
                    })
                    ->badge()
                    ->color(function ($state): string {
                        $margin = (float) $state;
                        if ($margin >= 15.0) {
                            return 'success';
                        }
                        if ($margin >= 8.0) {
                            return 'warning';
                        }

                        return 'danger';
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending_director_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'sent', 'sent_to_client' => 'Sent to Client',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        'superseded' => 'Superseded',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'accepted' => 'success',
                        'pending_director_approval' => 'warning',
                        'rejected', 'expired', 'superseded' => 'danger',
                        'sent', 'sent_to_client' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('expires_at')
                    ->label('Valid Until')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_director_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'sent' => 'Sent to Client',
                        'expired' => 'Expired',
                        'rejected' => 'Rejected',
                        'superseded' => 'Superseded',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (Quotation $record): bool => ! in_array($record->status, ['approved', 'accepted', 'won', 'converted_to_so', 'superseded'], true)),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Quotation $record) {
                        $pdf = PdfExportService::generateQuotationPdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->quotation_number);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "Quotation-{$safeNumber}.pdf"
                        );
                    }),

                Action::make('approve_director')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Quotation $record) => $record->status === 'pending_director_approval' && (auth()->user()?->hasRole('super_admin') ?? false))
                    ->form([
                        Forms\Components\Textarea::make('director_notes')
                            ->label('Director Approval Notes / Justification')
                            ->required(),
                    ])
                    ->action(function (Quotation $record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'is_approved_by_director' => true,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'director_notes' => $data['director_notes'],
                        ]);

                        Notification::make()
                            ->title('Quotation Approved')
                            ->body("Quotation {$record->quotation_number} has been approved.")
                            ->success()
                            ->send();
                    }),

                Action::make('create_revision')
                    ->label('Revise')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Create Quotation Revision')
                    ->modalDescription('This will create a new draft quotation with an incremented revision number (e.g. -R1) and mark this quotation as superseded.')
                    ->action(function (Quotation $record) {
                        $newRevision = $record->revision_number + 1;
                        $docService = app(DocumentNumberService::class);
                        $revisedNumber = $docService->formatQuotationRevision($record->quotation_number, $newRevision);

                        $newQuotation = $record->replicate([
                            'id', 'created_at', 'updated_at', 'deleted_at', 'pdf_path',
                        ]);
                        $newQuotation->quotation_number = $revisedNumber;
                        $newQuotation->revision_number = $newRevision;
                        $newQuotation->status = 'draft';
                        $newQuotation->is_approved_by_director = false;
                        $newQuotation->approved_by = null;
                        $newQuotation->approved_at = null;
                        $newQuotation->director_notes = null;
                        $newQuotation->save();

                        foreach ($record->items as $item) {
                            $newItem = $item->replicate(['id', 'quotation_id', 'created_at', 'updated_at']);
                            $newItem->quotation_id = $newQuotation->id;
                            $newItem->save();
                        }

                        $record->status = 'superseded';
                        $record->save();

                        Notification::make()
                            ->title('Revision Created')
                            ->body("New revision {$revisedNumber} created successfully.")
                            ->success()
                            ->send();

                        return redirect(QuotationResource::getUrl('edit', ['record' => $newQuotation]));
                    })
                    ->visible(fn (Quotation $record) => in_array($record->status, ['approved', 'sent', 'sent_to_client', 'rejected', 'expired'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
