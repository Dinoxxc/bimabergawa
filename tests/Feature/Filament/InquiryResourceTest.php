<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\InquiryResource;
use App\Filament\Resources\InquiryResource\Pages\ListInquiriesKanban;
use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\InternalComment;
use App\Models\User;
use App\Services\InquiryAssignmentService;
use App\Services\SecureFileUploadService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InquiryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $salesA;

    protected User $salesB;

    protected User $financeUser;

    protected User $logisticsUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->salesA = User::factory()->create([
            'email' => 'sales.a@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesA->assignRole('sales_pic');

        $this->salesB = User::factory()->create([
            'email' => 'sales.b@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesB->assignRole('sales_pic');

        $this->financeUser = User::factory()->create([
            'email' => 'finance@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->financeUser->assignRole('finance');

        $this->logisticsUser = User::factory()->create([
            'email' => 'logistics@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->logisticsUser->assignRole('logistics');
    }

    /**
     * TC-ERP-CRM-001: Tampilan Daftar Inkuiri Tabular & Filter Multi-Parameter
     */
    public function test_super_admin_can_view_inquiries_table_and_filter(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0001',
            'company_name' => 'PT. Mega Daya Kontraktor',
            'customer_name' => 'Ir. Gunawan',
            'phone_wa' => '081234567890',
            'email' => 'gunawan@megadaya.com',
            'status' => 'new',
            'source' => 'website_rfq',
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->get(InquiryResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('PBB/INQ/2026/09/0001');
        $response->assertSee('PT. Mega Daya Kontraktor');
    }

    /**
     * TC-ERP-CRM-002: Pergantian Tampilan Antara Tabel dan Kanban Board
     */
    public function test_kanban_view_page_renders_with_columns(): void
    {
        Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0002',
            'company_name' => 'PT. Sinar Perkasa',
            'customer_name' => 'Doni',
            'phone_wa' => '081299887766',
            'status' => 'new',
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->get(InquiryResource::getUrl('kanban'));
        $response->assertSuccessful();
        $response->assertSee('New Lead');
        $response->assertSee('Contacted');
        $response->assertSee('Quote Draft');
        $response->assertSee('Negotiation');
        $response->assertSee('Won');
        $response->assertSee('Lost');
        $response->assertSee('PBB/INQ/2026/09/0002');
    }

    public function test_inquiry_resource_registers_both_table_and_kanban_navigation_items(): void
    {
        $navItems = InquiryResource::getNavigationItems();
        $this->assertCount(2, $navItems);
        $this->assertEquals('Inquiries', $navItems[0]->getLabel());
        $this->assertEquals('Kanban Board', $navItems[1]->getLabel());
    }

    /**
     * TC-ERP-CRM-003: Transisi Status Inkuiri via Action / Kanban
     */
    public function test_status_transition_updates_database_and_records_activity_log(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0003',
            'company_name' => 'PT. Pembangunan Jaya',
            'customer_name' => 'Hendra',
            'phone_wa' => '081388776655',
            'status' => 'new',
        ]);

        $this->actingAs($this->salesA);

        Livewire::test(ListInquiriesKanban::class)
            ->call('moveCard', $inquiry->id, 'contacted');

        $this->assertEquals('contacted', $inquiry->fresh()->status);
        $this->assertNotNull($inquiry->fresh()->first_contacted_at);

        // Verifikasi pencatatan ActivityLog (TC-ERP-CRM-014)
        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'inquiry_status_change',
            'subject_id' => $inquiry->id,
            'causer_id' => $this->salesA->id,
        ]);
    }

    /**
     * TC-ERP-CRM-004: Penegakan Indikator Peringatan SLA Stale Aging (> 24 Jam)
     */
    public function test_sla_warning_badge_on_inquiry_older_than_24_hours(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0004',
            'company_name' => 'PT. Lambat Respon',
            'customer_name' => 'Budi',
            'phone_wa' => '081377889911',
            'status' => 'new',
        ]);
        $inquiry->timestamps = false;
        $inquiry->created_at = now()->subHours(26);
        $inquiry->save();

        $service = app(InquiryAssignmentService::class);
        $sla = $service->calculateSlaAging($inquiry);

        $this->assertEquals('warning', $sla['level']);
        $this->assertTrue($sla['is_stale']);
        $this->assertStringContainsString('SLA Warning', $sla['badge']);
    }

    /**
     * TC-ERP-CRM-005: Penegakan Indikator Peringatan SLA Kritis (> 48 Jam)
     */
    public function test_sla_critical_danger_badge_on_inquiry_older_than_48_hours(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0005',
            'company_name' => 'PT. Kritis Lead',
            'customer_name' => 'Agus',
            'phone_wa' => '081355667788',
            'status' => 'new',
        ]);
        $inquiry->timestamps = false;
        $inquiry->created_at = now()->subHours(50);
        $inquiry->save();

        $service = app(InquiryAssignmentService::class);
        $sla = $service->calculateSlaAging($inquiry);

        $this->assertEquals('danger', $sla['level']);
        $this->assertTrue($sla['is_stale']);
        $this->assertStringContainsString('SLA Danger: > 48 Jam Belum Diproses', $sla['badge']);
    }

    /**
     * TC-ERP-CRM-006: Penugasan PIC Sales (Sales Assignment Workflow)
     */
    public function test_sales_pic_assignment_creates_notification_and_activity_log(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0006',
            'company_name' => 'PT. Industri Otomasi',
            'customer_name' => 'Fajar',
            'phone_wa' => '081211223344',
            'status' => 'new',
        ]);

        $service = app(InquiryAssignmentService::class);
        $service->assignSalesPic($inquiry, $this->salesA, $this->superAdmin);

        $this->assertEquals($this->salesA->id, $inquiry->fresh()->assigned_user_id);

        // Verifikasi notifikasi terkirim (TC-ERP-CRM-017)
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->salesA->id,
            'type' => 'App\\Notifications\\InquiryAssignedNotification',
        ]);

        // Verifikasi activity log
        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'inquiry_assignment',
            'subject_id' => $inquiry->id,
        ]);
    }

    /**
     * TC-ERP-CRM-007: Pratinjau & Unduh Lampiran BOQ PDF via Signed URL
     */
    public function test_boq_attachment_url_generation_via_temporary_signed_url(): void
    {
        $filePath = 'inquiries/boq/project_boq_electrical.pdf';
        $signedUrl = SecureFileUploadService::generateTemporaryUrl($filePath, 10);

        $this->assertStringContainsString('signature=', $signedUrl);
        $this->assertStringContainsString('expires=', $signedUrl);
    }

    /**
     * TC-ERP-CRM-009 & TC-ERP-CRM-010: Penolakan / Status Lost dengan Alasan Wajib
     */
    public function test_transition_to_lost_requires_mandatory_cancellation_reason(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0008',
            'company_name' => 'PT. Batal Proyek',
            'customer_name' => 'Yudi',
            'phone_wa' => '081988776655',
            'status' => 'contacted',
        ]);

        $service = app(InquiryAssignmentService::class);

        // Uji validasi penolakan tanpa alasan (TC-ERP-CRM-010)
        $this->expectException(ValidationException::class);
        $service->changeStatus($inquiry, 'lost', '');
    }

    public function test_transition_to_lost_succeeds_with_valid_reason(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0009',
            'company_name' => 'PT. Batal Proyek Sukses',
            'customer_name' => 'Yudi',
            'phone_wa' => '081988776655',
            'status' => 'contacted',
        ]);

        $service = app(InquiryAssignmentService::class);
        $updated = $service->changeStatus($inquiry, 'lost', 'Competitor price was 15% lower.');

        $this->assertEquals('lost', $updated->status);
        $this->assertStringContainsString('Competitor price was 15% lower.', $updated->notes);
    }

    /**
     * TC-ERP-CRM-011 & TC-ERP-CRM-013: Akses atau Modifikasi Inkuiri oleh Sales Non-PIC (Isolasi Sales)
     */
    public function test_sales_pic_isolation_prevents_unassigned_sales_from_modifying_another_sales_inquiry(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0010',
            'company_name' => 'PT. Eksklusif Sales A',
            'customer_name' => 'Rudy',
            'phone_wa' => '081233445566',
            'status' => 'new',
            'assigned_user_id' => $this->salesA->id,
        ]);

        // Sales A can update their own inquiry
        $this->assertTrue($this->salesA->can('update', $inquiry));

        // Sales B is forbidden from updating Sales A's inquiry (TC-ERP-CRM-011)
        $this->assertFalse($this->salesB->can('update', $inquiry));

        // Super Admin has full bypass
        $this->assertTrue($this->superAdmin->can('update', $inquiry));
    }

    /**
     * TC-ERP-CRM-012: Penugasan PIC ke Pengguna Non-Aktif
     */
    public function test_cannot_assign_pic_to_inactive_user(): void
    {
        $inactiveSales = User::factory()->create([
            'is_active' => false,
        ]);
        $inactiveSales->assignRole('sales_pic');

        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0011',
            'company_name' => 'PT. Test Inactive',
            'customer_name' => 'Citra',
            'phone_wa' => '081244556677',
            'status' => 'new',
        ]);

        $service = app(InquiryAssignmentService::class);

        $this->expectException(ValidationException::class);
        $service->assignSalesPic($inquiry, $inactiveSales);
    }

    /**
     * TC-ERP-CRM-015: Penanganan Inkuiri dengan Jumlah Item SKU Besar (> 100 SKU)
     */
    public function test_inquiry_with_many_items_is_handled_without_memory_exhaustion(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0012',
            'company_name' => 'PT. Mega BOQ Proyek',
            'customer_name' => 'Darmawan',
            'phone_wa' => '081255667788',
            'status' => 'new',
        ]);

        $items = [];
        for ($i = 1; $i <= 100; $i++) {
            $items[] = [
                'inquiry_id' => $inquiry->id,
                'item_name' => "Schneider Electric MCB Acti9 {$i}A",
                'model_number' => "A9F7410{$i}",
                'quantity' => $i,
                'uom' => 'PCS',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        InquiryItem::insert($items);

        $this->assertEquals(100, $inquiry->items()->count());

        $this->actingAs($this->superAdmin);
        $response = $this->get(InquiryResource::getUrl('view', ['record' => $inquiry]));
        $response->assertSuccessful();
    }

    /**
     * TC-ERP-CRM-016: Penanganan Pembaruan Status Serentak (Optimistic Locking)
     */
    public function test_optimistic_locking_prevents_stale_status_update(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0013',
            'company_name' => 'PT. Concurrency Test',
            'customer_name' => 'Eko',
            'phone_wa' => '081266778899',
            'status' => 'new',
        ]);

        $service = app(InquiryAssignmentService::class);

        // Stale timestamp passed
        $staleTimestamp = now()->subMinutes(10)->toISOString();

        $this->expectException(ValidationException::class);
        $service->changeStatus($inquiry, 'contacted', null, null, $staleTimestamp);
    }

    /**
     * TC-ERP-CRM-018 & TC-ERP-CRM-019: Linimasa Koordinasi & Mention Pengguna
     */
    public function test_internal_comments_posting_with_user_mention_notification(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0014',
            'company_name' => 'PT. Kolaborasi Proyek',
            'customer_name' => 'Rina',
            'phone_wa' => '081277889900',
            'status' => 'new',
        ]);

        $service = app(InquiryAssignmentService::class);
        $comment = $service->postComment(
            inquiry: $inquiry,
            author: $this->superAdmin,
            comment: 'Tolong cek stok Schneider di gudang.',
            type: 'internal',
            attachmentPath: null,
            mentionedUserIds: [$this->salesA->id]
        );

        $this->assertDatabaseHas('internal_comments', [
            'id' => $comment->id,
            'commentable_id' => $inquiry->id,
            'type' => 'internal',
        ]);

        // Verifikasi notifikasi terkirim ke user yang dimention (TC-ERP-CRM-019)
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->salesA->id,
            'type' => 'App\\Notifications\\UserMentionedNotification',
        ]);
    }

    /**
     * TC-ERP-CRM-020: Penegakan Visibilitas Ganda: Catatan Staf vs Pesan Agen
     */
    public function test_internal_comments_dual_visibility_agent_isolation(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/09/0015',
            'company_name' => 'PT. Dual Visibility Test',
            'customer_name' => 'Kiki',
            'phone_wa' => '081288990011',
            'status' => 'new',
        ]);

        $service = app(InquiryAssignmentService::class);

        // Buat 1 komentar rahasia internal dan 1 komentar publik agen
        $service->postComment($inquiry, $this->superAdmin, 'Modal HPP item ini Rp 50.000 (Rahasia)', 'internal');
        $service->postComment($inquiry, $this->superAdmin, 'Barang ready stok di gudang Cikarang', 'agent_visible');

        // Staf internal dapat melihat kedua komentar (total 2)
        $this->actingAs($this->salesA);
        $staffComments = InternalComment::where('commentable_id', $inquiry->id)->get();
        $this->assertCount(2, $staffComments);

        // Pengguna agen freelance / tamu hanya melihat komentar agent_visible (0% bocor modal HPP)
        $agentUser = User::factory()->create();
        $agentUser->assignRole('freelance_sales');
        $this->actingAs($agentUser);

        $agentComments = InternalComment::where('commentable_id', $inquiry->id)->get();
        $this->assertCount(1, $agentComments);
        $this->assertEquals('Barang ready stok di gudang Cikarang', $agentComments->first()->comment);
    }
}
