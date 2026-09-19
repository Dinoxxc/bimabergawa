<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'delivery_number' => 'DO-'.date('Ym').'-'.strtoupper(fake()->unique()->bothify('####')).'/01',
            'sales_order_id' => SalesOrder::factory(),
            'courier_name' => fake()->randomElement(['Baraka Sarana Tama', 'Dakota Cargo', 'JNE Trucking', 'Kurir Internal PBB']),
            'tracking_number' => strtoupper(fake()->bothify('TRK-########')),
            'tracking_url' => null,
            'shipping_address' => fake()->address(),
            'recipient_pic_name' => fake()->name(),
            'recipient_phone' => fake()->phoneNumber(),
            'total_packages' => fake()->numberBetween(1, 10),
            'total_weight_kg' => fake()->randomFloat(2, 5, 250),
            'status' => 'prepared',
            'proof_of_delivery_path' => null,
            'shipped_at' => null,
        ];
    }
}
