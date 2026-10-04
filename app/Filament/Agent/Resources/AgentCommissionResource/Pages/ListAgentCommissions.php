<?php

declare(strict_types=1);

namespace App\Filament\Agent\Resources\AgentCommissionResource\Pages;

use App\Filament\Agent\Resources\AgentCommissionResource;
use App\Filament\Agent\Widgets\AgentCommissionOverviewWidget;
use App\Models\FreelanceSales;
use App\Services\CommissionPayoutService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use InvalidArgumentException;
use RuntimeException;

class ListAgentCommissions extends ListRecords
{
    protected static string $resource = AgentCommissionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AgentCommissionOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $agent = $user?->freelanceSales
            ?? FreelanceSales::where('user_id', $user?->id)->first();

        return [
            Action::make('request_payout')
                ->label('Request Commission Payout')
                ->icon('heroicon-m-banknotes')
                ->color('primary')
                ->modalHeading('Request Payout Disbursement')
                ->modalDescription(function () use ($agent) {
                    if (! $agent) {
                        return 'Partner profile not linked.';
                    }

                    return sprintf(
                        'Funds will be transferred to registered account: %s - %s (A.N. %s). Minimum payout is Rp 500.000.',
                        $agent->bank_name ?? 'BANK',
                        '****'.substr((string) ($agent->bank_account_number ?? ''), -4),
                        $agent->bank_account_name ?? $agent->name
                    );
                })
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Payout Amount')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(500000)
                        ->helperText('Minimum payout: Rp 500.000. Requests >= Rp 25.000.000 require Board of Directors approval.'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Beneficiary Notes / Reference (Optional)')
                        ->rows(2)
                        ->placeholder('Optional notes for Finance department...'),
                ])
                ->action(function (array $data, CommissionPayoutService $service) use ($agent): void {
                    if (! $agent) {
                        Notification::make()
                            ->title('Partner Profile Inactive')
                            ->body('Only approved freelance partners can request payout disbursements.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $payout = $service->requestPayout(
                            agentId: $agent->id,
                            amount: (float) $data['amount'],
                            notes: $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title('Payout Request Submitted')
                            ->body("Your payout ticket {$payout->payout_number} has been submitted for Finance verification.")
                            ->success()
                            ->send();
                    } catch (InvalidArgumentException|RuntimeException $e) {
                        Notification::make()
                            ->title('Request Rejected')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
