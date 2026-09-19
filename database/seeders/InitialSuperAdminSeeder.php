<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin Bootstrap User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@bimabergawa.com'],
            [
                'name' => 'Direksi PT. Panca Bima Bergawa',
                'phone' => '081234567890',
                'password' => Hash::make(config('app.initial_admin_password', 'PBB-RasunaSaid-2026!')),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // 2. Test Accounts for Internal Roles
        $salesUser = User::firstOrCreate(
            ['email' => 'sales@bimabergawa.com'],
            [
                'name' => 'Ahmad Fauzi (Sales PIC)',
                'phone' => '081299887766',
                'password' => Hash::make('PBB-Sales-2026!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $salesUser->syncRoles(['sales_pic']);

        $financeUser = User::firstOrCreate(
            ['email' => 'finance@bimabergawa.com'],
            [
                'name' => 'Budi Santoso (Finance)',
                'phone' => '081311223344',
                'password' => Hash::make('PBB-Finance-2026!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $financeUser->syncRoles(['finance']);

        $logisticsUser = User::firstOrCreate(
            ['email' => 'logistics@bimabergawa.com'],
            [
                'name' => 'Bayu Saputra (Logistik)',
                'phone' => '081355667788',
                'password' => Hash::make('PBB-Logistik-2026!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $logisticsUser->syncRoles(['logistics']);

        // 3. Test Freelance Agent with Profile
        $agentUser = User::firstOrCreate(
            ['email' => 'agen@bimabergawa.com'],
            [
                'name' => 'Hendra Nugraha (Mitra Agen)',
                'phone' => '081377889900',
                'password' => Hash::make('PBB-Agen-2026!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $agentUser->syncRoles(['freelance_sales']);

        $defaultTierId = CommissionTier::where('name', 'Silver Agent')->value('id') ?? CommissionTier::first()?->id;

        $agentProfile = FreelanceSales::firstOrCreate(
            ['user_id' => $agentUser->id],
            [
                'commission_tier_id' => $defaultTierId,
                'name' => $agentUser->name,
                'referral_code' => 'REF-PBB-001',
                'nik' => '3171012345670001',
                'npwp_number' => '09.123.456.7-012.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Hendra Nugraha',
                'industry_focus' => 'Kontraktor Listrik & Otomasi Pabrik',
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        if ($agentUser->freelance_sales_id === null) {
            $agentUser->update(['freelance_sales_id' => $agentProfile->id]);
        }
    }
}
