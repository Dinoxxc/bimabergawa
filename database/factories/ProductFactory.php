<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $modelNumber = strtoupper(fake()->unique()->bothify('MOD-###-???'));

        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'successor_product_id' => null,
            'name' => ucwords($name),
            'slug' => Str::slug($name.'-'.$modelNumber),
            'model_number' => $modelNumber,
            'cost_price' => fake()->randomFloat(2, 50000, 5000000),
            'currency' => 'IDR',
            'description' => fake()->paragraph(),
            'datasheet_path' => null,
            'availability_status' => fake()->randomElement(['ready_stock', 'indent_principal', 'discontinued']),
            'is_featured' => fake()->boolean(20),
            'is_published' => true,
            'meta_description' => fake()->sentence(),
        ];
    }
}
