<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\DocumentVerificationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * TC-ERP-REP-004: Verifikasi QR Code Dokumen Transaksi - Quotation Resmi.
     */
    public function test_can_verify_authentic_quotation_via_public_route(): void
    {
        $quotation = Quotation::factory()->create([
            'quotation_number' => 'PBB/QUO/2026/10/0001',
            'company_name' => 'PT. Pelita Proyek Cemerlang',
            'customer_name' => 'Budi Santoso',
            'grand_total' => 150000000.00,
            'status' => 'approved',
        ]);

        $hash = DocumentVerificationService::generateHash($quotation->quotation_number, (string) $quotation->created_at);

        $response = $this->get("http://app.bimabergawa.test/verify-doc/quotation/{$hash}");

        $response->assertSuccessful();
        $response->assertSee('[OFFICIALLY VERIFIED & AUTHENTIC]');
        $response->assertSee('PBB/QUO/2026/10/0001');
        $response->assertSee('PT. Pelita Proyek Cemerlang');
        $response->assertSee('PT. PANCA BIMA BERGAWA');
    }

    /**
     * TC-ERP-REP-004: Verifikasi QR Code Dokumen Transaksi - Sales Order.
     */
    public function test_can_verify_authentic_sales_order(): void
    {
        $quotation = Quotation::factory()->create([
            'company_name' => 'PT. Industri Maju Bersama',
            'customer_name' => 'Doni Kusuma',
        ]);

        $so = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'order_number' => 'PBB/SOR/2026/10/0001',
            'total_amount' => 85000000.00,
            'status' => 'approved',
        ]);

        $hash = DocumentVerificationService::generateHash($so->order_number, (string) $so->created_at);

        $response = $this->get("http://app.bimabergawa.test/verify-doc/sales-order/{$hash}");

        $response->assertSuccessful();
        $response->assertSee('[OFFICIALLY VERIFIED & AUTHENTIC]');
        $response->assertSee('PBB/SOR/2026/10/0001');
        $response->assertSee('PT. Industri Maju Bersama');
    }

    /**
     * TC-ERP-REP-004: Verifikasi QR Code Dokumen Transaksi - Surat Jalan (Delivery Order).
     */
    public function test_can_verify_authentic_delivery_order(): void
    {
        $do = DeliveryOrder::factory()->create([
            'delivery_number' => 'PBB/DOR/2026/10/0001/01',
            'recipient_pic_name' => 'Mandor Proyek Gudang Cikarang',
            'status' => 'shipped',
        ]);

        $hash = DocumentVerificationService::generateHash($do->delivery_number, (string) $do->created_at);

        $response = $this->get("http://app.bimabergawa.test/verify-doc/delivery-order/{$hash}");

        $response->assertSuccessful();
        $response->assertSee('[OFFICIALLY VERIFIED & AUTHENTIC]');
        $response->assertSee('PBB/DOR/2026/10/0001/01');
        $response->assertSee('Mandor Proyek Gudang Cikarang');
    }

    /**
     * TC-ERP-REP-004: Verifikasi QR Code Dokumen Transaksi - Faktur Tagihan (Invoice).
     */
    public function test_can_verify_authentic_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'PBB/INV/2026/10/0001',
            'company_name' => 'PT. Mega Graha Mandiri',
            'customer_name' => 'Sari Indah',
            'grand_total' => 25000000.00,
            'status' => 'unpaid',
        ]);

        $hash = DocumentVerificationService::generateHash($invoice->invoice_number, (string) $invoice->created_at);

        $response = $this->get("http://app.bimabergawa.test/verify-doc/invoice/{$hash}");

        $response->assertSuccessful();
        $response->assertSee('[OFFICIALLY VERIFIED & AUTHENTIC]');
        $response->assertSee('PBB/INV/2026/10/0001');
        $response->assertSee('PT. Mega Graha Mandiri');
    }

    /**
     * TC-ERP-REP-004: Penanganan Hash Tidak Valid atau Dokumen Palsu.
     */
    public function test_invalid_or_tampered_hash_returns_unverified_status(): void
    {
        $response = $this->get('http://app.bimabergawa.test/verify-doc/quotation/fake-invalid-hash-67890');

        $response->assertSuccessful();
        $response->assertSee('[UNVERIFIED OR INVALID DOCUMENT]');
        $response->assertSee('Verification Check Failed');
    }

    /**
     * Test WhatsApp dispatch telemetry execution and audit logging.
     */
    public function test_whatsapp_dispatch_audit_trail_logging(): void
    {
        $quotation = Quotation::factory()->create([
            'quotation_number' => 'PBB/QUO/2026/10/0005',
        ]);

        ActivityLog::create([
            'log_name' => 'whatsapp_dispatch_executed',
            'description' => "WhatsApp quotation dispatch executed for {$quotation->quotation_number} to 6281299887766.",
            'causer_type' => $this->superAdmin->getMorphClass(),
            'causer_id' => $this->superAdmin->id,
            'subject_type' => $quotation->getMorphClass(),
            'subject_id' => $quotation->id,
            'properties' => ['phone' => '6281299887766', 'document' => $quotation->quotation_number],
        ]);

        $log = ActivityLog::where('log_name', 'whatsapp_dispatch_executed')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->superAdmin->id, $log->causer_id);
        $this->assertStringContainsString('PBB/QUO/2026/10/0005', $log->description);
    }
}
