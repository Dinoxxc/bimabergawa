<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Models\ActivityLog;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Services\DeliveryOrderFulfillmentService;
use App\Services\DocumentVerificationService;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Logistics & Delivery';

    protected static ?string $navigationLabel = 'Delivery Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Sales Order Reference & Recipient')
                ->schema([
                    Forms\Components\Select::make('sales_order_id')
                        ->label('Sales Order Reference')
                        ->options(function () {
                            return SalesOrder::whereIn('status', ['approved', 'processing', 'partially_fulfilled', 'ready_to_ship'])
                                ->pluck('order_number', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->disabledOn('edit')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                return;
                            }
                            $salesOrder = SalesOrder::with(['quotation.items.product', 'quotation.inquiry'])->find($state);
                            if (! $salesOrder) {
                                return;
                            }

                            $service = app(DeliveryOrderFulfillmentService::class);
                            $remaining = $service->getRemainingQuantities($salesOrder);

                            $items = [];
                            foreach ($remaining as $r) {
                                if ($r['remaining_qty'] > 0) {
                                    $items[] = [
                                        'quotation_item_id' => $r['quotation_item_id'],
                                        'product_id' => $r['product_id'],
                                        'item_name' => $r['item_name'],
                                        'model_number' => $r['model_number'],
                                        'remaining_qty' => $r['remaining_qty'],
                                        'quantity_shipped' => $r['remaining_qty'],
                                        'uom' => $r['uom'],
                                    ];
                                }
                            }

                            $set('recipient_pic_name', $salesOrder->quotation?->customer_name ?? $salesOrder->quotation?->inquiry?->pic_name ?? 'Client PIC');
                            $set('recipient_phone', $salesOrder->quotation?->customer_phone ?? $salesOrder->quotation?->inquiry?->company_phone ?? '-');
                            $set('shipping_address', $salesOrder->shipping_address ?? $salesOrder->quotation?->inquiry?->delivery_address ?? 'Project Delivery Address');
                            $set('items', $items);
                        })
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('recipient_pic_name')
                        ->label('Recipient PIC')
                        ->required(),

                    Forms\Components\TextInput::make('recipient_phone')
                        ->label('Recipient Phone')
                        ->required(),

                    Forms\Components\Textarea::make('shipping_address')
                        ->label('Destination Shipping Address')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(4),

            Section::make('Carrier & Fleet Logistics')
                ->schema([
                    Forms\Components\TextInput::make('courier_name')
                        ->label('Carrier / Courier Name')
                        ->default('Armada Internal PBB')
                        ->required(),

                    Forms\Components\TextInput::make('driver_name')
                        ->label('Driver Name'),

                    Forms\Components\TextInput::make('vehicle_plate_number')
                        ->label('Vehicle Plate #'),

                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Tracking / AWB #'),

                    Forms\Components\TextInput::make('total_packages')
                        ->label('Total Packages')
                        ->numeric()
                        ->default(1)
                        ->required(),

                    Forms\Components\TextInput::make('total_weight_kg')
                        ->label('Total Weight (Kg)')
                        ->numeric()
                        ->suffix('Kg'),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'prepared' => 'Prepared',
                            'shipped' => 'Shipped / In Transit',
                            'partially_delivered' => 'Partially Delivered',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('shipped')
                        ->disabledOn('create'),
                ])
                ->columns(4),

            Section::make('Physical Shipment Items (0% Price Leakage)')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Dispatched Items')
                        ->schema([
                            Forms\Components\Hidden::make('quotation_item_id'),
                            Forms\Components\Hidden::make('product_id'),

                            Forms\Components\TextInput::make('item_name')
                                ->label('Item Description')
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(4),

                            Forms\Components\TextInput::make('model_number')
                                ->label('Part Number / SKU')
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('remaining_qty')
                                ->label('Available')
                                ->readOnly()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity_shipped')
                                ->label('Qty to Dispatch')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('uom')
                                ->label('UOM')
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(1),
                        ])
                        ->columns(10)
                        ->addable(false)
                        ->deletable(true)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            Section::make('Delivery Verification & Return Notes')
                ->schema([
                    Forms\Components\FileUpload::make('proof_of_delivery_path')
                        ->label('Proof of Delivery (POD)')
                        ->disk('local_private')
                        ->directory('delivery_orders/pod')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpan(2)
                        ->visibleOn(['view', 'edit']),

                    Forms\Components\FileUpload::make('return_proof_path')
                        ->label('Return Proof (Damaged Goods)')
                        ->disk('local_private')
                        ->directory('delivery_orders/returns')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpan(2)
                        ->visible(fn (?DeliveryOrder $record): bool => (bool) ($record?->return_proof_path)),

                    Forms\Components\Textarea::make('discrepancy_notes')
                        ->label('Discrepancy Notes')
                        ->columnSpanFull()
                        ->visible(fn (?DeliveryOrder $record): bool => (bool) ($record?->discrepancy_notes)),

                    Forms\Components\Textarea::make('notes')
                        ->label('Logistics & Delivery Notes')
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->label('DO # (Surat Jalan)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('salesOrder.order_number')
                    ->label('SO Ref')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('courier_name')
                    ->label('Carrier / Expedition')
                    ->description(fn (DeliveryOrder $record): ?string => $record->driver_name ? "Driver: {$record->driver_name} ({$record->vehicle_plate_number})" : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tracking_number')
                    ->label('Waybill / AWB')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-500']),

                TextColumn::make('recipient_pic_name')
                    ->label('Recipient')
                    ->description(fn (DeliveryOrder $record): ?string => $record->recipient_phone)
                    ->searchable(),

                TextColumn::make('total_packages')
                    ->label('Packages')
                    ->alignment('center')
                    ->formatStateUsing(fn ($state): string => "{$state} Pkgs")
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'prepared' => 'Prepared',
                        'shipped' => 'In Transit',
                        'partially_delivered' => 'Partially Delivered',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'shipped' => 'info',
                        'prepared' => 'warning',
                        'partially_delivered' => 'warning',
                        'returned', 'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('shipped_at')
                    ->label('Dispatched')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'prepared' => 'Prepared',
                        'shipped' => 'In Transit',
                        'partially_delivered' => 'Partially Delivered',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (DeliveryOrder $record): bool => ! in_array($record->status, ['delivered', 'partially_delivered', 'cancelled'], true)),

                Action::make('dispatch_whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (DeliveryOrder $record): string {
                        $phone = preg_replace('/[^0-9]/', '', (string) ($record->recipient_phone ?? $record->salesOrder?->phone_wa ?? '08123456789'));
                        if (str_starts_with($phone, '0')) {
                            $phone = '62'.substr($phone, 1);
                        }
                        $verifyUrl = DocumentVerificationService::getVerificationUrl('delivery-order', $record->delivery_number, (string) $record->created_at);
                        $msg = rawurlencode("Halo {$record->recipient_name}, Surat Jalan No. {$record->delivery_number} telah diberangkatkan melalui kurir {$record->courier_name} (Resi: {$record->tracking_number}). Tautan verifikasi fisik: {$verifyUrl}");

                        ActivityLog::create([
                            'log_name' => 'whatsapp_dispatch_executed',
                            'description' => "WhatsApp delivery order dispatch executed for {$record->delivery_number} to {$phone}.",
                            'causer_type' => auth()->user()?->getMorphClass(),
                            'causer_id' => auth()->id(),
                            'subject_type' => $record->getMorphClass(),
                            'subject_id' => $record->id,
                            'properties' => ['phone' => $phone, 'document' => $record->delivery_number],
                        ]);

                        return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                    }, shouldOpenInNewTab: true),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (DeliveryOrder $record) {
                        $pdf = PdfExportService::generateDeliveryOrderPdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->delivery_number);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "DeliveryOrder-{$safeNumber}.pdf"
                        );
                    }),

                Action::make('confirm_delivery')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DeliveryOrder $record): bool => in_array($record->status, ['prepared', 'shipped'], true))
                    ->form([
                        Forms\Components\FileUpload::make('proof_of_delivery')
                            ->label('Proof of Delivery (Signed DO & Client Stamp)')
                            ->disk('local_private')
                            ->directory('delivery_orders/pod')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->required(),

                        Forms\Components\Toggle::make('has_discrepancy')
                            ->label('Record Discrepancy / Damaged Goods (Cacat / Hilang)')
                            ->default(false)
                            ->live(),

                        Forms\Components\Textarea::make('discrepancy_notes')
                            ->label('Discrepancy Notes')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy'))
                            ->required(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy')),

                        Forms\Components\FileUpload::make('return_proof')
                            ->label('Photo of Damaged Goods / Return Slip')
                            ->disk('local_private')
                            ->directory('delivery_orders/returns')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('has_discrepancy')),
                    ])
                    ->action(function (DeliveryOrder $record, array $data) {
                        $service = app(DeliveryOrderFulfillmentService::class);
                        $podPath = $data['proof_of_delivery'] ?? null;
                        $hasDiscrepancy = (bool) ($data['has_discrepancy'] ?? false);

                        if ($hasDiscrepancy) {
                            $discrepancyNotes = $data['discrepancy_notes'] ?? null;
                            $returnProofPath = $data['return_proof'] ?? null;

                            $itemDiscrepancies = [];
                            foreach ($record->items as $item) {
                                $itemDiscrepancies[] = [
                                    'delivery_order_item_id' => $item->id,
                                    'quantity_received' => $item->quantity_shipped,
                                    'quantity_defective' => 0,
                                    'defect_notes' => null,
                                ];
                            }

                            $service->confirmDeliveryWithDiscrepancy(
                                $record,
                                $itemDiscrepancies,
                                $podPath,
                                $returnProofPath,
                                $discrepancyNotes
                            );
                        } else {
                            $service->confirmDelivery($record, $podPath);
                        }

                        Notification::make()
                            ->title('Delivery Confirmed')
                            ->body("Delivery Order {$record->delivery_number} recorded as delivered.")
                            ->success()
                            ->send();
                    }),

                Action::make('cancel_do')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DeliveryOrder $record): bool => in_array($record->status, ['prepared', 'shipped'], true))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->action(function (DeliveryOrder $record, array $data) {
                        $service = app(DeliveryOrderFulfillmentService::class);
                        $service->cancelDeliveryOrder($record, $data['cancellation_reason']);

                        Notification::make()
                            ->title('Delivery Order Cancelled')
                            ->body("Delivery Order {$record->delivery_number} has been cancelled.")
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'view' => Pages\ViewDeliveryOrder::route('/{record}'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }
}
