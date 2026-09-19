<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationPaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationPaymentTerm>
 */
class QuotationPaymentTermFactory extends Factory
{
    protected $model = QuotationPaymentTerm::class;

    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'phase_name' => 'DP 30% Setelah Penerbitan PO',
            'percentage' => 30.00,
            'amount' => fake()->randomFloat(2, 5000000, 30000000),
            'sort_order' => 1,
        ];
    }
}
