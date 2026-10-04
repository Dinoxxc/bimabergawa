<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Agent\Resources\AgentInquiryResource\Pages\CreateAgentInquiry;
use App\Models\CommissionPayout;
use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\InternalComment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\CommissionPayoutService;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentCommissionPortalTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $agentRole;

    protected Role $adminRole;

    protected Role $financeRole;

    protected CommissionTier $silverTier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agentRole = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);
        $this->adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->financeRole = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);

        $this->silverTier = CommissionTier::firstOrCreate(
            ['name' => 'Silver Tier'],
            [
                'min_turnover_amount' => 50000000.00,
                'commission_rate' => 2.50,
                'is_active' => true,
            ]
        );

        Storage::fake('local_private');
    }

    /**
     * Helper membuat akun agen freelance lengkap dengan relasi User dan profil.
     */
    protected function createAgent(string $name = 'Budi Santoso', string $email = 'budi@example.com'): array
    {
        $unique = time().'_'.rand(1000, 9999);
        $user = User::create([
            'name' => $name,
            'email' => $unique.'_'.$email,
            'phone' => '08123456789',
            'is_active' => true,
        ]);
        $user->assignRole($this->agentRole);

        $agent = FreelanceSales::create([
            'user_id' => $user->id,
            'commission_tier_id' => $this->silverTier->id,
            'name' => $name,
            'referral_code' => 'AGT-'.date('Ymd').'-'.$unique,
            'nik' => '3171012345670001',
            'npwp_number' => '01.234.567.8-123.000',
            'ktp_file_path' => 'private/identity/ktp_sample.jpg',
            'bank_name' => 'Bank Central Asia (BCA)',
            'bank_account_number' => '1234567890',
            'bank_account_name' => $name,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $user->update(['freelance_sales_id' => $agent->id]);

        return [$user, $agent];
    }

    // =========================================================================
    // DOMAIN 01: REGISTRASI AGEN, PROFIL, ENKRIPSI, & APPROVAL
    // =========================================================================

    /**
     * TC-AGN-REG-001: Pendaftaran Akun Agen Mandiri Lengkap
     */
    public function test_agent_registration_creates_pending_account_and_stores_ktp(): void
    {
        $ktpFile = UploadedFile::fake()->create('ktp_budi.jpg', 500, 'image/jpeg');

        $response = $this->post(route('agent.register.submit'), [
            'name' => 'Calon Agen Sukses',
            'email' => 'calon_'.time().'@example.com',
            'phone' => '08987654321',
            'nik' => '3201012345670002',
            'npwp_number' => '98.765.432.1-001.000',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '9876543210',
            'bank_account_name' => 'Calon Agen Sukses',
            'industry_focus' => 'Factory Automation',
            'ktp_file' => $ktpFile,
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('freelance_sales', [
            'name' => 'Calon Agen Sukses',
            'bank_name' => 'Bank Mandiri',
            'bank_account_name' => 'Calon Agen Sukses',
            'status' => 'pending_review',
        ]);
    }

    /**
     * TC-AGN-REG-002 & TC-AGN-REG-003: Enkripsi Otomatis NIK & Rekening Bank At-Rest
     */
    public function test_nik_and_bank_details_are_encrypted_at_rest_in_database(): void
    {
        [$user, $agent] = $this->createAgent('Enkripsi Test', 'encrypt@example.com');

        // Inspeksi raw database query
        $rawRecord = DB::table('freelance_sales')->where('id', $agent->id)->first();

        // 1. NIK tidak boleh tersimpan dalam bentuk teks polos
        $this->assertNotEquals('3171012345670001', $rawRecord->nik);
        $this->assertStringStartsWith('eyJpdiI6', $rawRecord->nik);

        // 2. Rekening bank tidak boleh tersimpan dalam teks polos
        $this->assertNotEquals('1234567890', $rawRecord->bank_account_number);
        $this->assertStringStartsWith('eyJpdiI6', $rawRecord->bank_account_number);

        // 3. Dekripsi transparan saat diakses via Eloquent Model
        $freshAgent = FreelanceSales::find($agent->id);
        $this->assertEquals('3171012345670001', $freshAgent->nik);
        $this->assertEquals('1234567890', $freshAgent->bank_account_number);

        // 4. Data terlindungi dari serialisasi array/JSON (UU PDP No. 27/2022)
        $arrayData = $freshAgent->toArray();
        $this->assertArrayNotHasKey('nik', $arrayData);
        $this->assertArrayNotHasKey('bank_account_number', $arrayData);
    }

    /**
     * TC-AGN-REG-007: Validasi Format NIK Bukan 16 Digit
     */
    public function test_registration_rejects_invalid_nik_format(): void
    {
        $ktpFile = UploadedFile::fake()->create('ktp.jpg', 200, 'image/jpeg');

        $response = $this->post(route('agent.register.submit'), [
            'name' => 'Budi Salah',
            'email' => 'salah_'.time().'@example.com',
            'phone' => '0812345',
            'nik' => '3171012345', // 10 digit (invalid)
            'bank_name' => 'BCA',
            'bank_account_number' => '1234',
            'bank_account_name' => 'Budi',
            'ktp_file' => $ktpFile,
        ]);

        $response->assertSessionHasErrors(['nik']);
    }

    /**
     * TC-AGN-REG-008: Validasi Email Duplikat
     */
    public function test_registration_rejects_duplicate_email(): void
    {
        [$user, $agent] = $this->createAgent('Agen Lama', 'existing@example.com');
        $ktpFile = UploadedFile::fake()->create('ktp.jpg', 200, 'image/jpeg');

        $response = $this->post(route('agent.register.submit'), [
            'name' => 'Agen Kembar',
            'email' => $user->email, // duplicate
            'phone' => '0812345',
            'nik' => '3171012345670009',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234',
            'bank_account_name' => 'Kembar',
            'ktp_file' => $ktpFile,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * TC-AGN-REG-009: Penolakan Format Berkas KTP Tidak Valid
     */
    public function test_registration_rejects_unallowed_file_extension(): void
    {
        $badFile = UploadedFile::fake()->create('ktp.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->post(route('agent.register.submit'), [
            'name' => 'Budi Bad File',
            'email' => 'badfile_'.time().'@example.com',
            'phone' => '0812345',
            'nik' => '3171012345670009',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234',
            'bank_account_name' => 'Budi',
            'ktp_file' => $badFile,
        ]);

        $response->assertSessionHasErrors(['ktp_file']);
    }

    // =========================================================================
    // DOMAIN 02: INKUIRI AGEN, TENANT ISOLATION, & ZERO LEAK
    // =========================================================================

    /**
     * TC-AGN-INQ-001 & TC-AGN-INQ-011: Input Inkuiri oleh Agen & Anti-Spoofing Agent ID
     */
    public function test_agent_inquiry_submission_forces_authenticated_agent_id(): void
    {
        [$userA, $agentA] = $this->createAgent('Agen Alpha', 'alpha@example.com');
        [$userB, $agentB] = $this->createAgent('Agen Beta', 'beta@example.com');

        $this->actingAs($userA);

        $page = new CreateAgentInquiry;

        // Simulasi input form dari user A dengan percobaan spoofing freelance_sales_id milik agent B
        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $mutated = $method->invoke($page, [
            'company_name' => 'PT Prospek Industri',
            'customer_name' => 'Hendra',
            'phone_wa' => '081211112222',
            'freelance_sales_id' => $agentB->id, // Mencoba memalsukan ID Agen B
        ]);

        // Verifikasi anti-spoofing: ID Agen B harus diabaikan dan dipaksa menjadi ID Agen A
        $this->assertEquals($agentA->id, $mutated['freelance_sales_id']);
        $this->assertEquals('new', $mutated['status']);
        $this->assertNotEmpty($mutated['inquiry_number']);
    }

    /**
     * TC-AGN-INQ-010: Penyembunyian Seluruh Informasi Margin HPP dari Tampilan Agen
     */
    public function test_agent_views_do_not_contain_hpp_cost_price_or_margin_fields(): void
    {
        $inquiryResourceCode = file_get_contents(app_path('Filament/Agent/Resources/AgentInquiryResource.php'));
        $commissionResourceCode = file_get_contents(app_path('Filament/Agent/Resources/AgentCommissionResource.php'));

        // Verifikasi 0% HPP leak: kode antarmuka agen tidak mengekspos field margin, cost price, atau hpp
        $this->assertStringNotContainsString("'cost_price'", $inquiryResourceCode);
        $this->assertStringNotContainsString("'base_price'", $inquiryResourceCode);
        $this->assertStringNotContainsString("'purchase_price'", $inquiryResourceCode);
        $this->assertStringNotContainsString("'margin'", $inquiryResourceCode);

        $this->assertStringNotContainsString("'cost_price'", $commissionResourceCode);
        $this->assertStringNotContainsString("'base_price'", $commissionResourceCode);
        $this->assertStringNotContainsString("'margin'", $commissionResourceCode);
    }

    /**
     * TC-AGN-INQ-018: Catatan Internal Staf 100% Terfilter dari Agen Freelance
     */
    public function test_internal_staff_notes_are_strictly_isolated_from_agents(): void
    {
        [$userA, $agentA] = $this->createAgent('Agen Alpha', 'alpha_notes@example.com');

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-NOTE-'.time(),
            'freelance_sales_id' => $agentA->id,
            'customer_name' => 'PT Prospek Catatan',
            'company_name' => 'PT Prospek Catatan',
            'phone_wa' => '08123456781',
            'type' => 'single_direct',
            'source' => 'referral',
            'status' => 'new',
        ]);

        // 1. Staf internal membuat catatan internal rahasia (berisi HPP & target margin)
        $internalStaff = User::create([
            'name' => 'Sales PIC Internal',
            'email' => 'salespic_'.time().'@pbb.com',
            'is_active' => true,
        ]);
        $internalStaff->assignRole(Role::firstOrCreate(['name' => 'sales_pic', 'guard_name' => 'web']));

        $secretComment = InternalComment::create([
            'user_id' => $internalStaff->id,
            'commentable_type' => Inquiry::class,
            'commentable_id' => $inquiry->id,
            'comment' => 'Rahasia: Modal HPP adalah Rp 4.500.000 dengan margin 18%',
            'type' => 'internal',
        ]);

        // 2. Komentar koordinasi publik agen
        $publicComment = InternalComment::create([
            'user_id' => $internalStaff->id,
            'commentable_type' => Inquiry::class,
            'commentable_id' => $inquiry->id,
            'comment' => 'Brosur Schneider dan surat penawaran sudah siap dikirim ke klien.',
            'type' => 'agent_visible',
        ]);

        // 3. Login sebagai agen: verifikasi global scope memfilter catatan rahasia
        $this->actingAs($userA);

        $visibleComments = InternalComment::where('commentable_id', $inquiry->id)->get();

        $this->assertFalse($visibleComments->contains('id', $secretComment->id));
        $this->assertTrue($visibleComments->contains('id', $publicComment->id));
    }

    // =========================================================================
    // DOMAIN 03: KALKULASI KOMISI, LEDGER, PENCAIRAN, & APPROVAL
    // =========================================================================

    /**
     * TC-AGN-COM-001 & TC-AGN-COM-002: Kalkulasi Otomatis Hak Komisi SO & Aktivasi Status Payable
     */
    public function test_commission_calculated_on_sales_order_and_becomes_payable_on_invoice(): void
    {
        [$userAgent, $agent] = $this->createAgent('Agen Komisi', 'komisi@example.com');

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-SO-'.time(),
            'freelance_sales_id' => $agent->id,
            'customer_name' => 'PT Prospek Komisi',
            'company_name' => 'PT Prospek Komisi',
            'phone_wa' => '08123456781',
            'type' => 'single_direct',
            'source' => 'referral',
            'status' => 'deal',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-SO-'.time(),
            'inquiry_id' => $inquiry->id,
            'freelance_sales_id' => $agent->id,
            'user_id' => $userAgent->id,
            'customer_name' => 'PT Prospek Komisi',
            'company_name' => 'PT Prospek Komisi',
            'dpp' => 50000000.00, // Rp 50.000.000
            'subtotal' => 50000000.00,
            'vat_rate_applied' => 12.00,
            'tax_amount' => 6000000.00,
            'grand_total' => 56000000.00,
            'status' => 'approved',
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-TEST-'.time(),
            'quotation_id' => $quotation->id,
            'customer_po_number' => 'PO-CLIENT-999',
            'total_amount' => 56000000.00,
            'vat_rate_applied' => 12.00,
            'status' => 'approved',
        ]);

        $service = app(CommissionPayoutService::class);

        // 1. Kalkulasi komisi: 2.5% x Rp 50.000.000 = Rp 1.250.000
        $dto = $service->calculateCommission($salesOrder);
        $this->assertNotNull($dto);
        $this->assertEquals(1250000.00, $dto->calculatedAmount);

        $earnedPayout = CommissionPayout::where('sales_order_id', $salesOrder->id)
            ->where('type', 'earned')
            ->first();

        $this->assertNotNull($earnedPayout);
        $this->assertEquals('pending_payment', $earnedPayout->status);

        // 2. Saldo siap cair sebelum invoice lunas masih 0
        $balanceBefore = $service->getAgentBalance($agent->id);
        $this->assertEquals(0.00, $balanceBefore['payable_balance']);

        // 3. Faktur lunas -> tandai komisi menjadi payable
        $service->markCommissionAsPayable($salesOrder);

        $earnedPayout->refresh();
        $this->assertEquals('payable', $earnedPayout->status);

        $balanceAfter = $service->getAgentBalance($agent->id);
        $this->assertEquals(1250000.00, $balanceAfter['payable_balance']);
    }

    /**
     * TC-AGN-COM-008: Penolakan Pengajuan di Bawah Batas Minimum (< Rp 500.000)
     */
    public function test_payout_request_below_minimum_amount_is_rejected(): void
    {
        [$userAgent, $agent] = $this->createAgent('Agen Min Payout', 'minpayout@example.com');
        $service = app(CommissionPayoutService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batas minimum pencairan komisi adalah Rp 500.000');

        $service->requestPayout($agent->id, 250000.00);
    }

    /**
     * TC-AGN-COM-010: Penolakan Pengajuan Melebihi Saldo Aktif (Overdraft Attempt)
     */
    public function test_payout_request_exceeding_payable_balance_is_rejected(): void
    {
        [$userAgent, $agent] = $this->createAgent('Agen Overdraft', 'overdraft@example.com');
        $service = app(CommissionPayoutService::class);

        // Agen belum memiliki saldo payable
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh melebihi saldo yang tersedia');

        $service->requestPayout($agent->id, 1000000.00);
    }

    /**
     * TC-AGN-COM-003, TC-AGN-COM-004, TC-AGN-COM-005, TC-AGN-COM-007: Siklus Payout Lengkap & PDF Voucher
     */
    public function test_full_payout_lifecycle_approval_transfer_and_pdf_generation(): void
    {
        [$userAgent, $agent] = $this->createAgent('Agen Sukses Payout', 'sukses_payout@example.com');
        $service = app(CommissionPayoutService::class);

        // Buat hak komisi senilai Rp 10.000.000 yang sudah payable
        CommissionPayout::create([
            'freelance_sales_id' => $agent->id,
            'sales_order_id' => null,
            'amount' => 10000000.00,
            'type' => 'earned',
            'status' => 'payable',
            'notes' => 'Hak komisi proyek gedung perkantoran',
        ]);

        $this->assertEquals(10000000.00, $service->getAgentBalance($agent->id)['payable_balance']);

        // 1. Agen mengajukan pencairan Rp 5.000.000
        $payoutTicket = $service->requestPayout(
            agentId: $agent->id,
            amount: 5000000.00,
            notes: 'Pencairan komisi termin 1'
        );

        $this->assertEquals('pending_payment', $payoutTicket->status);
        $this->assertEquals('requested', $payoutTicket->type);
        $this->assertStringStartsWith('PBB/COM/', $payoutTicket->payout_number);

        // Sisa saldo siap cair menjadi Rp 5.000.000
        $this->assertEquals(5000000.00, $service->getAgentBalance($agent->id)['payable_balance']);

        // 2. Finance menyetujui pengajuan
        $financeUser = User::create([
            'name' => 'Staff Finance',
            'email' => 'finance_'.time().'@pbb.com',
            'is_active' => true,
        ]);
        $financeUser->assignRole($this->financeRole);

        $approvedPayout = $service->approvePayout($payoutTicket, $financeUser->id, false);
        $this->assertEquals('payable', $approvedPayout->status);

        // 3. Finance mengeksekusi transfer dan unggah bukti struk bank
        $transferProof = 'private/transfers/trf_sample_123.jpg';
        $settledPayout = $service->markAsPaid($approvedPayout, $transferProof, 'Ref: TRF-BCA-998877');

        $this->assertEquals('paid', $settledPayout->status);
        $this->assertNotNull($settledPayout->paid_at);
        $this->assertEquals($transferProof, $settledPayout->transfer_proof_path);

        // 4. Generate voucher PDF resmi
        $pdf = PdfExportService::generateCommissionVoucherPdf($settledPayout, false);
        $this->assertNotEmpty($pdf->output());
    }

    /**
     * TC-AGN-COM-016: Eskalasi Approval Direktur untuk Pencairan Komisi Besar (>= Rp 25 Juta)
     */
    public function test_director_approval_limit_escalation_for_large_payouts(): void
    {
        [$userAgent, $agent] = $this->createAgent('Agen Proyek Mega', 'mega@example.com');
        $service = app(CommissionPayoutService::class);

        // Buat hak komisi senilai Rp 30.000.000 yang sudah payable
        CommissionPayout::create([
            'freelance_sales_id' => $agent->id,
            'sales_order_id' => null,
            'amount' => 30000000.00,
            'type' => 'earned',
            'status' => 'payable',
            'notes' => 'Hak komisi proyek data center mega watt',
        ]);

        // Agen mengajukan pencairan Rp 28.000.000 (>= Rp 25 Juta)
        $payoutTicket = $service->requestPayout(
            agentId: $agent->id,
            amount: 28000000.00,
            notes: 'Pencairan komisi proyek data center'
        );

        $financeUser = User::create([
            'name' => 'Staff Finance',
            'email' => 'fin_'.time().'@pbb.com',
            'is_active' => true,
        ]);
        $financeUser->assignRole($this->financeRole);

        // 1. Finance biasa (non-Direktur) mencoba approve -> status tetap pending_payment (eskalasi)
        $escalatedPayout = $service->approvePayout($payoutTicket, $financeUser->id, false);
        $this->assertEquals('pending_payment', $escalatedPayout->status);
        $this->assertStringContainsString('Menunggu Approval Direktur', $escalatedPayout->notes);

        // 2. Direktur / Super Admin melakukan approval -> status berubah menjadi payable
        $directorApproved = $service->approvePayout($escalatedPayout, 1, true);
        $this->assertEquals('payable', $directorApproved->status);
        $this->assertStringContainsString('(Direktur)', $directorApproved->notes);
    }
}
