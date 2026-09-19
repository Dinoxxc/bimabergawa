<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpecification>
 */
class ProductSpecificationFactory extends Factory
{
    protected $model = ProductSpecification::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'spec_key' => fake()->randomElement(['Rated Current', 'Voltage', 'IP Rating', 'Material']),
            'spec_value' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
