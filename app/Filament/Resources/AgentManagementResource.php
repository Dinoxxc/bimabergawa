<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AgentManagementResource\Pages;
use App\Models\ActivityLog;
use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\User;
use App\Services\SecureFileUploadService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class AgentManagementResource extends Resource
{
    protected static ?string $model = FreelanceSales::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Freelance Partners';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Partner Personal & Professional Identity')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Legal Name')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('referral_code')
                            ->label('Referral Code')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('commission_tier_id')
                            ->label('Commission Tier')
                            ->options(fn () => CommissionTier::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Account Status')
                            ->options([
                                'pending_review' => 'Pending Verification',
                                'approved' => 'Approved / Active',
                                'revision_required' => 'Revision Required',
                                'rejected' => 'Rejected',
                                'suspended' => 'Suspended',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('industry_focus')
                            ->label('Industry / Market Segment')
                            ->maxLength(200),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection / Revision Reason')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('status'), ['rejected', 'revision_required'], true)),
                    ])
                    ->columns(2),

                Section::make('Encrypted Banking Destination (UU PDP No. 27/2022)')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Holder Name')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('npwp_number')
                            ->label('NPWP Tax Identification')
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Partner Name')
                    ->description(fn (FreelanceSales $record): string => $record->referral_code)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Login Email')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                Tables\Columns\TextColumn::make('tier.name')
                    ->label('Tier')
                    ->description(fn (FreelanceSales $record): ?string => $record->tier ? $record->tier->commission_rate.'%' : null)
                    ->placeholder('Unassigned'),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Disbursement Bank')
                    ->description(fn (FreelanceSales $record): string => $record->bank_account_name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_review' => 'Pending Verification',
                        'approved' => 'Active / Approved',
                        'revision_required' => 'Revision Needed',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending_review' => 'warning',
                        'rejected', 'suspended' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_review' => 'Pending Verification',
                        'approved' => 'Active / Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action: Approve Agent (TC-AGN-REG-004, TC-AGN-REG-013, TC-AGN-REG-016)
                Action::make('approve_agent')
                    ->label('Approve Partner')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (FreelanceSales $record): bool => in_array($record->status, ['pending_review', 'revision_required'], true))
                    ->form([
                        Forms\Components\Select::make('commission_tier_id')
                            ->label('Assign Commission Tier')
                            ->options(fn () => CommissionTier::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => CommissionTier::where('is_active', true)->first()?->id)
                            ->required(),
                    ])
                    ->action(function (FreelanceSales $record, array $data): void {
                        $tier = CommissionTier::findOrFail($data['commission_tier_id']);

                        $record->update([
                            'status' => 'approved',
                            'commission_tier_id' => $tier->id,
                            'approved_at' => now(),
                        ]);

                        // Ensure User exists and has Spatie role freelance_sales (TC-AGN-REG-013)
                        $user = $record->user;
                        if ($user) {
                            $role = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);
                            $user->assignRole($role);
                            $user->update(['freelance_sales_id' => $record->id, 'is_active' => true]);
                        }

                        // Audit Log (TC-AGN-REG-016)
                        ActivityLog::create([
                            'user_id' => auth()->id(),
                            'causer_type' => User::class,
                            'causer_id' => auth()->id(),
                            'subject_type' => FreelanceSales::class,
                            'subject_id' => $record->id,
                            'event' => 'agent_verified',
                            'description' => sprintf('Agent [%s] approved by Super Admin with tier [%s]', $record->name, $tier->name),
                            'properties' => [
                                'commission_rate' => $tier->commission_rate,
                                'tier_id' => $tier->id,
                                'approved_at' => now()->toIso8601String(),
                            ],
                        ]);

                        Notification::make()
                            ->title('Partner Approved')
                            ->body("Freelance partner {$record->name} successfully approved with {$tier->name}.")
                            ->success()
                            ->send();
                    }),

                // Action: Reject Agent (TC-AGN-REG-010)
                Action::make('reject_agent')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (FreelanceSales $record): bool => in_array($record->status, ['pending_review', 'revision_required'], true))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('e.g. Identity KTP image unreadable or banking name mismatch...'),
                    ])
                    ->action(function (FreelanceSales $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        ActivityLog::create([
                            'user_id' => auth()->id(),
                            'causer_type' => User::class,
                            'causer_id' => auth()->id(),
                            'subject_type' => FreelanceSales::class,
                            'subject_id' => $record->id,
                            'event' => 'agent_rejected',
                            'description' => sprintf('Agent [%s] rejected: %s', $record->name, $data['rejection_reason']),
                        ]);

                        Notification::make()
                            ->title('Partner Application Rejected')
                            ->body("Partner {$record->name} status set to rejected.")
                            ->warning()
                            ->send();
                    }),

                // Action: Preview KTP Document (TC-AGN-REG-006, TC-AGN-REG-011)
                Action::make('view_ktp')
                    ->label('KTP')
                    ->icon('heroicon-m-identification')
                    ->color('info')
                    ->visible(fn (FreelanceSales $record): bool => ! empty($record->ktp_file_path))
                    ->url(fn (FreelanceSales $record): string => SecureFileUploadService::generateTemporaryUrl($record->ktp_file_path))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentManagement::route('/'),
        ];
    }
}
