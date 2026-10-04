<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ManageSystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Commercial & System Settings';

    protected static ?string $title = 'Commercial & System Settings';

    protected static ?string $slug = 'system-settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-system-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * RBAC Access Matrix: Only authorized internal staff can access.
     * Freelance sales agents (mitra) and guests are strictly blocked.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'finance', 'sales_pic', 'logistics']);
    }

    public function mount(): void
    {
        $this->form->fill([
            // Tab 1: General & Identity
            'general_company_name' => SystemSettingService::getString('general.company_name', 'PT. Panca Bima Bergawa'),
            'general_company_tagline' => SystemSettingService::getString('general.company_tagline', 'One-Stop B2B Industrial General Supplier & Mitra Pengadaan Pabrik'),
            'general_company_address' => SystemSettingService::getString('general.company_address', 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920'),
            'general_contact_phone' => SystemSettingService::getString('general.contact_phone', '+62 21 5292 0000'),
            'general_contact_whatsapp' => SystemSettingService::getString('general.contact_whatsapp', '6281112345678'),
            'general_contact_email' => SystemSettingService::getString('general.contact_email', 'sales@bimabergawa.com'),
            'general_default_timezone' => SystemSettingService::getString('general.default_timezone', 'Asia/Jakarta'),
            'general_date_format' => SystemSettingService::getString('general.date_format', 'd/m/Y'),

            // Tab 2: Commercial & Tax
            'commercial_tax_tax_vat_rate' => SystemSettingService::getFloat('commercial_tax.tax_vat_rate', 11.00),
            'commercial_tax_critical_margin_threshold' => SystemSettingService::getFloat('commercial_tax.critical_margin_threshold', 8.00),
            'commercial_tax_warning_margin_threshold' => SystemSettingService::getFloat('commercial_tax.warning_margin_threshold', 15.00),
            'commercial_tax_director_approval_limit' => SystemSettingService::getFloat('commercial_tax.director_approval_limit', 100000000.00),
            'commercial_tax_quote_validity_days' => SystemSettingService::getInt('commercial_tax.quote_validity_days', 14),
            'commercial_tax_company_npwp' => SystemSettingService::getString('commercial_tax.company_npwp', '01.890.123.4-012.000'),
            'commercial_tax_company_bank_name' => SystemSettingService::getString('commercial_tax.company_bank_name', 'Bank Central Asia (BCA)'),
            'commercial_tax_company_bank_account_number' => SystemSettingService::getString('commercial_tax.company_bank_account_number', '500-123-4567'),
            'commercial_tax_company_bank_account_name' => SystemSettingService::getString('commercial_tax.company_bank_account_name', 'PT. Panca Bima Bergawa'),

            // Tab 3: Logistics & Warehousing
            'logistics_default_warehouse_origin' => SystemSettingService::getString('logistics.default_warehouse_origin', 'Gudang Pusat Rasuna Said, Jakarta Selatan'),
            'logistics_authorized_couriers' => SystemSettingService::getString('logistics.authorized_couriers', 'Dakota Cargo, Baraka Sarana Tama, JNE Trucking, Kurir Internal PBB'),

            // Tab 4: CRM & Sales
            'crm_sales_inquiry_stale_hours' => SystemSettingService::getInt('crm_sales.inquiry_stale_hours', 24),
            'crm_sales_whatsapp_quotation_template' => SystemSettingService::getString('crm_sales.whatsapp_quotation_template', 'Halo {pic_name}, terima kasih telah menghubungi PT. Panca Bima Bergawa. Berikut kami lampirkan dokumen penawaran resmi {quote_number}.'),

            // Tab 5: Commission & Freelance Partners
            'commission_minimum_payout_amount' => SystemSettingService::getFloat('commission_tiers.minimum_payout_amount', 500000.00),
            'commission_director_payout_approval_limit' => SystemSettingService::getFloat('commission_tiers.director_payout_approval_limit', 10000000.00),

            // Tab 6: System & Maintenance
            'maintenance_mode_public' => SystemSettingService::getBool('system_maintenance.maintenance_mode_public', false),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;
        $isFinance = $user?->hasRole('finance') ?? false;
        $isSales = $user?->hasRole('sales_pic') ?? false;
        $isLogistics = $user?->hasRole('logistics') ?? false;

        return $schema
            ->schema([
                Tabs::make('SettingsTabs')
                    ->tabs([
                        // Tab 1: General & Company Identity
                        Tab::make('General & Identity')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Official Legal Identity & Contact Details')
                                    ->description('Corporate identity shown on official ISO A4 PDF letterheads and corporate correspondence.')
                                    ->schema([
                                        Forms\Components\TextInput::make('general_company_name')
                                            ->label('Company Legal Name')
                                            ->required()
                                            ->disabled(! $isSuperAdmin),

                                        Forms\Components\TextInput::make('general_company_tagline')
                                            ->label('Company Tagline / Subtitle')
                                            ->disabled(! $isSuperAdmin),

                                        Forms\Components\Textarea::make('general_company_address')
                                            ->label('Headquarters Physical Address')
                                            ->rows(3)
                                            ->required()
                                            ->disabled(! $isSuperAdmin)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('general_contact_phone')
                                            ->label('Corporate Phone')
                                            ->disabled(! $isSuperAdmin),

                                        Forms\Components\TextInput::make('general_contact_whatsapp')
                                            ->label('Official WhatsApp Hotline')
                                            ->disabled(! ($isSuperAdmin || $isSales)),

                                        Forms\Components\TextInput::make('general_contact_email')
                                            ->label('Official Email Address')
                                            ->email()
                                            ->disabled(! $isSuperAdmin),

                                        Forms\Components\Select::make('general_default_timezone')
                                            ->label('System Timezone')
                                            ->options([
                                                'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                                                'Asia/Makassar' => 'Asia/Makassar (WITA)',
                                                'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                                            ])
                                            ->disabled(! $isSuperAdmin),

                                        Forms\Components\Select::make('general_date_format')
                                            ->label('Date Display Format')
                                            ->options([
                                                'd/m/Y' => 'DD/MM/YYYY (e.g. 04/10/2026)',
                                                'd F Y' => 'DD Month YYYY (e.g. 04 October 2026)',
                                                'Y-m-d' => 'ISO Standard (YYYY-MM-DD)',
                                            ])
                                            ->disabled(! $isSuperAdmin),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Commercial & Tax (HPP & Margin confidential - only Super Admin & Finance)
                        Tab::make('Commercial & Tax')
                            ->icon('heroicon-o-banknotes')
                            ->visible($isSuperAdmin || $isFinance)
                            ->schema([
                                Section::make('Dynamic National VAT / PPN (UU HPP & UU KUP Compliance)')
                                    ->description('Source of truth for instant snapshot VAT calculation applied on new quotations, sales orders, and invoices.')
                                    ->schema([
                                        Forms\Components\TextInput::make('commercial_tax_tax_vat_rate')
                                            ->label('Active National VAT Rate (%)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->required()
                                            ->helperText('Changing this rate takes effect immediately for newly generated transactions without distorting past audited records.'),

                                        Forms\Components\TextInput::make('commercial_tax_quote_validity_days')
                                            ->label('Quotation Validity Period')
                                            ->numeric()
                                            ->suffix('Days')
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make('Live Margin HPP Guard & Approval Thresholds')
                                    ->description('Configurable gross profit margin gates and Director escalation triggers.')
                                    ->schema([
                                        Forms\Components\TextInput::make('commercial_tax_critical_margin_threshold')
                                            ->label('Critical Margin Gate (%)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->required()
                                            ->helperText('Quotations with margin below this threshold lock quotation issuance and require Director approval.'),

                                        Forms\Components\TextInput::make('commercial_tax_warning_margin_threshold')
                                            ->label('Thin Margin Warning Gate (%)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->required()
                                            ->helperText('Quotations with margin between critical and warning gates display an amber badge alert.'),

                                        Forms\Components\TextInput::make('commercial_tax_director_approval_limit')
                                            ->label('Sales Order Director Approval Limit')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->helperText('Sales Orders with total value exceeding this amount require Director Sign-Off.'),
                                    ])
                                    ->columns(3),

                                Section::make('Corporate Treasury & Settlement Accounts')
                                    ->description('Official corporate bank accounts printed on quotation and invoice payment instructions.')
                                    ->schema([
                                        Forms\Components\TextInput::make('commercial_tax_company_npwp')
                                            ->label('Corporate NPWP Number'),

                                        Forms\Components\TextInput::make('commercial_tax_company_bank_name')
                                            ->label('Settlement Bank Name'),

                                        Forms\Components\TextInput::make('commercial_tax_company_bank_account_number')
                                            ->label('Corporate Account Number'),

                                        Forms\Components\TextInput::make('commercial_tax_company_bank_account_name')
                                            ->label('Account Holder Name'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 3: Logistics & Warehousing
                        Tab::make('Logistics & Delivery')
                            ->icon('heroicon-o-truck')
                            ->visible($isSuperAdmin || $isLogistics)
                            ->schema([
                                Section::make('Fulfillment & Dispatch Parameters')
                                    ->schema([
                                        Forms\Components\TextInput::make('logistics_default_warehouse_origin')
                                            ->label('Default Central Warehouse Location')
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('logistics_authorized_couriers')
                                            ->label('Authorized Couriers & Freight Partners')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Comma-separated list of recognized freight and delivery fleets.'),
                                    ]),
                            ]),

                        // Tab 4: CRM & Sales SLA
                        Tab::make('CRM & Sales')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->visible($isSuperAdmin || $isSales)
                            ->schema([
                                Section::make('Lead Pipeline SLA & Direct Dispatch')
                                    ->schema([
                                        Forms\Components\TextInput::make('crm_sales_inquiry_stale_hours')
                                            ->label('Inquiry Stale SLA Threshold (Hours)')
                                            ->numeric()
                                            ->suffix('Hours')
                                            ->required()
                                            ->helperText('Leads untouched past this duration trigger yellow warning badges in the CRM Kanban view.'),

                                        Forms\Components\Textarea::make('crm_sales_whatsapp_quotation_template')
                                            ->label('WhatsApp Quotation Dispatch Message Template')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Placeholders available: {pic_name}, {quote_number}, {company_name}.'),
                                    ]),
                            ]),

                        // Tab 5: Commission & Partners
                        Tab::make('Partner Commissions')
                            ->icon('heroicon-o-currency-dollar')
                            ->visible($isSuperAdmin || $isFinance)
                            ->schema([
                                Section::make('Freelance Agent Disbursement Parameters')
                                    ->schema([
                                        Forms\Components\TextInput::make('commission_minimum_payout_amount')
                                            ->label('Minimum Commission Claim Amount')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(),

                                        Forms\Components\TextInput::make('commission_director_payout_approval_limit')
                                            ->label('Payout Threshold Requiring Director Approval')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 6: System & Maintenance (Super Admin Exclusive)
                        Tab::make('System Maintenance')
                            ->icon('heroicon-o-shield-check')
                            ->visible($isSuperAdmin)
                            ->schema([
                                Section::make('Architecture Controls & Maintenance Flags')
                                    ->schema([
                                        Forms\Components\Toggle::make('maintenance_mode_public')
                                            ->label('Public Website Maintenance Mode')
                                            ->helperText('When enabled, visitors to bimabergawa.com see a corporate maintenance notice while ERP staff access remains uninterrupted.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $user = auth()->user();

        if (! $user || ! static::canAccess()) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You do not have permission to modify system settings.')
                ->danger()
                ->send();

            return;
        }

        // Mapping from form keys to database keys, groups, and types
        $keyDefinitions = [
            'general_company_name' => ['key' => 'general.company_name', 'group' => 'general', 'type' => 'string'],
            'general_company_tagline' => ['key' => 'general.company_tagline', 'group' => 'general', 'type' => 'string'],
            'general_company_address' => ['key' => 'general.company_address', 'group' => 'general', 'type' => 'text'],
            'general_contact_phone' => ['key' => 'general.contact_phone', 'group' => 'general', 'type' => 'string'],
            'general_contact_whatsapp' => ['key' => 'general.contact_whatsapp', 'group' => 'general', 'type' => 'string'],
            'general_contact_email' => ['key' => 'general.contact_email', 'group' => 'general', 'type' => 'string'],
            'general_default_timezone' => ['key' => 'general.default_timezone', 'group' => 'general', 'type' => 'string'],
            'general_date_format' => ['key' => 'general.date_format', 'group' => 'general', 'type' => 'string'],

            'commercial_tax_tax_vat_rate' => ['key' => 'commercial_tax.tax_vat_rate', 'group' => 'commercial_tax', 'type' => 'decimal'],
            'commercial_tax_critical_margin_threshold' => ['key' => 'commercial_tax.critical_margin_threshold', 'group' => 'commercial_tax', 'type' => 'decimal'],
            'commercial_tax_warning_margin_threshold' => ['key' => 'commercial_tax.warning_margin_threshold', 'group' => 'commercial_tax', 'type' => 'decimal'],
            'commercial_tax_director_approval_limit' => ['key' => 'commercial_tax.director_approval_limit', 'group' => 'commercial_tax', 'type' => 'decimal'],
            'commercial_tax_quote_validity_days' => ['key' => 'commercial_tax.quote_validity_days', 'group' => 'commercial_tax', 'type' => 'integer'],
            'commercial_tax_company_npwp' => ['key' => 'commercial_tax.company_npwp', 'group' => 'commercial_tax', 'type' => 'string'],
            'commercial_tax_company_bank_name' => ['key' => 'commercial_tax.company_bank_name', 'group' => 'commercial_tax', 'type' => 'string'],
            'commercial_tax_company_bank_account_number' => ['key' => 'commercial_tax.company_bank_account_number', 'group' => 'commercial_tax', 'type' => 'string'],
            'commercial_tax_company_bank_account_name' => ['key' => 'commercial_tax.company_bank_account_name', 'group' => 'commercial_tax', 'type' => 'string'],

            'logistics_default_warehouse_origin' => ['key' => 'logistics.default_warehouse_origin', 'group' => 'logistics', 'type' => 'string'],
            'logistics_authorized_couriers' => ['key' => 'logistics.authorized_couriers', 'group' => 'logistics', 'type' => 'string'],

            'crm_sales_inquiry_stale_hours' => ['key' => 'crm_sales.inquiry_stale_hours', 'group' => 'crm_sales', 'type' => 'integer'],
            'crm_sales_whatsapp_quotation_template' => ['key' => 'crm_sales.whatsapp_quotation_template', 'group' => 'crm_sales', 'type' => 'text'],

            'commission_minimum_payout_amount' => ['key' => 'commission_tiers.minimum_payout_amount', 'group' => 'commission_tiers', 'type' => 'decimal'],
            'commission_director_payout_approval_limit' => ['key' => 'commission_tiers.director_payout_approval_limit', 'group' => 'commission_tiers', 'type' => 'decimal'],

            'maintenance_mode_public' => ['key' => 'system_maintenance.maintenance_mode_public', 'group' => 'system_maintenance', 'type' => 'boolean'],
        ];

        $oldValues = [];
        $newValues = [];
        $changedKeys = [];

        foreach ($formData as $formKey => $val) {
            if (! isset($keyDefinitions[$formKey])) {
                continue;
            }

            $def = $keyDefinitions[$formKey];
            $dbKey = $def['key'];
            $group = $def['group'];
            $type = $def['type'];

            // Role access guard when saving
            if ($group === 'general' && ! $user->hasRole('super_admin')) {
                continue;
            }
            if ($group === 'commercial_tax' && ! $user->hasAnyRole(['super_admin', 'finance'])) {
                continue;
            }
            if ($group === 'logistics' && ! $user->hasAnyRole(['super_admin', 'logistics'])) {
                continue;
            }
            if ($group === 'crm_sales' && ! $user->hasAnyRole(['super_admin', 'sales_pic'])) {
                continue;
            }
            if ($group === 'commission_tiers' && ! $user->hasAnyRole(['super_admin', 'finance'])) {
                continue;
            }
            if ($group === 'system_maintenance' && ! $user->hasRole('super_admin')) {
                continue;
            }

            $oldRecord = SystemSetting::where('key', $dbKey)->first();
            $oldVal = $oldRecord ? $oldRecord->value : null;

            $stringVal = match ($type) {
                'boolean' => $val ? '1' : '0',
                'decimal' => is_numeric($val) ? number_format((float) $val, 2, '.', '') : (string) ($val ?? ''),
                default => (string) ($val ?? ''),
            };

            if ($oldVal !== $stringVal) {
                $oldValues[$dbKey] = $oldVal;
                $newValues[$dbKey] = $stringVal;
                $changedKeys[] = $dbKey;

                SystemSetting::updateOrCreate(
                    ['key' => $dbKey],
                    [
                        'value' => $stringVal,
                        'group' => $group,
                        'type' => $type,
                        'is_public' => str_starts_with($dbKey, 'general.') || $dbKey === 'commercial_tax.company_npwp',
                    ]
                );

                SystemSettingService::clearCache($dbKey);
            }
        }

        // Catat Audit Trail komprehensif pada tabel activity_logs (TC-ERP-INV-007, TC-SEC-PDP-010)
        if (! empty($changedKeys)) {
            ActivityLog::create([
                'log_name' => 'system_settings_updated',
                'description' => sprintf(
                    'System settings updated by [%s]: %d configuration parameter(s) modified (%s).',
                    $user->name,
                    count($changedKeys),
                    implode(', ', $changedKeys)
                ),
                'causer_type' => $user->getMorphClass(),
                'causer_id' => $user->id,
                'properties' => [
                    'old' => $oldValues,
                    'new' => $newValues,
                    'changed' => $changedKeys,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        }

        Notification::make()
            ->title('Settings Saved')
            ->body('System and commercial configurations have been updated successfully.')
            ->success()
            ->send();
    }
}
