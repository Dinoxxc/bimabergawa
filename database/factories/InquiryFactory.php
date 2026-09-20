<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'inquiry_number' => 'PBB/INQ/'.date('Y').'/'.date('m').'/'.strtoupper(fake()->unique()->bothify('####')),
            'assigned_user_id' => null,
            'freelance_sales_id' => null,
            'customer_name' => fake()->name(),
            'company_name' => fake()->company(),
            'project_name' => 'Proyek '.fake()->company().' '.fake()->city(),
            'phone_wa' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'project_location' => fake()->city(),
            'type' => 'single_direct',
            'source' => fake()->randomElement(['website_rfq', 'direct_whatsapp', 'walk_in_phone']),
            'status' => 'new',
            'notes' => fake()->sentence(),
        ];
    }
}
