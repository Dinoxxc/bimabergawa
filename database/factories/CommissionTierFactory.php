<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommissionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionTier>
 */
class CommissionTierFactory extends Factory
{
    protected $model = CommissionTier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Silver', 'Gold', 'Platinum', 'Diamond']).' Tier '.fake()->unique()->numberBetween(10, 99),
            'min_turnover_amount' => fake()->randomFloat(2, 0, 100000000),
            'commission_rate' => fake()->randomFloat(2, 1.00, 10.00),
            'is_active' => true,
        ];
    }
}
