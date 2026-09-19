<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryTelemetry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryTelemetry>
 */
class InquiryTelemetryFactory extends Factory
{
    protected $model = InquiryTelemetry::class;

    public function definition(): array
    {
        return [
            'inquiry_id' => Inquiry::factory(),
            'utm_source' => fake()->randomElement(['google', 'linkedin', 'direct', 'newsletter']),
            'utm_medium' => fake()->randomElement(['cpc', 'organic', 'social', 'email']),
            'utm_campaign' => fake()->slug(2),
            'utm_term' => fake()->word(),
            'utm_content' => fake()->word(),
            'referrer_url' => fake()->url(),
            'landing_page' => 'https://bimabergawa.com/products',
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
