<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrderItem>
 */
class DeliveryOrderItemFactory extends Factory
{
    protected $model = DeliveryOrderItem::class;

    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'product_id' => Product::factory(),
            'item_name' => fake()->words(3, true),
            'model_number' => strtoupper(fake()->bothify('MOD-###-???')),
            'quantity_shipped' => fake()->numberBetween(1, 20),
        ];
    }
}
