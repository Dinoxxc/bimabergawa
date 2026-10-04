<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Models\Quotation;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM & Sales';

    protected static ?string $navigationLabel = 'Quotations';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
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
                        'pending_director_approval', 'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'sent', 'sent_to_client' => 'Sent to Client',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'accepted' => 'success',
                        'pending_director_approval', 'pending_approval' => 'warning',
                        'rejected', 'expired' => 'danger',
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
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'sent_to_client' => 'Sent to Client',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
        ];
    }
}
