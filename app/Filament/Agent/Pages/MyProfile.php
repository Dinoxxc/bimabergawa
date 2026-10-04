<?php

declare(strict_types=1);

namespace App\Filament\Agent\Pages;

use App\Models\ActivityLog;
use App\Models\FreelanceSales;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Account Settings';

    protected static ?string $navigationLabel = 'Partner Profile & Banking';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.agent.pages.my-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $agent = $user?->freelanceSales
            ?? FreelanceSales::where('user_id', $user?->id)->first();

        if ($agent) {
            $this->form->fill([
                'name' => $agent->name,
                'referral_code' => $agent->referral_code,
                'tier_name' => $agent->tier?->name ?? 'Standard Partner',
                'commission_rate' => $agent->tier?->commission_rate ? $agent->tier->commission_rate.'%' : '2.50%',
                'phone' => $user->phone ?? '',
                'bank_name' => $agent->bank_name,
                'bank_account_number' => $agent->bank_account_number,
                'bank_account_name' => $agent->bank_account_name,
                'npwp_number' => $agent->npwp_number,
                'industry_focus' => $agent->industry_focus,
            ]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Commercial Partner Details')
                    ->description('Your registered corporate identity and partner credentials.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Legal Name')
                            ->disabled(),

                        Forms\Components\TextInput::make('referral_code')
                            ->label('Partner Referral Code')
                            ->disabled(),

                        Forms\Components\TextInput::make('tier_name')
                            ->label('Active Commission Tier')
                            ->disabled(),

                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Base Commission Rate')
                            ->disabled(),

                        Forms\Components\TextInput::make('phone')
                            ->label('WhatsApp / Phone Number')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('industry_focus')
                            ->label('Industry / Market Focus')
                            ->placeholder('e.g. MEP Contractors, Panel Makers, Manufacturing MRO'),
                    ])
                    ->columns(2),

                Section::make('Banking & Disbursement Destination')
                    ->description('Bank account for automated commission disbursements. Encrypted at-rest (UU PDP No. 27/2022).')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->placeholder('e.g. BCA, Bank Mandiri, BNI, BRI, CIMB')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->placeholder('e.g. 1234567890')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Holder Name')
                            ->placeholder('Must match KTP / Identity Document')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('npwp_number')
                            ->label('NPWP Tax ID (Optional)')
                            ->placeholder('e.g. 01.234.567.8-123.000'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $user = auth()->user();

        $agent = $user?->freelanceSales
            ?? FreelanceSales::where('user_id', $user?->id)->first();

        if (! $agent) {
            Notification::make()
                ->title('Profile not found')
                ->danger()
                ->send();

            return;
        }

        // Update User
        if (! empty($formData['phone'])) {
            $user->update(['phone' => $formData['phone']]);
        }

        // Update Agent Banking with encrypted at-rest casts (TC-AGN-REG-005)
        $agent->update([
            'bank_name' => $formData['bank_name'],
            'bank_account_number' => $formData['bank_account_number'],
            'bank_account_name' => $formData['bank_account_name'],
            'npwp_number' => $formData['npwp_number'] ?? $agent->npwp_number,
            'industry_focus' => $formData['industry_focus'] ?? $agent->industry_focus,
        ]);

        // Audit Log (TC-AGN-REG-005, TC-AGN-REG-016)
        ActivityLog::create([
            'user_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'subject_type' => get_class($agent),
            'subject_id' => $agent->id,
            'event' => 'agent_banking_updated',
            'description' => sprintf('Agent [%s] updated bank disbursement destination to %s', $agent->name, $formData['bank_name']),
            'properties' => [
                'bank_name' => $formData['bank_name'],
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        Notification::make()
            ->title('Profile Updated')
            ->body('Your banking information has been securely updated and encrypted.')
            ->success()
            ->send();
    }
}
