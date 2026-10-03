<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Industrial Supply',
            'code' => 'SUP-'.strtoupper(fake()->unique()->bothify('???-###')),
            'brand_id' => Brand::factory(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'payment_terms' => fake()->randomElement(['CBD', 'COD', 'Net 14', 'Net 30', 'Net 60']),
            'discount_structure' => 'Pricelist - '.fake()->randomElement(['20%', '25%', '30%', '35%']),
            'notes' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
