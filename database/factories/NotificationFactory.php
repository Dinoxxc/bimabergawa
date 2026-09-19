<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'type' => 'App\\Notifications\\NewInquiryReceived',
            'notifiable_type' => 'user',
            'notifiable_id' => User::factory(),
            'data' => [
                'title' => 'Inquiry Baru Masuk',
                'message' => 'Terdapat permintaan penawaran baru yang membutuhkan tanggapan.',
                'action_url' => '/erp/inquiries',
            ],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
