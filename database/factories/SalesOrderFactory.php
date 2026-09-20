<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'PBB/SOR/'.date('Y').'/'.date('m').'/'.strtoupper(fake()->unique()->bothify('####')),
            'quotation_id' => Quotation::factory(),
            'customer_po_number' => 'PO-'.strtoupper(fake()->bothify('###-????')),
            'customer_po_date' => now()->subDays(2),
            'customer_po_file_path' => null,
            'project_name' => 'Proyek '.fake()->company().' '.fake()->city(),
            'status' => 'processing',
            'vat_rate_applied' => 11.00,
            'total_amount' => fake()->randomFloat(2, 10000000, 100000000),
            'currency' => 'IDR',
            'is_approved_by_director' => true,
            'idempotency_key' => fake()->optional()->uuid(),
        ];
    }
}
