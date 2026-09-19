<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommissionPayout;
use App\Models\FreelanceSales;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionPayout>
 */
class CommissionPayoutFactory extends Factory
{
    protected $model = CommissionPayout::class;

    public function definition(): array
    {
        return [
            'payout_number' => 'COM-'.date('Ym').'-'.strtoupper(fake()->unique()->bothify('####')),
            'freelance_sales_id' => FreelanceSales::factory(),
            'sales_order_id' => SalesOrder::factory(),
            'amount' => fake()->randomFloat(2, 500000, 10000000),
            'type' => 'earned',
            'status' => 'pending_payment',
            'transfer_proof_path' => null,
            'notes' => fake()->sentence(),
            'paid_at' => null,
        ];
    }
}
