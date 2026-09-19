<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryItem>
 */
class InquiryItemFactory extends Factory
{
    protected $model = InquiryItem::class;

    public function definition(): array
    {
        return [
            'inquiry_id' => Inquiry::factory(),
            'product_id' => Product::factory(),
            'item_name' => fake()->words(3, true),
            'model_number' => strtoupper(fake()->bothify('MOD-###-???')),
            'quantity' => fake()->numberBetween(1, 50),
            'custom_notes' => fake()->sentence(),
        ];
    }
}
