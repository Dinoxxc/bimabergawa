<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\CommissionCalculationDTO;
use App\Events\CommissionPayoutRequested;
use App\Models\CommissionPayout;
use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\CommissionPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class CommissionPayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommissionPayoutService $service;

    private FreelanceSales $agent;

    private SalesOrder $salesOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CommissionPayoutService;

        $tier = CommissionTier::factory()->create([
            'name' => 'Gold Partner',
            'commission_rate' => 3.00,
        ]);

        $user = User::factory()->create();

        $this->agent = FreelanceSales::factory()->create([
            'commission_tier_id' => $tier->id,
            'user_id' => $user->id,
            'name' => 'Ahmad Dahlan',
            'status' => 'approved',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Ahmad Dahlan',
        ]);

        $quotation = Quotation::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'status' => 'approved',
            'dpp' => 50000000.00,
            'vat_rate_applied' => 11.00,
            'grand_total' => 55500000.00,
        ]);

        $this->salesOrder = SalesOrder::factory()->create([
            'quotation_id' => $quotation->id,
            'order_number' => 'PBB/SOR/2026/09/0001',
            'status' => 'processing',
            'total_amount' => 55500000.00,
            'vat_rate_applied' => 11.00,
        ]);
    }

    public function test_calculate_commission_creates_pending_earned_entry_for_agent_sales_order(): void
    {
        $dto = $this->service->calculateCommission($this->salesOrder);

        $this->assertInstanceOf(CommissionCalculationDTO::class, $dto);
        $this->assertSame($this->salesOrder->id, $dto->salesOrderId);
        $this->assertSame($this->agent->id, $dto->agentId);
        $this->assertSame(50000000.00, $dto->subtotalEligible);
        $this->assertSame(3.00, $dto->tierRatePercent);
        $this->assertSame(1500000.00, $dto->calculatedAmount); // 3% dari 50 Juta

        $payout = CommissionPayout::where('sales_order_id', $this->salesOrder->id)->first();
        $this->assertNotNull($payout);
        $this->assertSame('earned', $payout->type);
        $this->assertSame('pending_payment', $payout->status);
        $this->assertEquals('1500000.00', $payout->amount);
    }

    public function test_mark_commission_as_payable_updates_status_to_payable_when_invoice_paid(): void
    {
        $this->service->calculateCommission($this->salesOrder);

        $payout = CommissionPayout::where('sales_order_id', $this->salesOrder->id)->first();
        $this->assertSame('pending_payment', $payout->status);

        // Klien melunasi faktur tagihan
        $this->service->markCommissionAsPayable($this->salesOrder);

        $payout->refresh();
        $this->assertSame('payable', $payout->status);
    }

    public function test_get_agent_balance_calculates_earned_paid_requested_and_payable_balance(): void
    {
        // 1. Komisi earned payable Rp 5.000.000
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        // 2. Komisi earned pending Rp 2.000.000 (belum lunas klien)
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 2000000.00,
            'type' => 'earned',
            'status' => 'pending_payment',
        ]);

        // 3. Komisi requested Rp 1.500.000 (sedang diajukan)
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 1500000.00,
            'type' => 'requested',
            'status' => 'pending_payment',
        ]);

        $balance = $this->service->getAgentBalance($this->agent->id);

        $this->assertSame(7000000.00, $balance['total_earned']);
        $this->assertSame(0.00, $balance['total_paid']);
        $this->assertSame(1500000.00, $balance['total_requested']);
        // Saldo siap cair = 5.000.000 - 1.500.000 = 3.500.000
        $this->assertSame(3500000.00, $balance['payable_balance']);
    }

    public function test_request_payout_creates_requested_entry_and_dispatches_event(): void
    {
        Event::fake([CommissionPayoutRequested::class]);

        // Berikan saldo siap cair Rp 3.000.000
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 3000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        $payout = $this->service->requestPayout($this->agent->id, 2000000.00);

        $this->assertInstanceOf(CommissionPayout::class, $payout);
        $this->assertSame('requested', $payout->type);
        $this->assertSame('pending_payment', $payout->status);
        $this->assertEquals('2000000.00', $payout->amount);
        $this->assertStringStartsWith('PBB/COM/', $payout->payout_number);

        // Saldo siap cair tersisa Rp 1.000.000
        $balance = $this->service->getAgentBalance($this->agent->id);
        $this->assertSame(1000000.00, $balance['payable_balance']);

        Event::assertDispatched(CommissionPayoutRequested::class, function ($e) use ($payout) {
            return $e->payoutId === $payout->id && $e->amount === 2000000.00;
        });
    }

    public function test_request_payout_rejects_below_minimum_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batas minimum pencairan komisi');

        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 3000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        // Coba cairkan Rp 300.000 (minimum default adalah Rp 500.000)
        $this->service->requestPayout($this->agent->id, 300000.00);
    }

    public function test_request_payout_rejects_overdraft_exceeding_payable_balance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh melebihi saldo yang tersedia');

        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 1000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        // Saldo Rp 1.000.000, coba cairkan Rp 1.500.000
        $this->service->requestPayout($this->agent->id, 1500000.00);
    }

    public function test_request_payout_idempotency_key_returns_existing_payout(): void
    {
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        $key = 'idem-agent-payout-12345';
        $p1 = $this->service->requestPayout($this->agent->id, 1000000.00, idempotencyKey: $key);
        $p2 = $this->service->requestPayout($this->agent->id, 1000000.00, idempotencyKey: $key);

        $this->assertSame($p1->id, $p2->id);
        $this->assertSame($p1->payout_number, $p2->payout_number);

        // Hanya 1 tiket pencairan yang terbuat
        $this->assertSame(1, CommissionPayout::where('type', 'requested')->count());
    }

    public function test_request_payout_atomic_mutex_lock_rejects_concurrent_request(): void
    {
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        // Simulasikan mutex lock sedang dipegang oleh thread paralel lain
        $lockKey = 'agent_payout_lock_'.$this->agent->id;
        Cache::lock($lockKey, 10)->get();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Permintaan pencairan sedang diproses. Harap tunggu.');

        try {
            $this->service->requestPayout($this->agent->id, 1000000.00);
        } finally {
            Cache::lock($lockKey, 10)->release();
        }
    }

    public function test_mark_as_paid_updates_status_and_proof_path(): void
    {
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        $payout = $this->service->requestPayout($this->agent->id, 2500000.00);
        $this->assertSame('pending_payment', $payout->status);

        $paidPayout = $this->service->markAsPaid($payout, 'transfer-proofs/bca-slip-001.pdf', 'Transfer berhasil');

        $this->assertSame('paid', $paidPayout->status);
        $this->assertSame('transfer-proofs/bca-slip-001.pdf', $paidPayout->transfer_proof_path);
        $this->assertNotNull($paidPayout->paid_at);

        // Saldo terbayar sekarang Rp 2.500.000, saldo sisa siap cair Rp 2.500.000
        $balance = $this->service->getAgentBalance($this->agent->id);
        $this->assertSame(2500000.00, $balance['total_paid']);
        $this->assertSame(2500000.00, $balance['payable_balance']);
    }

    public function test_reverse_commission_rejects_pending_commission_on_sales_order_cancellation(): void
    {
        $this->service->calculateCommission($this->salesOrder);

        $payout = CommissionPayout::where('sales_order_id', $this->salesOrder->id)->first();
        $this->assertSame('pending_payment', $payout->status);

        // Klien membatalkan Sales Order
        $this->service->reverseCommissionForCancelledSalesOrder($this->salesOrder, 'Klien membatalkan PO proyek');

        $payout->refresh();
        $this->assertSame('rejected', $payout->status);
        $this->assertStringContainsString('Reversal', $payout->notes);
    }

    public function test_calculate_commission_does_not_downgrade_already_payable_or_paid_commission(): void
    {
        $this->service->calculateCommission($this->salesOrder);
        $this->service->markCommissionAsPayable($this->salesOrder);

        $payout = CommissionPayout::where('sales_order_id', $this->salesOrder->id)->first();
        $this->assertSame('payable', $payout->status);

        // Rekalkulasi komisi ulang (misal: penyesuaian catatan)
        $this->service->calculateCommission($this->salesOrder);

        $payout->refresh();
        $this->assertSame('payable', $payout->status); // Status tidak boleh turun ke pending_payment
    }

    public function test_reverse_commission_reverses_payable_commission_and_creates_clawback_for_paid_commission(): void
    {
        // 1. Uji pembalikan komisi yang sudah berstatus payable
        $payablePayout = CommissionPayout::factory()->create([
            'sales_order_id' => $this->salesOrder->id,
            'freelance_sales_id' => $this->agent->id,
            'type' => 'earned',
            'status' => 'payable',
            'amount' => 1000000.00,
        ]);

        $this->service->reverseCommissionForCancelledSalesOrder($this->salesOrder, 'Batal proyek');
        $payablePayout->refresh();
        $this->assertSame('rejected', $payablePayout->status);

        // 2. Uji pencatatan deduction / clawback jika komisi sudah terlanjur dibayarkan (status = paid)
        $paidPayout = CommissionPayout::factory()->create([
            'sales_order_id' => $this->salesOrder->id,
            'freelance_sales_id' => $this->agent->id,
            'type' => 'earned',
            'status' => 'paid',
            'amount' => 1500000.00,
        ]);

        $this->service->reverseCommissionForCancelledSalesOrder($this->salesOrder, 'Klien wanprestasi');

        $clawback = CommissionPayout::where('sales_order_id', $this->salesOrder->id)
            ->where('type', 'deducted')
            ->first();

        $this->assertNotNull($clawback);
        $this->assertSame('paid', $clawback->status);
        $this->assertEquals('1500000.00', $clawback->amount);
        $this->assertStringContainsString('Clawback', $clawback->notes);
    }

    public function test_get_agent_balance_accounts_for_clawback_deductions(): void
    {
        // Saldo earned payable Rp 5.000.000
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        // Pemotongan clawback Rp 1.500.000
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 1500000.00,
            'type' => 'deducted',
            'status' => 'paid',
        ]);

        $balance = $this->service->getAgentBalance($this->agent->id);
        $this->assertSame(1500000.00, $balance['total_deducted']);
        $this->assertSame(3500000.00, $balance['payable_balance']); // 5 Juta - 1.5 Juta = 3.5 Juta
    }

    public function test_request_payout_masks_bank_account_in_notes_for_uu_pdp_compliance(): void
    {
        CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 5000000.00,
            'type' => 'earned',
            'status' => 'payable',
        ]);

        $payout = $this->service->requestPayout($this->agent->id, 1000000.00);

        // Rekening agen adalah 1234567890 -> wajib di-mask menjadi ******7890
        $this->assertStringNotContainsString('1234567890', $payout->notes);
        $this->assertStringContainsString('7890', $payout->notes);
        $this->assertStringContainsString('*', $payout->notes);
    }

    public function test_mark_as_paid_rejects_rejected_payout(): void
    {
        $payout = CommissionPayout::factory()->create([
            'freelance_sales_id' => $this->agent->id,
            'amount' => 1000000.00,
            'type' => 'requested',
            'status' => 'rejected',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('telah ditolak tidak dapat dibayarkan');

        $this->service->markAsPaid($payout, 'proof.pdf');
    }
}
