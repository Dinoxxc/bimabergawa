<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
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

        // 3. Serialization privacy check (UU PDP No. 27/2022): sensitive PII must not appear in toArray or toJson
        $agentArray = $loadedAgent->toArray();
        $this->assertArrayNotHasKey('nik', $agentArray, 'NIK must be hidden in toArray()');
        $this->assertArrayNotHasKey('npwp_number', $agentArray, 'NPWP must be hidden in toArray()');
        $this->assertArrayNotHasKey('bank_account_number', $agentArray, 'Bank account must be hidden in toArray()');
        $this->assertArrayNotHasKey('ktp_file_path', $agentArray, 'KTP file path must be hidden in toArray()');

        $agentJson = $loadedAgent->toJson();
        $this->assertStringNotContainsString($plainNik, $agentJson);
        $this->assertStringNotContainsString($plainAccount, $agentJson);
    }

    /**
     * TC-SEC-PDP-002: Proteksi Kerahasiaan HPP Produk & Item Penawaran (Zero-Price Policy & Anti-HPP Leakage).
     */
    public function test_product_and_quotation_cost_price_is_hidden_from_serialization(): void
    {
        $product = Product::make([
            'name' => 'Sensor Industri Schneider',
            'cost_price' => 450000.00,
            'model_number' => 'XS618B1PAL2',
        ]);

        $productArray = $product->toArray();
        $this->assertArrayNotHasKey('cost_price', $productArray, 'cost_price must be hidden from Product toArray()');
        $this->assertStringNotContainsString('450000', $product->toJson(), 'cost_price must not appear in Product toJson()');

        // Direct PHP access for service calculation remains intact
        $this->assertEquals(450000.00, (float) $product->cost_price);

        $item = QuotationItem::make([
            'item_name' => 'Inverter Altivar 5.5kW',
            'cost_price' => 7850000.00,
            'unit_price' => 9500000.00,
        ]);

        $itemArray = $item->toArray();
        $this->assertArrayNotHasKey('cost_price', $itemArray, 'cost_price must be hidden from QuotationItem toArray()');
        $this->assertStringNotContainsString('7850000', $item->toJson(), 'cost_price must not appear in QuotationItem toJson()');
    }

    /**
     * TC-SEC-PDP-003: Proteksi Kerahasiaan Total Cost, Margin Laba, & Catatan Direksi pada Penawaran Harga.
     */
    public function test_quotation_margins_and_director_notes_are_hidden_from_serialization(): void
    {
        $quotation = Quotation::make([
            'quotation_number' => 'PBB/QUO/2026/10/0001',
            'subtotal' => 10000000.00,
            'grand_total' => 11100000.00,
            'total_cost' => 8000000.00,
            'gross_profit_margin' => 20.00,
            'director_notes' => 'Disetujui diskon khusus proyek MRT Jakarta.',
        ]);

        $quotationArray = $quotation->toArray();
        $this->assertArrayNotHasKey('total_cost', $quotationArray, 'total_cost must be hidden from Quotation toArray()');
        $this->assertArrayNotHasKey('gross_profit_margin', $quotationArray, 'gross_profit_margin must be hidden from Quotation toArray()');
        $this->assertArrayNotHasKey('director_notes', $quotationArray, 'director_notes must be hidden from Quotation toArray()');

        $quotationJson = $quotation->toJson();
        $this->assertStringNotContainsString('8000000', $quotationJson);
        $this->assertStringNotContainsString('Disetujui diskon khusus', $quotationJson);

        // Direct property access for authorized staff remains accessible
        $this->assertEquals(8000000.00, (float) $quotation->total_cost);
        $this->assertEquals(20.00, (float) $quotation->gross_profit_margin);
    }
}
