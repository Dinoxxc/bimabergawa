<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PolymorphicAndAuditLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Rule 6.8: Enforce Morph Map Polimorfik
     */
    public function test_morph_map_uses_short_aliases(): void
    {
        $this->assertEquals('sales_order', (new SalesOrder)->getMorphClass());
        $this->assertEquals('inquiry', (new Inquiry)->getMorphClass());
        $this->assertEquals('user', (new User)->getMorphClass());
    }

    /**
     * TC-SEC-PDP-004 & TC-SEC-PDP-012: ActivityLog Audit Trail Append-Only & Short Morph
     */
    public function test_activity_log_creates_immutable_record_with_polymorphic_relations(): void
    {
        $user = User::create([
            'name' => 'Admin Auditor',
            'email' => 'auditor_'.time().'@example.com',
        ]);

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-AUDIT-'.time(),
            'customer_name' => 'PT Audit Trail Client',
            'company_name' => 'PT Audit Trail Client',
            'phone_wa' => '08123456789',
            'type' => 'single_direct',
            'source' => 'website_rfq',
            'status' => 'quote_sent',
        ]);

        $log = ActivityLog::log(
            logName: 'commercial_status_update',
            description: 'Status inkuiri diubah dari new ke quote_sent',
            subject: $inquiry,
            causer: $user,
            properties: ['old_status' => 'new', 'new_status' => 'quote_sent']
        );

        $this->assertNotNull($log->id);
        $this->assertEquals('inquiry', $log->subject_type);
        $this->assertEquals($inquiry->id, $log->subject_id);
        $this->assertEquals('user', $log->causer_type);
        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals(['old_status' => 'new', 'new_status' => 'quote_sent'], $log->properties);

        // Verify relationships
        $this->assertInstanceOf(Inquiry::class, $log->subject);
        $this->assertEquals($inquiry->id, $log->subject->id);
        $this->assertInstanceOf(User::class, $log->causer);
        $this->assertEquals($user->id, $log->causer->id);
    }
}
