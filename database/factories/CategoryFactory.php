<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => ucwords($name),
            'code' => strtoupper(fake()->lexify('???')),
            'slug' => Str::slug($name),
            'icon_name' => 'heroicon-o-folder',
            'description' => fake()->sentence(),
            'spec_template' => [['name' => 'Standard', 'unit' => '', 'type' => 'string']],
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
