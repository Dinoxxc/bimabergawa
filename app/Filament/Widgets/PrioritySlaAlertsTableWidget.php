<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\InquiryResource;
use App\Models\Inquiry;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PrioritySlaAlertsTableWidget extends TableWidget
{
    protected static ?string $heading = 'Priority Inquiries & SLA Aging Watchlist';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Inquiry::query()
                    ->whereIn('status', ['new_rfq', 'review_sourcing', 'quote_draft', 'client_negotiation'])
                    ->latest()
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('inquiry_number')
                    ->label('Inquiry #')
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('company_name')
                    ->label('Client Entity')
                    ->description(fn (Inquiry $record): string => (string) $record->customer_name)
                    ->sortable(),

                TextColumn::make('phone_wa')
                    ->label('WhatsApp Channel')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('salesPic.name')
                    ->label('Sales PIC')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'warning'),

                TextColumn::make('status')
                    ->label('Pipeline Stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new_rfq' => 'New RFQ',
                        'review_sourcing' => 'Review & Sourcing',
                        'quote_draft' => 'Quote Drafting',
                        'client_negotiation' => 'Negotiation',
                        'deal' => 'Closed Won',
                        'lost' => 'Closed Lost',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new_rfq' => 'sky',
                        'review_sourcing' => 'warning',
                        'quote_draft' => 'info',
                        'client_negotiation' => 'purple',
                        'deal' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('SLA Aging Status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $diffInHours = (int) $state->diffInHours(now());
                        if ($diffInHours >= 48) {
                            return "{$diffInHours}h [Critical Stale]";
                        }
                        if ($diffInHours >= 24) {
                            return "{$diffInHours}h [Warning SLA]";
                        }

                        return "{$diffInHours}h [Normal SLA]";
                    })
                    ->color(function ($state): string {
                        $diffInHours = (int) $state->diffInHours(now());
                        if ($diffInHours >= 48) {
                            return 'danger';
                        }
                        if ($diffInHours >= 24) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('Open RFQ')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Inquiry $record): string => InquiryResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
