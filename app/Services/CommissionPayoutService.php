<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CommissionCalculationDTO;
use App\Events\CommissionPayoutRequested;
use App\Models\CommissionPayout;
use App\Models\FreelanceSales;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CommissionPayoutService
{
    public const DEFAULT_MIN_PAYOUT_AMOUNT = 500000.00;

    public const DEFAULT_DIRECTOR_PAYOUT_LIMIT = 25000000.00;

    public const DEFAULT_COMMISSION_RATE = 2.50;

    protected DocumentNumberService $documentNumberService;

    public function __construct(?DocumentNumberService $documentNumberService = null)
    {
        $this->documentNumberService = $documentNumberService ?? new DocumentNumberService;
    }

    /**
     * Hitung hak komisi mitra agen freelance saat Sales Order diterbitkan/disetujui.
     * Mengambil persentase komisi dari tier aktif agen atau kesepakatan penawaran,
     * menghitung nominal dari Dasar Pengenaan Pajak (DPP), dan mencatat baris ledger 'earned'.
     */
    public function calculateCommission(SalesOrder $salesOrder): ?CommissionCalculationDTO
    {
        $quotation = $salesOrder->quotation;
        $agentId = $quotation?->freelance_sales_id
            ?? $quotation?->inquiry?->freelance_sales_id
            ?? null;

        if ($agentId === null) {
            return null;
        }

        $agent = FreelanceSales::find($agentId);
        if (! $agent) {
            return null;
        }

        // Hitung DPP (Dasar Pengenaan Pajak / nilai subtotal sebelum PPN)
        $dpp = $quotation->dpp > 0.0
            ? (float) $quotation->dpp
            : round((float) $salesOrder->total_amount / (1 + ((float) $salesOrder->vat_rate_applied / 100)), 2);

        // Ambil tarif komisi resmi dari tier aktif agen atau fallback ke penawaran
        $tierRate = (float) ($agent->tier?->commission_rate
            ?? $quotation->commission_rate
            ?? self::DEFAULT_COMMISSION_RATE);

        $calculatedAmount = round($dpp * ($tierRate / 100), 2);

        // Catat entri komisi pada buku besar (commission_payouts) tanpa menurunkan status yang sudah payable / paid
        $existingPayout = CommissionPayout::where('sales_order_id', $salesOrder->id)
            ->where('freelance_sales_id', $agentId)
            ->where('type', 'earned')
            ->first();

        if ($existingPayout && in_array($existingPayout->status, ['payable', 'paid'], true)) {
            $existingPayout->update([
                'amount' => $calculatedAmount,
                'notes' => sprintf(
                    'Hak komisi Sales Order %s diperbarui (DPP: Rp %s, Rate: %.2f%%) [Status dipertahankan: %s]',
                    $salesOrder->order_number,
                    number_format($dpp, 2, ',', '.'),
                    $tierRate,
                    $existingPayout->status
                ),
            ]);
        } else {
            CommissionPayout::updateOrCreate(
                [
                    'sales_order_id' => $salesOrder->id,
                    'freelance_sales_id' => $agentId,
                    'type' => 'earned',
                ],
                [
                    'amount' => $calculatedAmount,
                    'status' => 'pending_payment',
                    'notes' => sprintf(
                        'Hak komisi Sales Order %s (DPP: Rp %s, Rate: %.2f%%)',
                        $salesOrder->order_number,
                        number_format($dpp, 2, ',', '.'),
                        $tierRate
                    ),
                ]
            );
        }

        return new CommissionCalculationDTO(
            salesOrderId: $salesOrder->id,
            agentId: $agentId,
            subtotalEligible: $dpp,
            tierRatePercent: $tierRate,
            calculatedAmount: $calculatedAmount
        );
    }

    /**
     * Tandai komisi transaksi menjadi siap dicairkan (payable) saat faktur tagihan klien telah lunas.
     */
    public function markCommissionAsPayable(SalesOrder|int $salesOrder): void
    {
        $soId = $salesOrder instanceof SalesOrder ? $salesOrder->id : $salesOrder;

        CommissionPayout::where('sales_order_id', $soId)
            ->where('type', 'earned')
            ->where('status', 'pending_payment')
            ->update(['status' => 'payable']);
    }

    /**
     * Hitung ringkasan saldo komisi agen: total perolehan, total terbayar, total potongan, dan saldo siap cair.
     *
     * @return array{
     *     agent_id: int,
     *     total_earned: float,
     *     total_paid: float,
     *     total_requested: float,
     *     total_deducted: float,
     *     payable_balance: float
     * }
     */
    public function getAgentBalance(int $agentId): array
    {
        $earnedPayable = (float) CommissionPayout::where('freelance_sales_id', $agentId)
            ->where('type', 'earned')
            ->where('status', 'payable')
            ->sum('amount');

        $totalEarned = (float) CommissionPayout::where('freelance_sales_id', $agentId)
            ->where('type', 'earned')
            ->where('status', '!=', 'rejected')
            ->sum('amount');

        $totalRequested = (float) CommissionPayout::where('freelance_sales_id', $agentId)
            ->where('type', 'requested')
            ->whereIn('status', ['pending_payment', 'payable'])
            ->sum('amount');

        $totalPaid = (float) CommissionPayout::where('freelance_sales_id', $agentId)
            ->where(function ($q) {
                $q->where('type', 'paid')
                    ->orWhere(function ($sq) {
                        $sq->where('type', 'requested')
                            ->where('status', 'paid');
                    });
            })
            ->sum('amount');

        // Total pemotongan / clawback akibat pembatalan pesanan yang telah dibayarkan
        $totalDeducted = (float) CommissionPayout::where('freelance_sales_id', $agentId)
            ->where('type', 'deducted')
            ->sum('amount');

        $payableBalance = max(0.0, round($earnedPayable - $totalRequested - $totalPaid - $totalDeducted, 2));

        return [
            'agent_id' => $agentId,
            'total_earned' => round($totalEarned, 2),
            'total_paid' => round($totalPaid, 2),
            'total_requested' => round($totalRequested, 2),
            'total_deducted' => round($totalDeducted, 2),
            'payable_balance' => $payableBalance,
        ];
    }

    /**
     * Pengajuan pencairan komisi mandiri oleh mitra agen freelance.
     * Dilengkapi proteksi Atomic Mutex Lock untuk mencegah penarikan ganda serentak (TC-AGN-COM-011),
     * validasi batas minimum pencairan, serta evaluasi batas approval Direktur (TC-AGN-COM-016).
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function requestPayout(
        int $agentId,
        float $amount,
        ?string $idempotencyKey = null,
        ?string $notes = null
    ): CommissionPayout {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pengajuan pencairan komisi harus lebih besar dari 0.');
        }

        // Idempotency check: jika permintaan dengan kunci sama sudah tercatat, kembalikan data eksisting
        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = CommissionPayout::where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        // Proteksi Atomic Mutex Lock (TC-AGN-COM-011)
        $lockKey = 'agent_payout_lock_'.$agentId;
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            // Pola Double-Checked Locking: jika lock gagal, periksa apakah permintaan dengan idempotency key berhasil dibuat di thread sebelah
            if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
                $existing = CommissionPayout::where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return $existing;
                }
            }

            throw new RuntimeException('Permintaan pencairan sedang diproses. Harap tunggu.');
        }

        try {
            // Pola Double-Checked Locking: setelah lock didapatkan, pastikan tidak ada record dibuat sebelum lock release
            if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
                $existing = CommissionPayout::where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return $existing;
                }
            }

            $agent = FreelanceSales::findOrFail($agentId);
            if ($agent->status !== 'approved') {
                throw new InvalidArgumentException('Hanya agen resmi berstatus disetujui (approved) yang dapat mengajukan pencairan komisi.');
            }

            // Validasi kelengkapan data perbankan tujuan pencairan
            if (empty($agent->bank_name) || empty($agent->bank_account_number)) {
                throw new InvalidArgumentException('Profil perbankan agen (nama bank dan nomor rekening) wajib dilengkapi sebelum mengajukan pencairan.');
            }

            // Validasi batas minimum pencairan (default Rp 500.000)
            $minPayout = SystemSettingService::getFloat(
                'commission.min_payout_amount',
                self::DEFAULT_MIN_PAYOUT_AMOUNT
            );

            if ($amount < $minPayout) {
                throw new InvalidArgumentException(sprintf(
                    'Batas minimum pencairan komisi adalah Rp %s.',
                    number_format($minPayout, 0, ',', '.')
                ));
            }

            // Validasi kecukupan saldo siap cair (dengan toleransi presisi desimal floating point)
            $balance = $this->getAgentBalance($agentId);
            if (($amount - $balance['payable_balance']) > 0.005) {
                throw new InvalidArgumentException(sprintf(
                    'Nominal pengajuan pencairan (Rp %s) tidak boleh melebihi saldo yang tersedia (Rp %s).',
                    number_format($amount, 0, ',', '.'),
                    number_format($balance['payable_balance'], 0, ',', '.')
                ));
            }

            // Evaluasi batas approval Direktur untuk komisi besar (>= Rp 25 Juta)
            $directorLimit = SystemSettingService::getFloat(
                'commission.director_payout_approval_limit',
                self::DEFAULT_DIRECTOR_PAYOUT_LIMIT
            );
            $requiresDirector = ($amount >= $directorLimit);

            // Generate nomor tiket pencairan resmi korporat (PBB/COM/YYYY/MM/XXXX)
            $payoutNumber = $this->documentNumberService->generateCommissionNumber();

            // Masking nomor rekening perbankan untuk kepatuhan UU PDP No. 27/2022 pada catatan publik
            $rawAccount = (string) ($agent->bank_account_number ?? '');
            $maskedAccount = (strlen($rawAccount) > 4)
                ? str_repeat('*', max(0, strlen($rawAccount) - 4)).substr($rawAccount, -4)
                : '****';

            $payout = CommissionPayout::create([
                'payout_number' => $payoutNumber,
                'freelance_sales_id' => $agentId,
                'sales_order_id' => null,
                'amount' => $amount,
                'type' => 'requested',
                'status' => 'pending_payment',
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes ?? sprintf(
                    'Pengajuan pencairan komisi mandiri agen: %s (%s - %s)%s',
                    $agent->name,
                    $agent->bank_name,
                    $maskedAccount,
                    $requiresDirector ? ' [Menunggu Approval Direktur]' : ''
                ),
            ]);

            // Emisi domain event pengajuan pencairan komisi
            CommissionPayoutRequested::dispatch(
                $payout->id,
                $agentId,
                $amount,
                $agent->bank_name ?? 'BANK'
            );

            return $payout;
        } finally {
            $lock->release();
        }
    }

    /**
     * Persetujuan pengajuan pencairan komisi oleh Finance atau Direktur.
     */
    public function approvePayout(CommissionPayout $payout, int $approverUserId, bool $isDirector = false): CommissionPayout
    {
        if ($payout->status !== 'pending_payment' || $payout->type !== 'requested') {
            throw new InvalidArgumentException('Hanya pengajuan komisi berstatus pending_payment yang dapat disetujui.');
        }

        $directorLimit = SystemSettingService::getFloat(
            'commission.director_payout_approval_limit',
            self::DEFAULT_DIRECTOR_PAYOUT_LIMIT
        );

        // Jika nominal komisi >= batas eskalasi Direktur, verifikasi kewenangan approval (TC-AGN-COM-016)
        if ((float) $payout->amount >= $directorLimit && ! $isDirector) {
            $payout->update([
                'notes' => ($payout->notes ? $payout->notes.' | ' : '').sprintf('Diverifikasi oleh Finance User ID [%d] [Menunggu Approval Direktur]', $approverUserId),
            ]);

            return $payout;
        }

        $payout->update([
            'status' => 'payable',
            'notes' => ($payout->notes ? $payout->notes.' | ' : '').sprintf('Disetujui oleh User ID [%d]%s', $approverUserId, $isDirector ? ' (Direktur)' : ''),
        ]);

        return $payout;
    }

    /**
     * Penolakan pengajuan pencairan komisi oleh Finance atau Direktur.
     * Mengembalikan alokasi saldo yang tertahan (total_requested) kembali menjadi saldo siap cair (payable_balance).
     */
    public function rejectPayout(CommissionPayout $payout, string $reason, ?int $rejectorUserId = null): CommissionPayout
    {
        if ($payout->type !== 'requested') {
            throw new InvalidArgumentException('Hanya pengajuan pencairan komisi (type = requested) yang dapat ditolak.');
        }

        if ($payout->status === 'paid') {
            throw new InvalidArgumentException('Pengajuan pencairan komisi yang telah dibayarkan tidak dapat ditolak.');
        }

        if ($payout->status === 'rejected') {
            throw new InvalidArgumentException('Pengajuan pencairan komisi ini telah ditolak sebelumnya.');
        }

        $notes = $payout->notes ? $payout->notes.' | ' : '';
        $notes .= sprintf(
            'Ditolak%s: %s',
            $rejectorUserId !== null ? sprintf(' oleh User ID [%d]', $rejectorUserId) : '',
            $reason
        );

        $payout->update([
            'status' => 'rejected',
            'notes' => $notes,
        ]);

        return $payout;
    }

    /**
     * Eksekusi pencairan komisi dan pencatatan bukti transfer bank oleh tim Finance.
     */
    public function markAsPaid(
        CommissionPayout $payout,
        string $transferProofPath,
        ?string $notes = null
    ): CommissionPayout {
        if ($payout->type !== 'requested') {
            throw new InvalidArgumentException('Hanya pengajuan pencairan komisi (type = requested) yang dapat diproses pembayarannya.');
        }

        if ($payout->status === 'paid') {
            throw new InvalidArgumentException('Tiket pencairan komisi ini telah diselesaikan sebelumnya.');
        }

        if ($payout->status === 'rejected') {
            throw new InvalidArgumentException('Pengajuan pencairan komisi yang telah ditolak tidak dapat dibayarkan.');
        }

        $payout->update([
            'status' => 'paid',
            'transfer_proof_path' => $transferProofPath,
            'paid_at' => now(),
            'notes' => $notes ?? $payout->notes,
        ]);

        return $payout;
    }

    /**
     * Pembatalan komisi (reversal) saat Sales Order dibatalkan oleh klien (TC-AGN-COM-015).
     * Membatalkan komisi pending maupun payable, serta mencatat pemotongan saldo (clawback) jika komisi sudah terlanjur cair.
     */
    public function reverseCommissionForCancelledSalesOrder(SalesOrder $salesOrder, string $reason = ''): void
    {
        DB::transaction(function () use ($salesOrder, $reason) {
            $earnedPayouts = CommissionPayout::where('sales_order_id', $salesOrder->id)
                ->where('type', 'earned')
                ->get();

            foreach ($earnedPayouts as $payout) {
                if (in_array($payout->status, ['pending_payment', 'payable'], true)) {
                    $payout->update([
                        'status' => 'rejected',
                        'notes' => sprintf('Reversal komisi akibat pembatalan Sales Order: %s', $reason),
                    ]);
                } elseif ($payout->status === 'paid') {
                    // Jika komisi sudah terlanjur ditransfer ke agen, buat baris pemotongan saldo (deducted / clawback)
                    CommissionPayout::create([
                        'freelance_sales_id' => $payout->freelance_sales_id,
                        'sales_order_id' => $salesOrder->id,
                        'amount' => $payout->amount,
                        'type' => 'deducted',
                        'status' => 'paid',
                        'notes' => sprintf('Reversal komisi (Clawback) akibat pembatalan Sales Order yang telah dibayarkan: %s', $reason),
                    ]);
                }
            }
        }, 3);
    }

    // Alias methods untuk integrasi modul lain
    public function calculateAndCreateEarnedCommission(SalesOrder $salesOrder): ?CommissionCalculationDTO
    {
        return $this->calculateCommission($salesOrder);
    }

    public function activateCommissionOnInvoiceSettlement(SalesOrder|int $salesOrder): void
    {
        $this->markCommissionAsPayable($salesOrder);
    }

    public function getAgentBalanceSummary(int $agentId): array
    {
        return $this->getAgentBalance($agentId);
    }
}
