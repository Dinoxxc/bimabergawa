<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CommissionTier;
use Illuminate\Database\Seeder;

class CommissionTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Silver Agent',
                'min_turnover_amount' => 0.00,
                'commission_rate' => 2.50,
                'is_active' => true,
            ],
            [
                'name' => 'Gold Agent',
                'min_turnover_amount' => 100000000.00,
                'commission_rate' => 3.50,
                'is_active' => true,
            ],
            [
                'name' => 'Platinum Agent',
                'min_turnover_amount' => 500000000.00,
                'commission_rate' => 5.00,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            CommissionTier::firstOrCreate(
                ['name' => $tier['name']],
                $tier
            );
        }
    }
}
