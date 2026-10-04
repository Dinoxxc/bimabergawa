<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionPayoutResource\Pages;
use App\Models\ActivityLog;
use App\Models\CommissionPayout;
use App\Models\User;
use App\Services\CommissionPayoutService;
use App\Services\PdfExportService;
use App\Services\SecureFileUploadService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

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
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('freelanceSales.name')
                    ->label('Agent Partner')
                    ->description(fn (CommissionPayout $record): ?string => $record->freelanceSales?->referral_code)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salesOrder.order_number')
                    ->label('SO Ref')
                    ->searchable()
                    ->placeholder('Payout Claim / Consolidated')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment('right')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-slate-900']),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'earned' => 'Deal Incentive',
                        'requested' => 'Payout Request',
                        'paid' => 'Settled Payout',
                        'deducted' => 'Reversal / Deduction',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'earned' => 'info',
                        'requested' => 'warning',
                        'paid' => 'success',
                        'deducted' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_payment' => 'Pending Verification',
                        'payable' => 'Approved / Ready to Pay',
                        'paid' => 'Transferred / Settled',
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

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->tooltip(fn (CommissionPayout $record): ?string => $record->notes)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'earned' => 'Deal Incentive',
                        'requested' => 'Payout Request',
                        'paid' => 'Settled Payout',
                        'deducted' => 'Reversal',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Pending Verification',
                        'payable' => 'Approved / Payable',
                        'paid' => 'Paid / Settled',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                // Action: Approve Payout (TC-AGN-COM-004, TC-AGN-COM-016)
                Action::make('approve_payout')
                    ->label('Approve')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (CommissionPayout $record): bool => $record->type === 'requested' && $record->status === 'pending_payment')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payout Request')
                    ->modalDescription(function (CommissionPayout $record): string {
                        $isDirectorReq = (float) $record->amount >= CommissionPayoutService::DEFAULT_DIRECTOR_PAYOUT_LIMIT;

                        return sprintf(
                            'Approve disbursement of Rp %s for %s.%s',
                            number_format((float) $record->amount, 0, ',', '.'),
                            $record->freelanceSales?->name ?? 'Partner',
                            $isDirectorReq ? ' (Amount >= Rp 25.000.000 requires Director privilege)' : ''
                        );
                    })
                    ->action(function (CommissionPayout $record, CommissionPayoutService $service): void {
                        $isDirector = auth()->user()?->hasRole('super_admin') ?? false;

                        try {
                            $updated = $service->approvePayout($record, auth()->id(), $isDirector);

                            if ($updated->status === 'payable') {
                                Notification::make()
                                    ->title('Payout Approved')
                                    ->body('Payout request has been approved and is ready for bank transfer.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Verified by Finance')
                                    ->body('Amount >= Rp 25.000.000. Escalated to Director for final authorization.')
                                    ->warning()
                                    ->send();
                            }
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Action: Reject Payout
                Action::make('reject_payout')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (CommissionPayout $record): bool => $record->type === 'requested' && in_array($record->status, ['pending_payment', 'payable'], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('e.g. Account number discrepancy, client payment dispute...'),
                    ])
                    ->action(function (CommissionPayout $record, array $data, CommissionPayoutService $service): void {
                        try {
                            $service->rejectPayout($record, $data['reason'], auth()->id());

                            Notification::make()
                                ->title('Payout Rejected')
                                ->body('Payout request was rejected. Reserved balance returned to partner.')
                                ->warning()
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Action Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Action: Mark as Paid / Record Transfer (TC-AGN-COM-005, TC-AGN-COM-013)
                Action::make('mark_as_paid')
                    ->label('Disburse / Transfer')
                    ->icon('heroicon-m-banknotes')
                    ->color('primary')
                    ->visible(fn (CommissionPayout $record): bool => $record->type === 'requested' && $record->status === 'payable')
                    ->form([
                        Forms\Components\FileUpload::make('transfer_proof')
                            ->label('Bank Transfer Proof Document')
                            ->disk('local')
                            ->directory('private/transfers')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Upload bank transfer receipt (PDF, JPG, PNG max 5 MB).'),

                        Forms\Components\TextInput::make('bank_ref_number')
                            ->label('Bank Reference / Trx ID')
                            ->placeholder('e.g. TRF-BCA-20261004-987123'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Settlement Notes')
                            ->rows(2),
                    ])
                    ->action(function (CommissionPayout $record, array $data, CommissionPayoutService $service): void {
                        try {
                            $notes = $record->notes ? $record->notes.' | ' : '';
                            $notes .= sprintf(
                                'Settled by Finance User ID [%d]%s%s',
                                auth()->id(),
                                ! empty($data['bank_ref_number']) ? ' Ref: '.$data['bank_ref_number'] : '',
                                ! empty($data['notes']) ? ' - '.$data['notes'] : ''
                            );

                            $service->markAsPaid(
                                payout: $record,
                                transferProofPath: $data['transfer_proof'],
                                notes: $notes
                            );

                            // Audit Log (TC-AGN-COM-013)
                            ActivityLog::create([
                                'user_id' => auth()->id(),
                                'causer_type' => User::class,
                                'causer_id' => auth()->id(),
                                'subject_type' => CommissionPayout::class,
                                'subject_id' => $record->id,
                                'event' => 'commission_payout_paid',
                                'description' => sprintf('Commission payout %s settled (Rp %s)', $record->payout_number, number_format((float) $record->amount, 0, ',', '.')),
                                'properties' => [
                                    'amount' => $record->amount,
                                    'bank_ref_number' => $data['bank_ref_number'] ?? null,
                                    'transfer_proof_path' => $data['transfer_proof'],
                                    'paid_at' => now()->toIso8601String(),
                                ],
                            ]);

                            Notification::make()
                                ->title('Payout Disbursed')
                                ->body("Payout {$record->payout_number} successfully recorded as settled.")
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Failed to Disburse')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Action: Download Voucher PDF (TC-AGN-COM-007)
                Action::make('download_voucher')
                    ->label('Voucher')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (CommissionPayout $record): bool => $record->status === 'paid')
                    ->action(function (CommissionPayout $record) {
                        $pdf = PdfExportService::generateCommissionVoucherPdf($record);
                        $safeNumber = str_replace(['/', '\\'], '-', $record->payout_number ?? 'COM-'.$record->id);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            "CommissionVoucher-{$safeNumber}.pdf"
                        );
                    }),

                // Action: Preview Transfer Proof
                Action::make('view_transfer_proof')
                    ->label('Proof')
                    ->icon('heroicon-m-paper-clip')
                    ->color('info')
                    ->visible(fn (CommissionPayout $record): bool => ! empty($record->transfer_proof_path))
                    ->url(fn (CommissionPayout $record): string => SecureFileUploadService::generateTemporaryUrl($record->transfer_proof_path))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionPayouts::route('/'),
        ];
    }
}
