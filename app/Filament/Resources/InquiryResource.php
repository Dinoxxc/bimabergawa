<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Filament\Resources\InquiryResource\RelationManagers;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\InquiryAssignmentService;
use App\Services\SecureFileUploadService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM & Sales';

    protected static ?string $navigationLabel = 'Inquiries';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Client & Project Information')
                    ->description('Prospect entity details and communication channels.')
                    ->schema([
                        Forms\Components\TextInput::make('inquiry_number')
                            ->label('Inquiry #')
                            ->default(fn () => DocumentNumberService::previewNext('INQ'))
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('phone_wa')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->label('Corporate Email')
                            ->email()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('project_location')
                            ->label('Project Location')
                            ->maxLength(200),
                        Forms\Components\Select::make('type')
                            ->label('Inquiry Type')
                            ->options([
                                'single_direct' => 'Single Direct',
                                'bulk_cart' => 'Bulk Cart',
                                'manual_offline' => 'Manual Offline',
                            ])
                            ->default('single_direct')
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Lead Source')
                            ->options([
                                'website_rfq' => 'Website RFQ',
                                'direct_whatsapp' => 'Direct WhatsApp',
                                'walk_in_phone' => 'Walk-in / Phone',
                                'exhibition' => 'Exhibition',
                                'referral' => 'Referral',
                            ])
                            ->default('website_rfq')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Pipeline & Assignment')
                    ->description('Current stage in the commercial conversion funnel.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Inquiry Status')
                            ->options([
                                'new' => 'New Lead',
                                'contacted' => 'Contacted',
                                'quote_draft' => 'Quote Draft',
                                'quote_sent' => 'Quote Sent',
                                'deal' => 'Deal / Won',
                                'lost' => 'Lost',
                            ])
                            ->default('new')
                            ->required(),
                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Sales PIC')
                            ->relationship('assignedUser', 'name', fn (Builder $query) => $query->role(['sales_pic', 'super_admin'])->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select active Sales PIC'),
                        Forms\Components\DateTimePicker::make('first_contacted_at')
                            ->label('First Contacted At')
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->label('Commercial / Technical Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('BOQ Project Document')
                    ->description('Attached bill of quantities or project specification file.')
                    ->schema([
                        Forms\Components\FileUpload::make('boq_attachment_path')
                            ->label('BOQ Document (PDF / XLSX)')
                            ->disk('local_private')
                            ->directory('inquiries/boq')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->maxSize(5120),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inquiry_number')
                    ->label('Inquiry #')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->limit(28),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Contact')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone_wa')
                    ->label('WhatsApp')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Sales PIC')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'quote_draft', 'quote_sent' => 'primary',
                        'deal' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('sla_aging')
                    ->label('SLA Aging')
                    ->state(fn (Inquiry $record): string => app(InquiryAssignmentService::class)->calculateSlaAging($record)['badge_short'])
                    ->badge()
                    ->color(fn (Inquiry $record): string => app(InquiryAssignmentService::class)->calculateSlaAging($record)['color']),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New Lead',
                        'contacted' => 'Contacted',
                        'quote_draft' => 'Quote Draft',
                        'quote_sent' => 'Quote Sent',
                        'deal' => 'Deal / Won',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Sales PIC')
                    ->relationship('assignedUser', 'name', fn (Builder $query) => $query->role(['sales_pic', 'super_admin'])->where('is_active', true)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                // Action: Assign Sales PIC (TC-ERP-CRM-006, TC-ERP-CRM-012)
                Action::make('assign_pic')
                    ->label('Assign PIC')
                    ->icon('heroicon-m-user-plus')
                    ->color('gray')
                    ->visible(fn (Inquiry $record): bool => auth()->user()?->can('assignPic', $record) ?? false)
                    ->form([
                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Select Sales PIC')
                            ->options(fn () => User::role(['sales_pic', 'super_admin'])->where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Inquiry $record, array $data, InquiryAssignmentService $service): void {
                        $targetUser = User::findOrFail($data['assigned_user_id']);
                        $service->assignSalesPic($record, $targetUser, auth()->user());

                        Notification::make()
                            ->title('PIC Assigned')
                            ->body("Inquiry {$record->inquiry_number} successfully assigned to {$targetUser->name}.")
                            ->success()
                            ->send();
                    }),

                // Action: Contact via WhatsApp & Update first_contacted_at
                Action::make('contact_wa')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->color('success')
                    ->url(function (Inquiry $record): string {
                        $cleanPhone = (string) preg_replace('/[^0-9]/', '', $record->phone_wa);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62'.substr($cleanPhone, 1);
                        }

                        return "https://wa.me/{$cleanPhone}";
                    })
                    ->openUrlInNewTab()
                    ->after(function (Inquiry $record, InquiryAssignmentService $service): void {
                        if ($record->status === 'new') {
                            $service->changeStatus($record, 'contacted', null, auth()->user());
                        }
                    }),

                // Action: Download BOQ Attachment via Temporary Signed URL (TC-ERP-CRM-007)
                Action::make('download_boq')
                    ->label('BOQ')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Inquiry $record): bool => ! empty($record->boq_attachment_path))
                    ->url(fn (Inquiry $record): string => SecureFileUploadService::generateTemporaryUrl($record->boq_attachment_path))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
                ]),
            ])
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'kanban' => Pages\ListInquiriesKanban::route('/kanban'),
            'create' => Pages\CreateInquiry::route('/create'),
            'view' => Pages\ViewInquiry::route('/{record}'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
