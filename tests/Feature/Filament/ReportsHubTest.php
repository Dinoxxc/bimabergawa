<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\ReportsHub;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsHubTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $financeUser;

    private User $salesUser;

    private User $logisticsUser;

    private User $mitraUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['email' => 'director@bimabergawa.com', 'is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->financeUser = User::factory()->create(['email' => 'finance@bimabergawa.com', 'is_active' => true]);
        $this->financeUser->assignRole('finance');

        $this->salesUser = User::factory()->create(['email' => 'sales@bimabergawa.com', 'is_active' => true]);
        $this->salesUser->assignRole('sales_pic');

        $this->logisticsUser = User::factory()->create(['email' => 'logistics@bimabergawa.com', 'is_active' => true]);
        $this->logisticsUser->assignRole('logistics');

        $this->mitraUser = User::factory()->create(['email' => 'mitra@bimabergawa.com', 'is_active' => true]);
        $this->mitraUser->assignRole('freelance_sales');
    }

    /**
     * TC-ERP-REP-001: Tampilan 4-Tab Pusat Laporan Bisnis & Keuangan (/admin/reports).
     */
    public function test_super_admin_can_access_reports_hub_and_all_4_tabs(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('http://app.bimabergawa.test/admin/reports');

        $response->assertSuccessful();
        $response->assertSee('Executive Business &amp; Financial Reports Hub', false);
        $response->assertSee('1. Sales &amp; Conversion', false);
        $response->assertSee('2. Profitability &amp; Margin HPP', false);
        $response->assertSee('3. Accounts Receivable (AR Aging)', false);
        $response->assertSee('4. Agent Commission Summary', false);
    }

    /**
     * Test Finance role has access to financial and margin tabs.
     */
    public function test_finance_role_can_access_margin_and_ar_tabs(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->get('http://app.bimabergawa.test/admin/reports');

        $response->assertSuccessful();
        $response->assertSee('2. Profitability &amp; Margin HPP', false);
        $response->assertSee('3. Accounts Receivable (AR Aging)', false);
        $response->assertSee('4. Agent Commission Summary', false);
    }

    /**
     * TC-ERP-REP-002: Pembatasan Hak Akses Tab Margin HPP pada Reports Hub (Zero HPP Leak).
     */
    public function test_sales_pic_is_strictly_restricted_to_tab1_and_cannot_view_margin_tab(): void
    {
        $response = $this->actingAs($this->salesUser)
            ->get('http://app.bimabergawa.test/admin/reports');

        $response->assertSuccessful();
        $response->assertSee('1. Sales &amp; Conversion', false);
        $response->assertDontSee('2. Profitability &amp; Margin HPP', false);
        $response->assertDontSee('3. Accounts Receivable (AR Aging)', false);
        $response->assertDontSee('4. Agent Commission Summary', false);

        // Verify Livewire component blocks setting active tab to margin for Sales PIC
        Livewire::actingAs($this->salesUser)
            ->test(ReportsHub::class)
            ->call('setActiveTab', 'margin')
            ->assertSet('activeTab', 'sales');
    }

    /**
     * Test unauthorized roles (Logistics and Freelance Mitra) are blocked.
     */
    public function test_logistics_and_mitra_are_forbidden_from_reports_hub(): void
    {
        $this->actingAs($this->logisticsUser)
            ->get('http://app.bimabergawa.test/admin/reports')
            ->assertForbidden();

        $this->actingAs($this->mitraUser)
            ->get('http://app.bimabergawa.test/admin/reports')
            ->assertForbidden();
    }

    /**
     * Test unauthenticated guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('http://app.bimabergawa.test/admin/reports')
            ->assertRedirect('http://app.bimabergawa.test/login');
    }

    /**
     * TC-ERP-REP-003: Akurasi Analisis Umur Piutang Komersial (AR Aging Buckets).
     */
    public function test_ar_aging_buckets_calculation_accuracy(): void
    {
        $now = now();

        // 1. Current Invoice (due 10 days in the future)
        Invoice::factory()->create([
            'due_date' => $now->copy()->addDays(10),
            'grand_total' => 10000000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
        ]);

        // 2. Overdue Tier 1 (due 15 days ago)
        Invoice::factory()->create([
            'due_date' => $now->copy()->subDays(15),
            'grand_total' => 20000000.00,
            'paid_amount' => 5000000.00, // Remaining 15jt
            'status' => 'partially_paid',
        ]);

        // 3. Overdue Tier 2 (due 45 days ago)
        Invoice::factory()->create([
            'due_date' => $now->copy()->subDays(45),
            'grand_total' => 30000000.00,
            'paid_amount' => 0.00,
            'status' => 'overdue',
        ]);

        // 4. Critical Overdue (due 75 days ago)
        Invoice::factory()->create([
            'due_date' => $now->copy()->subDays(75),
            'grand_total' => 40000000.00,
            'paid_amount' => 0.00,
            'status' => 'overdue',
        ]);

        $this->actingAs($this->superAdmin);
        $reportsHub = new ReportsHub;
        $arData = $reportsHub->getArAgingData();

        $this->assertEquals(95000000.00, $arData['totalOutstanding']);
        $this->assertEquals(10000000.00, $arData['currentAr']);
        $this->assertEquals(15000000.00, $arData['overdue1_30']);
        $this->assertEquals(30000000.00, $arData['overdue31_60']);
        $this->assertEquals(40000000.00, $arData['criticalOverdue']);
        $this->assertCount(4, $arData['invoicesList']);
    }

    /**
     * Test margin analysis calculations.
     */
    public function test_margin_analysis_calculations(): void
    {
        // 1. Healthy margin quotation (20%)
        Quotation::factory()->create([
            'subtotal' => 100000000.00,
            'grand_total' => 111000000.00,
            'gross_profit_margin' => 20.00,
            'status' => 'approved',
        ]);

        // 2. Thin margin quotation (10%)
        Quotation::factory()->create([
            'subtotal' => 50000000.00,
            'grand_total' => 55500000.00,
            'gross_profit_margin' => 10.00,
            'status' => 'approved',
        ]);

        // 3. Critical margin quotation (5%) with director approval
        Quotation::factory()->create([
            'subtotal' => 200000000.00,
            'grand_total' => 222000000.00,
            'gross_profit_margin' => 5.00,
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin);
        $reportsHub = new ReportsHub;
        $marginData = $reportsHub->getMarginAnalysisData();

        $this->assertEquals(350000000.00, $marginData['totalRevenue']);
        $this->assertEquals(1, $marginData['healthyCount']);
        $this->assertEquals(1, $marginData['thinCount']);
        $this->assertEquals(1, $marginData['criticalCount']);
        $this->assertCount(1, $marginData['criticalTransactions']);
    }
}
