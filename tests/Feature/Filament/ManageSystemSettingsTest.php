<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageSystemSettings;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $financeUser;

    protected User $salesUser;

    protected User $logisticsUser;

    protected User $mitraUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SystemSettingSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->financeUser = User::factory()->create([
            'email' => 'finance@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->financeUser->assignRole('finance');

        $this->salesUser = User::factory()->create([
            'email' => 'sales@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesUser->assignRole('sales_pic');

        $this->logisticsUser = User::factory()->create([
            'email' => 'logistics@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->logisticsUser->assignRole('logistics');

        $this->mitraUser = User::factory()->create([
            'email' => 'mitra@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->mitraUser->assignRole('freelance_sales');
    }

    /**
     * Test super_admin can render and view the ManageSystemSettings page.
     */
    public function test_super_admin_can_access_manage_system_settings_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('http://app.bimabergawa.test/admin/system-settings');

        $response->assertSuccessful();
    }

    /**
     * Test freelance agent (mitra) is blocked from accessing system settings.
     */
    public function test_mitra_agent_is_strictly_blocked_from_system_settings(): void
    {
        $response = $this->actingAs($this->mitraUser)
            ->get('http://app.bimabergawa.test/admin/system-settings');

        $response->assertForbidden();
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('http://app.bimabergawa.test/admin/system-settings');

        $response->assertRedirect('http://app.bimabergawa.test/login');
    }

    /**
     * Test page mount pre-fills default seeded values.
     */
    public function test_page_mount_pre_fills_configured_settings(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ManageSystemSettings::class)
            ->assertSet('data.general_company_name', 'PT. Panca Bima Bergawa')
            ->assertSet('data.commercial_tax_tax_vat_rate', 11.00)
            ->assertSet('data.commercial_tax_critical_margin_threshold', 8.00)
            ->assertSet('data.commercial_tax_director_approval_limit', 100000000.00);
    }

    /**
     * Test updating national VAT rate dynamically (UU HPP & UU KUP Compliance).
     */
    public function test_updating_vat_rate_updates_database_clears_cache_and_logs_audit_trail(): void
    {
        $this->assertEquals(11.00, SystemSettingService::getFloat('commercial_tax.tax_vat_rate'));

        Livewire::actingAs($this->superAdmin)
            ->test(ManageSystemSettings::class)
            ->set('data.commercial_tax_tax_vat_rate', 12.00)
            ->call('save')
            ->assertHasNoErrors();

        // 1. Verify Database Record
        $setting = SystemSetting::where('key', 'commercial_tax.tax_vat_rate')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('12.00', $setting->value);

        // 2. Verify Service Cache Clearance (Instant Update)
        $this->assertEquals(12.00, SystemSettingService::getFloat('commercial_tax.tax_vat_rate'));

        // 3. Verify ActivityLog Audit Trail (TC-ERP-INV-007, TC-SEC-PDP-010)
        $auditLog = ActivityLog::where('log_name', 'system_settings_updated')->first();
        $this->assertNotNull($auditLog);
        $this->assertSame($this->superAdmin->id, $auditLog->causer_id);
        $this->assertContains('commercial_tax.tax_vat_rate', $auditLog->properties['changed'] ?? []);
        $this->assertEquals('11.00', $auditLog->properties['old']['commercial_tax.tax_vat_rate'] ?? null);
        $this->assertEquals('12.00', $auditLog->properties['new']['commercial_tax.tax_vat_rate'] ?? null);
    }

    /**
     * Test finance role can update commercial tax settings.
     */
    public function test_finance_role_can_update_commercial_tax_settings(): void
    {
        Livewire::actingAs($this->financeUser)
            ->test(ManageSystemSettings::class)
            ->set('data.commercial_tax_tax_vat_rate', 13.00)
            ->set('data.commercial_tax_critical_margin_threshold', 10.00)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(13.00, SystemSettingService::getFloat('commercial_tax.tax_vat_rate'));
        $this->assertEquals(10.00, SystemSettingService::getFloat('commercial_tax.critical_margin_threshold'));
    }

    /**
     * Test sales role cannot update commercial tax settings (guarded on save).
     */
    public function test_sales_role_cannot_modify_commercial_tax_settings(): void
    {
        $initialVat = SystemSettingService::getFloat('commercial_tax.tax_vat_rate');

        Livewire::actingAs($this->salesUser)
            ->test(ManageSystemSettings::class)
            ->set('data.commercial_tax_tax_vat_rate', 15.00)
            ->call('save');

        // VAT rate remains unchanged because sales_pic is not authorized for commercial_tax group
        $this->assertEquals($initialVat, SystemSettingService::getFloat('commercial_tax.tax_vat_rate'));
    }

    /**
     * Test updating company profile and hotline parameters.
     */
    public function test_super_admin_can_update_company_profile_and_hotline(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ManageSystemSettings::class)
            ->set('data.general_company_address', 'Gd. Wirausaha lt.2 Unit 201, Jl. H.R. Rasuna Said, Jakarta 12920')
            ->set('data.general_contact_whatsapp', '6281299887766')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(
            'Gd. Wirausaha lt.2 Unit 201, Jl. H.R. Rasuna Said, Jakarta 12920',
            SystemSettingService::getString('general.company_address')
        );
        $this->assertEquals(
            '6281299887766',
            SystemSettingService::getString('general.contact_whatsapp')
        );
    }
}
