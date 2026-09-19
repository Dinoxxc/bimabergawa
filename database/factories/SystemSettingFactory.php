<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $key = 'setting_'.fake()->unique()->word().'.'.fake()->unique()->word();

        return [
            'key' => $key,
            'value' => fake()->word(),
            'group' => 'general',
            'type' => 'string',
            'description' => fake()->sentence(),
            'is_public' => false,
        ];
    }
}
