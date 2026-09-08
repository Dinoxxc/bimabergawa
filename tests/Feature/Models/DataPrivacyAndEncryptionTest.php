<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataPrivacyAndEncryptionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * TC-SEC-PDP-001: Enkripsi Data Pribadi Sensitif Agen (At-Rest Encryption)
     */
    public function test_sensitive_agent_profile_data_is_encrypted_at_rest(): void
    {
        $tier = CommissionTier::create([
            'name' => 'Gold Partner',
            'min_turnover_amount' => 100000000.00,
            'commission_rate' => 3.50,
            'is_active' => true,
        ]);

        $plainNik = '3171012345670001';
        $plainNpwp = '01.234.567.8-012.000';
        $plainAccount = '1234567890';

        $agent = FreelanceSales::create([
            'commission_tier_id' => $tier->id,
            'name' => 'Budi Santoso',
            'referral_code' => 'AGT-BUDI-001',
            'nik' => $plainNik,
            'npwp_number' => $plainNpwp,
            'ktp_file_path' => 'private/ktp/budi.jpg',
            'bank_name' => 'BCA',
            'bank_account_number' => $plainAccount,
            'bank_account_name' => 'Budi Santoso',
            'industry_focus' => 'Electrical Panel',
            'status' => 'approved',
        ]);

        // 1. Direct database raw check: verify ciphertext is NOT plaintext
        $rawRecord = DB::table('freelance_sales')->where('id', $agent->id)->first();
        $this->assertNotNull($rawRecord);
        $this->assertNotEquals($plainNik, $rawRecord->nik, 'NIK must not be stored in plaintext');
        $this->assertNotEquals($plainNpwp, $rawRecord->npwp_number, 'NPWP must not be stored in plaintext');
        $this->assertNotEquals($plainAccount, $rawRecord->bank_account_number, 'Bank account must not be stored in plaintext');

        // 2. Eloquent model accessor: verify transparent decryption
        $loadedAgent = FreelanceSales::find($agent->id);
        $this->assertEquals($plainNik, $loadedAgent->nik);
        $this->assertEquals($plainNpwp, $loadedAgent->npwp_number);
        $this->assertEquals($plainAccount, $loadedAgent->bank_account_number);
    }
}
