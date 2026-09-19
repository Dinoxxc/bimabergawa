<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 100000, 5000000);
        $qty = fake()->numberBetween(1, 10);

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'item_name' => fake()->words(3, true),
            'model_number' => strtoupper(fake()->bothify('MOD-###-???')),
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $qty, 2),
        ];
    }
}
