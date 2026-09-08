<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\Scopes\AgentTenantScope;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentTenantScopeTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * TC-AGN-INQ-002 & TC-AGN-INQ-006: Penegakan Isolasi Data Tenant & Anti-IDOR
     */
    public function test_agent_tenant_scope_isolates_inquiries_per_agent(): void
    {
        $role = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);

        $tier = CommissionTier::create([
            'name' => 'Silver Tier',
            'min_turnover_amount' => 50000000.00,
            'commission_rate' => 2.50,
            'is_active' => true,
        ]);

        // Agent A
        $agentA = FreelanceSales::create([
            'commission_tier_id' => $tier->id,
            'name' => 'Agen A',
            'referral_code' => 'AGT-AAA-001',
            'nik' => '1111111111111111',
            'bank_name' => 'BCA',
            'bank_account_number' => '11112222',
            'bank_account_name' => 'Agen A',
            'status' => 'approved',
        ]);
        $userA = User::create([
            'name' => 'User Agen A',
            'email' => 'agent_a_'.time().'@example.com',
            'freelance_sales_id' => $agentA->id,
        ]);
        $userA->assignRole($role);
        $agentA->update(['user_id' => $userA->id]);

        // Agent B
        $agentB = FreelanceSales::create([
            'commission_tier_id' => $tier->id,
            'name' => 'Agen B',
            'referral_code' => 'AGT-BBB-002',
            'nik' => '2222222222222222',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '33334444',
            'bank_account_name' => 'Agen B',
            'status' => 'approved',
        ]);
        $userB = User::create([
            'name' => 'User Agen B',
            'email' => 'agent_b_'.time().'@example.com',
            'freelance_sales_id' => $agentB->id,
        ]);
        $userB->assignRole($role);
        $agentB->update(['user_id' => $userB->id]);

        // Inquiries
        $inqA = Inquiry::create([
            'inquiry_number' => 'INQ-TEST-A-'.time(),
            'freelance_sales_id' => $agentA->id,
            'customer_name' => 'PT Prospek A',
            'company_name' => 'PT Prospek A',
            'phone_wa' => '08123456781',
            'type' => 'single_direct',
            'source' => 'referral',
            'status' => 'new',
        ]);

        $inqB = Inquiry::create([
            'inquiry_number' => 'INQ-TEST-B-'.time(),
            'freelance_sales_id' => $agentB->id,
            'customer_name' => 'PT Prospek B',
            'company_name' => 'PT Prospek B',
            'phone_wa' => '08123456782',
            'type' => 'bulk_cart',
            'source' => 'referral',
            'status' => 'new',
        ]);

        // 1. Authenticated as Agent A: Can ONLY see Inquiry A
        $this->actingAs($userA);
        $visibleInquiries = Inquiry::all();
        $this->assertTrue($visibleInquiries->contains('id', $inqA->id));
        $this->assertFalse($visibleInquiries->contains('id', $inqB->id));
        $this->assertNull(Inquiry::find($inqB->id));

        // 2. Authenticated as Agent B: Can ONLY see Inquiry B
        $this->actingAs($userB);
        $visibleInquiriesB = Inquiry::all();
        $this->assertTrue($visibleInquiriesB->contains('id', $inqB->id));
        $this->assertFalse($visibleInquiriesB->contains('id', $inqA->id));
        $this->assertNull(Inquiry::find($inqA->id));

        // 3. Bypass Global Scope (for Super Admin / Sales PIC internal): Can see both
        $allInquiries = Inquiry::withoutGlobalScope(AgentTenantScope::class)->get();
        $this->assertTrue($allInquiries->contains('id', $inqA->id));
        $this->assertTrue($allInquiries->contains('id', $inqB->id));
    }

    /**
     * Test AgentTenantScope also isolates Quotation records per agent.
     */
    public function test_agent_tenant_scope_isolates_quotations_per_agent(): void
    {
        $role = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);

        $tier = CommissionTier::create([
            'name' => 'Gold Tier',
            'min_turnover_amount' => 100000000.00,
            'commission_rate' => 3.00,
            'is_active' => true,
        ]);

        $agent = FreelanceSales::create([
            'commission_tier_id' => $tier->id,
            'name' => 'Agen Khusus',
            'referral_code' => 'AGT-KHUSUS-'.time(),
            'nik' => '9999999999999999',
            'bank_name' => 'BCA',
            'bank_account_number' => '99998888',
            'bank_account_name' => 'Agen Khusus',
            'status' => 'approved',
        ]);

        $user = User::create([
            'name' => 'User Agen Khusus',
            'email' => 'agent_khusus_'.time().'@example.com',
            'freelance_sales_id' => $agent->id,
        ]);
        $user->assignRole($role);

        $salesPic = User::create([
            'name' => 'Internal Sales PIC',
            'email' => 'sales_pic_q_'.time().'@example.com',
        ]);

        $quoMyAgent = Quotation::create([
            'quotation_number' => 'QUO-AGENT-MY-'.time(),
            'user_id' => $salesPic->id,
            'freelance_sales_id' => $agent->id,
            'customer_name' => 'Client Klien Agen',
            'company_name' => 'PT Klien Agen',
            'subtotal' => 5000000.00,
            'dpp' => 5000000.00,
            'vat_rate_applied' => 12.00,
            'tax_amount' => 600000.00,
            'grand_total' => 5600000.00,
            'status' => 'draft',
        ]);

        $quoOther = Quotation::create([
            'quotation_number' => 'QUO-AGENT-OTHER-'.time(),
            'user_id' => $salesPic->id,
            'freelance_sales_id' => null, // Direct lead
            'customer_name' => 'Client Direct PBB',
            'company_name' => 'PT Direct PBB',
            'subtotal' => 20000000.00,
            'dpp' => 20000000.00,
            'vat_rate_applied' => 12.00,
            'tax_amount' => 2400000.00,
            'grand_total' => 22400000.00,
            'status' => 'draft',
        ]);

        // When acting as agent, only agent's quotations are visible
        $this->actingAs($user);
        $visibleQuotations = Quotation::all();
        $this->assertTrue($visibleQuotations->contains('id', $quoMyAgent->id));
        $this->assertFalse($visibleQuotations->contains('id', $quoOther->id));
        $this->assertNull(Quotation::find($quoOther->id));
    }

    /**
     * TC-ERP-INV-014: Resolusi Inkuiri Multi-Revisi Penawaran via latestOfMany()
     */
    public function test_inquiry_quotation_relation_returns_latest_of_many_revision(): void
    {
        $salesPic = User::create([
            'name' => 'Sales PIC Revision',
            'email' => 'sales_rev_'.time().'@example.com',
        ]);

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-REV-'.time(),
            'customer_name' => 'PT Multi Revisi',
            'company_name' => 'PT Multi Revisi',
            'phone_wa' => '081122334455',
            'type' => 'single_direct',
            'source' => 'website_rfq',
            'status' => 'new',
        ]);

        // Create Revision 0 (Initial)
        $quoRev0 = Quotation::create([
            'quotation_number' => 'QUO-REV-'.time().'-R0',
            'revision_number' => 0,
            'inquiry_id' => $inquiry->id,
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Multi Revisi',
            'company_name' => 'PT Multi Revisi',
            'subtotal' => 10000000.00,
            'grand_total' => 11200000.00,
            'status' => 'rejected',
            'created_at' => now()->subMinutes(10),
        ]);

        // Create Revision 1 (Updated discount)
        $quoRev1 = Quotation::create([
            'quotation_number' => 'QUO-REV-'.time().'-R1',
            'revision_number' => 1,
            'inquiry_id' => $inquiry->id,
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Multi Revisi',
            'company_name' => 'PT Multi Revisi',
            'subtotal' => 9500000.00,
            'grand_total' => 10640000.00,
            'status' => 'rejected',
            'created_at' => now()->subMinutes(5),
        ]);

        // Create Revision 2 (Final Deal)
        $quoRev2 = Quotation::create([
            'quotation_number' => 'QUO-REV-'.time().'-R2',
            'revision_number' => 2,
            'inquiry_id' => $inquiry->id,
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Multi Revisi',
            'company_name' => 'PT Multi Revisi',
            'subtotal' => 9000000.00,
            'grand_total' => 10080000.00,
            'status' => 'approved',
            'created_at' => now(),
        ]);

        // Verification: inquiry->quotation must resolve to the latest revision (R2)
        $freshInquiry = $inquiry->fresh();
        $this->assertNotNull($freshInquiry->quotation);
        $this->assertEquals($quoRev2->id, $freshInquiry->quotation->id);
        $this->assertEquals(2, $freshInquiry->quotation->revision_number);
        $this->assertEquals(9000000.00, (float) $freshInquiry->quotation->subtotal);
        $this->assertCount(3, $freshInquiry->quotations);
    }
}
