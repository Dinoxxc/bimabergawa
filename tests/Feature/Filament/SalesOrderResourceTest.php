<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\SalesOrderResource;
use App\Models\ActivityLog;
use App\Models\CommissionPayout;
use App\Models\FreelanceSales;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use App\Services\SalesOrderWorkflowService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SalesOrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $salesUser;

    protected User $financeUser;

    protected User $logisticsUser;

    protected Quotation $approvedQuotation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->salesUser = User::factory()->create([
            'email' => 'sales@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesUser->assignRole('sales_pic');

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

        $this->approvedQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0001',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Mega Konstruksi Mandiri',
            'customer_name' => 'Ir. Hartono',
            'project_name' => 'Schneider Panel Retrofit',
            'delivery_terms' => 'Loco Warehouse PBB Jakarta',
            'validity_days' => 14,
            'subtotal' => 45000000.00,
            'dpp' => 45000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 4950000.00,
            'grand_total' => 49950000.00,
            'total_cost' => 36000000.00,
            'gross_profit_margin' => 20.00,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        QuotationItem::create([
            'quotation_id' => $this->approvedQuotation->id,
            'item_name' => 'Schneider Compact NSX100F',
            'model_number' => 'LV429630',
            'quantity' => 10,
            'uom' => 'pcs',
            'unit_price' => 4500000.00,
            'cost_price' => 3600000.00,
            'total_price' => 45000000.00,
        ]);
    }

    /**
     * TC-ERP-SO-001: Konversi Quotation Menjadi Sales Order Resmi
     */
    public function test_conversion_from_approved_quotation_creates_sales_order(): void
    {
        $service = app(SalesOrderWorkflowService::class);

        $so = $service->convertFromQuotation($this->approvedQuotation, [
            'customer_po_number' => 'PO-MKM-2026-001',
            'customer_po_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(SalesOrder::class, $so);
        $this->assertStringStartsWith('PBB/SOR/', $so->order_number);
        $this->assertEquals($this->approvedQuotation->id, $so->quotation_id);
        $this->assertEquals('PO-MKM-2026-001', $so->customer_po_number);
        $this->assertEquals(49950000.00, (float) $so->total_amount);
        $this->assertEquals('approved', $so->status); // < 100 Juta dan margin aman -> auto approved
    }

    /**
     * TC-ERP-SO-003: Persetujuan Sales Order Otomatis (Nilai < Rp 100 Juta & Margin Aman)
     */
    public function test_sales_order_under_100_million_is_auto_approved(): void
    {
        $service = app(SalesOrderWorkflowService::class);

        $so = $service->convertFromQuotation($this->approvedQuotation, [
            'customer_po_number' => 'PO-AUTO-01',
        ]);

        $this->assertEquals('approved', $so->status);
        $this->assertFalse((bool) $so->is_approved_by_director);
    }

    /**
     * TC-ERP-SO-004: Eskalasi Kewajiban Persetujuan Direktur (Nilai >= Rp 100 Juta)
     */
    public function test_sales_order_100_million_or_above_escalates_to_pending_director_approval(): void
    {
        $largeQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0002',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Tender Mega Infrastruktur',
            'customer_name' => 'Bambang',
            'project_name' => 'MRT Station Substation Phase 2',
            'subtotal' => 150000000.00,
            'dpp' => 150000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 16500000.00,
            'grand_total' => 166500000.00, // >= 100 Juta
            'total_cost' => 120000000.00,
            'gross_profit_margin' => 20.00,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        $service = app(SalesOrderWorkflowService::class);
        $so = $service->convertFromQuotation($largeQuotation, [
            'customer_po_number' => 'PO-MRT-2026-99',
        ]);

        $this->assertEquals('pending_director_approval', $so->status);
        $this->assertFalse((bool) $so->is_approved_by_director);
    }

    /**
     * TC-ERP-SO-005: Eksekusi Approval oleh Akun Direktur / Super Admin
     */
    public function test_super_admin_can_approve_pending_sales_order(): void
    {
        $so = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0002',
            'quotation_id' => $this->approvedQuotation->id,
            'customer_po_number' => 'PO-MRT-2026-99',
            'total_amount' => 166500000.00,
            'vat_rate_applied' => 11.00,
            'currency' => 'IDR',
            'status' => 'pending_director_approval',
            'is_approved_by_director' => false,
        ]);

        $service = app(SalesOrderWorkflowService::class);
        $approvedSo = $service->approveSalesOrder($so, $this->superAdmin->id, 'Endorsed for strategic project.');

        $this->assertEquals('approved', $approvedSo->status);
        $this->assertTrue((bool) $approvedSo->is_approved_by_director);
        $this->assertEquals($this->superAdmin->id, $approvedSo->approved_by);
        $this->assertNotNull($approvedSo->approved_at);
        $this->assertEquals('Endorsed for strategic project.', $approvedSo->director_notes);
    }

    /**
     * TC-ERP-SO-006: Penerbitan Berkas PDF Sales Order Resmi
     */
    public function test_sales_order_pdf_export_generates_valid_pdf_document(): void
    {
        $so = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0003',
            'quotation_id' => $this->approvedQuotation->id,
            'customer_po_number' => 'PO-PDF-TEST-01',
            'customer_po_date' => now()->toDateString(),
            'total_amount' => 49950000.00,
            'vat_rate_applied' => 11.00,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        $pdf = PdfExportService::generateSalesOrderPdf($so, saveToStorage: true);
        $binary = $pdf->output();

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    /**
     * TC-ERP-SO-007: Penguncian Komisi Agen Freelance saat SO Approved
     */
    public function test_commission_is_calculated_for_freelance_agent_upon_sales_order_approval(): void
    {
        $agentUser = User::factory()->create(['is_active' => true]);
        $agent = FreelanceSales::create([
            'name' => 'Agent Partner',
            'user_id' => $agentUser->id,
            'referral_code' => 'AGT-001',
            'nik' => '3171012345678901',
            'bank_account_name' => 'Agent Partner',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'status' => 'approved',
        ]);

        $agentQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0003',
            'user_id' => $this->salesUser->id,
            'freelance_sales_id' => $agent->id,
            'company_name' => 'PT. Mitra Agen Mandiri',
            'customer_name' => 'Joko',
            'subtotal' => 20000000.00,
            'dpp' => 20000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 2200000.00,
            'grand_total' => 22200000.00,
            'total_cost' => 16000000.00,
            'gross_profit_margin' => 20.00,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        $service = app(SalesOrderWorkflowService::class);
        $so = $service->convertFromQuotation($agentQuotation, [
            'customer_po_number' => 'PO-AGENT-REF-01',
        ]);

        $payout = CommissionPayout::where('sales_order_id', $so->id)
            ->where('freelance_sales_id', $agent->id)
            ->first();

        $this->assertNotNull($payout);
        $this->assertEquals('earned', $payout->type);
        $this->assertEquals('pending_payment', $payout->status);
        $this->assertTrue((float) $payout->amount > 0);
    }

    /**
     * TC-ERP-SO-008: Validasi Penolakan Konversi dari Quotation yang Ditolak (Rejected)
     */
    public function test_conversion_fails_for_rejected_or_draft_quotation(): void
    {
        $rejectedQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0004',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Rejected Client',
            'customer_name' => 'Budi',
            'subtotal' => 1000000.00,
            'grand_total' => 1110000.00,
            'status' => 'rejected',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $service = app(SalesOrderWorkflowService::class);
        $service->convertFromQuotation($rejectedQuotation);
    }

    /**
     * TC-ERP-SO-010: Pembatalan Sales Order dengan Alasan Wajib
     */
    public function test_cancelling_sales_order_requires_valid_reason(): void
    {
        $so = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0005',
            'quotation_id' => $this->approvedQuotation->id,
            'total_amount' => 49950000.00,
            'status' => 'processing',
        ]);

        $service = app(SalesOrderWorkflowService::class);

        // Alasan kosong harus melempar exception
        try {
            $service->cancelSalesOrder($so, '   ', $this->salesUser->id);
            $this->fail('Expected InvalidArgumentException for empty cancellation reason.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('wajib diisi', $e->getMessage());
        }

        // Alasan valid berhasil membatalkan
        $cancelledSo = $service->cancelSalesOrder($so, 'Customer PO was revoked due to project cancellation.', $this->salesUser->id);
        $this->assertEquals('cancelled', $cancelledSo->status);
        $this->assertEquals('Customer PO was revoked due to project cancellation.', $cancelledSo->cancellation_reason);
    }

    /**
     * TC-ERP-SO-011: Penyuntingan Item/Harga pada Sales Order yang Telah Selesai/Terkirim Dikunci
     */
    public function test_completed_or_shipped_sales_order_is_locked_from_editing(): void
    {
        $completedSo = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0006',
            'quotation_id' => $this->approvedQuotation->id,
            'total_amount' => 49950000.00,
            'status' => 'completed',
        ]);

        $this->assertFalse(SalesOrderResource::canEdit($completedSo));

        $secondQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0005',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Second Client',
            'customer_name' => 'Second Contact',
            'subtotal' => 1000000.00,
            'dpp' => 1000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 110000.00,
            'grand_total' => 1110000.00,
            'status' => 'approved',
        ]);

        $processingSo = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0007',
            'quotation_id' => $secondQuotation->id,
            'total_amount' => 49950000.00,
            'status' => 'processing',
        ]);

        $this->assertTrue(SalesOrderResource::canEdit($processingSo));
    }

    /**
     * TC-ERP-SO-013: Pencatatan Lengkap Perubahan Status pada ActivityLog
     */
    public function test_activity_log_records_sales_order_events(): void
    {
        $service = app(SalesOrderWorkflowService::class);
        $so = $service->convertFromQuotation($this->approvedQuotation, [
            'customer_po_number' => 'PO-AUDIT-01',
        ]);

        $log = ActivityLog::where('subject_type', SalesOrder::class)
            ->where('subject_id', $so->id)
            ->where('log_name', 'sales_order_converted')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString($so->order_number, $log->description);
    }

    /**
     * TC-ERP-SO-014: Penanganan Sales Order dengan Nilai Transaksi Sangat Besar (> Rp 10 Miliar)
     */
    public function test_sales_order_handles_large_transaction_values(): void
    {
        $largeQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0008',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Mega BUMN Industri',
            'customer_name' => 'Direktur Utama',
            'subtotal' => 15750000000.00,
            'dpp' => 15750000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 1732500000.00,
            'grand_total' => 17482500000.00, // Rp 17.48 Miliar
            'total_cost' => 13500000000.00,
            'gross_profit_margin' => 14.28,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        $service = app(SalesOrderWorkflowService::class);
        $so = $service->convertFromQuotation($largeQuotation, [
            'customer_po_number' => 'PO-BUMN-2026-0001',
        ]);

        $this->assertEquals(17482500000.00, (float) $so->total_amount);
        $this->assertEquals('pending_director_approval', $so->status);
    }

    /**
     * TC-ERP-SO-015: Penomoran Dokumen Sales Order Bebas Race Condition
     */
    public function test_document_number_service_generates_unique_sales_order_numbers(): void
    {
        $num1 = DocumentNumberService::generateNext('SOR');
        $num2 = DocumentNumberService::generateNext('SOR');

        $this->assertStringStartsWith('PBB/SOR/', $num1);
        $this->assertStringStartsWith('PBB/SOR/', $num2);
        $this->assertNotEquals($num1, $num2);
    }

    /**
     * Render Halaman Tabel Sales Order
     */
    public function test_sales_orders_table_page_renders_successfully(): void
    {
        $this->actingAs($this->superAdmin);

        SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/09/0009',
            'quotation_id' => $this->approvedQuotation->id,
            'customer_po_number' => 'PO-UI-TEST',
            'total_amount' => 49950000.00,
            'status' => 'approved',
        ]);

        $response = $this->get(SalesOrderResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('PBB/SOR/2026/09/0009');
        $response->assertSee('PO-UI-TEST');
    }
}
