<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionPayoutResource\Pages;
use App\Models\CommissionPayout;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommissionPayoutResource extends Resource
{
    protected static ?string $model = CommissionPayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance & Commercial';

    protected static ?string $navigationLabel = 'Commission Payouts';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payout_number')
                    ->label('Payout #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('freelanceSales.full_name')
                    ->label('Agent Partner')
                    ->description(fn (CommissionPayout $record): ?string => $record->freelanceSales?->agent_code)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salesOrder.order_number')
                    ->label('SO Ref')
                    ->searchable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('amount')
                    ->label('Claim Amount')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Deal Commission',
                        'bonus' => 'Performance Bonus',
                        'adjustment' => 'Adjustment',
                        default => ucfirst($state),
                    })
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'verified_finance' => 'Finance Verified',
                        'approved_director' => 'Director Approved',
                        'paid' => 'Settled / Paid',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'approved_director', 'verified_finance' => 'info',
                        'pending_review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'verified_finance' => 'Finance Verified',
                        'approved_director' => 'Director Approved',
                        'paid' => 'Settled / Paid',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionPayouts::route('/'),
        ];
    }
}
