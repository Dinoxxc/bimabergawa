<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommissionTier;
use App\Models\FreelanceSales;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelanceSales>
 */
class FreelanceSalesFactory extends Factory
{
    protected $model = FreelanceSales::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'commission_tier_id' => CommissionTier::factory(),
            'name' => $name,
            'referral_code' => 'REF-'.strtoupper(fake()->unique()->bothify('###-???')),
            'nik' => fake()->numerify('3171############'),
            'npwp_number' => fake()->numerify('##.###.###.#-###.###'),
            'ktp_file_path' => null,
            'bank_name' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']),
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_name' => $name,
            'industry_focus' => fake()->randomElement(['Kontraktor MEP', 'Panel Builder', 'Pabrik Manufaktur', 'Gedung Komersial']),
            'status' => 'approved',
            'approved_at' => now(),
        ];
    }
}
