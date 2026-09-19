<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'log_name' => 'inquiry_activity',
            'description' => fake()->sentence(),
            'subject_type' => 'inquiry',
            'subject_id' => Inquiry::factory(),
            'causer_type' => 'user',
            'causer_id' => User::factory(),
            'properties' => ['status' => 'contacted'],
            'ip_address' => '127.0.0.1',
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
