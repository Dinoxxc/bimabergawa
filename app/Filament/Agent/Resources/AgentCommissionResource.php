<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources;

use App\Filament\Agent\Resources\AgentCommissionResource\Pages;
use App\Models\CommissionPayout;
use App\Services\PdfExportService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentCommissionResource extends Resource
{
    protected static ?string $model = CommissionPayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial & Earnings';

    protected static ?string $navigationLabel = 'My Commission Ledger';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payout_number')
                    ->label('Ticket / Ref #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                Tables\Columns\TextColumn::make('salesOrder.order_number')
                    ->label('Sales Order Ref')
                    ->searchable()
                    ->placeholder('Consolidated / Payout')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal (IDR)')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                Tables\Columns\TextColumn::make('type')
                    ->label('Entry Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'earned' => 'Deal Incentive',
                        'requested' => 'Payout Request',
                        'paid' => 'Direct Payout',
                        'deducted' => 'Reversal / Deducted',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'earned' => 'info',
                        'requested' => 'warning',
                        'paid' => 'success',
                        'deducted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_payment' => 'Pending Verification',
                        'payable' => 'Ready to Withdraw',
                        'paid' => 'Disbursed / Paid',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'payable' => 'info',
                        'pending_payment' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Description & Reference')
                    ->limit(50)
                    ->tooltip(fn (CommissionPayout $record): ?string => $record->notes)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Filter Type')
                    ->options([
                        'earned' => 'Deal Incentive',
                        'requested' => 'Payout Request',
                        'paid' => 'Settled Payout',
                        'deducted' => 'Reversal / Deduction',
                    ]),
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'pending_payment' => 'Pending',
                        'payable' => 'Ready to Withdraw',
                        'paid' => 'Disbursed / Settled',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Action::make('download_voucher')
                    ->label('Voucher PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (CommissionPayout $record): bool => $record->status === 'paid')
                    ->action(function (CommissionPayout $record) {
                        $pdf = PdfExportService::generateCommissionVoucherPdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->payout_number ?? 'COM-'.$record->id);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "CommissionVoucher-{$safeNumber}.pdf"
                        );
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // AgentTenantScope is automatically attached on CommissionPayout model
        return parent::getEloquentQuery()
            ->with(['freelanceSales', 'salesOrder.quotation.inquiry']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentCommissions::route('/'),
        ];
    }
}
