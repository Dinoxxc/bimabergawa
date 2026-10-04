<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use App\Services\SalesOrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM & Sales';

    protected static ?string $navigationLabel = 'Sales Orders';

    protected static ?int $navigationSort = 3;

    public static function canEdit(Model $record): bool
    {
        /** @var SalesOrder $record */
        return ! in_array($record->status, ['completed', 'shipped', 'cancelled'], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Identification & Customer PO')
                    ->description('Link to confirmed quotation, customer PO reference, and project details.')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Sales Order #')
                            ->default(fn () => DocumentNumberService::previewNext('SOR'))
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'font-mono font-bold']),

                        Forms\Components\Select::make('quotation_id')
                            ->label('Approved Quotation Ref')
                            ->relationship('quotation', 'quotation_number', fn (Builder $query) => $query->whereIn('status', ['approved', 'won', 'accepted']))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $quotation = Quotation::find($state);
                                    if ($quotation) {
                                        $set('project_name', $quotation->project_name);
                                        $set('total_amount', $quotation->grand_total);
                                        $set('vat_rate_applied', $quotation->vat_rate_applied);
                                        $set('currency', $quotation->currency ?? 'IDR');
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('customer_po_number')
                            ->label('Client PO Number')
                            ->placeholder('e.g. PO-KTR-2026-098')
                            ->maxLength(100),

                        Forms\Components\DatePicker::make('customer_po_date')
                            ->label('Client PO Date')
                            ->default(now()),

                        Forms\Components\TextInput::make('project_name')
                            ->label('Project Title')
                            ->maxLength(200)
                            ->placeholder('e.g. Panel Assembly Project Phase 2'),

                        Forms\Components\FileUpload::make('customer_po_file_path')
                            ->label('Customer PO Document (PDF / Image)')
                            ->disk('local_private')
                            ->directory('sales_orders/po')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120),
                    ])
                    ->columns(2),

                Section::make('Commercial Values & Tax Snapshot')
                    ->description('Total financial values snapshotted from the approved quotation.')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Value (Inc. VAT)')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->extraAttributes(['class' => 'font-bold']),

                        Forms\Components\TextInput::make('vat_rate_applied')
                            ->label('Statutory VAT Applied (%)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->disabled()
                            ->dehydrated()
                            ->default('IDR'),
                    ])
                    ->columns(3),

                Section::make('Fulfillment & Director Approval Status')
                    ->description('Current stage in fulfillment pipeline and director exception tracking.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending_director_approval' => 'Pending Director Approval',
                                'approved' => 'Approved (Ready for Fulfillment)',
                                'processing' => 'Processing',
                                'indent_principal' => 'Indent Principal',
                                'ready_to_ship' => 'Ready to Ship',
                                'shipped' => 'Dispatched / Shipped',
                                'partially_fulfilled' => 'Partially Fulfilled',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('processing')
                            ->required(),

                        Forms\Components\Toggle::make('is_approved_by_director')
                            ->label('Director Approval Granted')
                            ->disabled(fn () => ! (auth()->user()?->hasRole('super_admin') ?? false))
                            ->helperText('Required for orders >= Rp 100 Million or critical margin transactions.'),

                        Forms\Components\Textarea::make('director_notes')
                            ->label('Director Approval Notes / Commercial Endorsement')
                            ->placeholder('Director exception justification notes.')
                            ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'sales_pic', 'finance']) ?? true)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->visible(fn (?SalesOrder $record) => $record && $record->status === 'cancelled')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('SO #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('customer_po_number')
                    ->label('Client PO #')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('quotation.company_name')
                    ->label('Client Entity')
                    ->description(fn (SalesOrder $record): ?string => $record->project_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Value')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                IconColumn::make('is_approved_by_director')
                    ->label('Dir Approval')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->alignment('center'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_director_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'draft' => 'Draft',
                        'processing' => 'Processing',
                        'indent_principal' => 'Indent Principal',
                        'ready_to_ship' => 'Ready to Ship',
                        'shipped' => 'Dispatched',
                        'partially_fulfilled' => 'Partially Fulfilled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'ready_to_ship', 'completed' => 'success',
                        'processing', 'shipped', 'partially_fulfilled' => 'info',
                        'pending_director_approval', 'indent_principal' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_director_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'processing' => 'Processing',
                        'indent_principal' => 'Indent Principal',
                        'ready_to_ship' => 'Ready to Ship',
                        'shipped' => 'Dispatched',
                        'partially_fulfilled' => 'Partially Fulfilled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (SalesOrder $record): bool => ! in_array($record->status, ['completed', 'shipped', 'cancelled'], true)),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (SalesOrder $record) {
                        $pdf = PdfExportService::generateSalesOrderPdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->order_number);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "SalesOrder-{$safeNumber}.pdf"
                        );
                    }),

                Action::make('approve_director')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (SalesOrder $record): bool => $record->status === 'pending_director_approval' && (auth()->user()?->hasRole('super_admin') ?? false))
                    ->form([
                        Forms\Components\Textarea::make('director_notes')
                            ->label('Director Approval Notes')
                            ->required(),
                    ])
                    ->action(function (SalesOrder $record, array $data) {
                        app(SalesOrderWorkflowService::class)->approveSalesOrder(
                            $record,
                            auth()->id(),
                            $data['director_notes']
                        );

                        Notification::make()
                            ->title('Sales Order Approved')
                            ->body("Sales Order {$record->order_number} approved by Director.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
