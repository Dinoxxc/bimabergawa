<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'code' => strtoupper(fake()->lexify('???')),
            'slug' => Str::slug($name),
            'logo_path' => 'brands/'.Str::slug($name).'.webp',
            'website_url' => fake()->url(),
            'description' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
