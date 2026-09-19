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
            'spec_group' => fake()->randomElement(['Kelistrikan', 'Dimensi & Fisik', 'Standar & Sertifikasi']),
            'spec_key' => fake()->randomElement(['Rated Current', 'Voltage', 'IP Rating', 'Material']),
            'spec_value' => fake()->word(),
            'spec_unit' => fake()->randomElement(['Ampere', 'Volt', 'kW', 'bar', 'inch', 'mm', null]),
            'is_highlight' => fake()->boolean(25),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
