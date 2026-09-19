<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    protected $model = QuotationItem::class;

    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 50000, 5000000);
        $unitPrice = round($costPrice * 1.25, 2);
        $qty = fake()->numberBetween(1, 20);

        return [
            'quotation_id' => Quotation::factory(),
            'product_id' => Product::factory(),
            'item_name' => fake()->words(3, true),
            'model_number' => strtoupper(fake()->bothify('MOD-###-???')),
            'lead_time' => 'Ready Stock',
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
            'discount_percent' => 0.00,
            'total_price' => round($unitPrice * $qty, 2),
        ];
    }
}
