<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000000, 100000000);
        $tax = round($subtotal * 0.11, 2);
        $grandTotal = $subtotal + $tax;
        $totalCost = round($subtotal * 0.82, 2);
        $margin = round((($subtotal - $totalCost) / $subtotal) * 100, 2);

        return [
            'quotation_number' => 'PBB/QUO/'.date('Y').'/'.date('m').'/'.strtoupper(fake()->unique()->bothify('####')),
            'revision_number' => 0,
            'inquiry_id' => Inquiry::factory(),
            'user_id' => User::factory(),
            'freelance_sales_id' => null,
            'customer_name' => fake()->name(),
            'company_name' => fake()->company(),
            'project_name' => 'Proyek '.fake()->company().' '.fake()->city(),
            'delivery_terms' => 'Franco Jakarta',
            'validity_days' => 14,
            'expires_at' => now()->addDays(14),
            'subtotal' => $subtotal,
            'discount_amount' => 0.00,
            'dpp' => $subtotal,
            'vat_rate_applied' => 11.00,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'currency' => 'IDR',
            'exchange_rate_applied' => 1.0000,
            'base_currency' => 'IDR',
            'idempotency_key' => fake()->optional()->uuid(),
            'total_cost' => $totalCost,
            'gross_profit_margin' => $margin,
            'is_approved_by_director' => true,
            'status' => 'draft',
        ];
    }
}
