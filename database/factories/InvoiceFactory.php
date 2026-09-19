<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000000, 100000000);
        $tax = round($subtotal * 0.11, 2);
        $grandTotal = $subtotal + $tax;

        return [
            'invoice_number' => 'INV-'.date('Ym').'-'.strtoupper(fake()->unique()->bothify('####')),
            'faktur_pajak_number' => '010.032-26.'.fake()->numerify('########'),
            'sales_order_id' => SalesOrder::factory(),
            'quotation_id' => Quotation::factory(),
            'payment_term_id' => null,
            'customer_name' => fake()->name(),
            'company_name' => fake()->company(),
            'company_npwp' => '01.234.567.8-'.fake()->numerify('###.###'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'vat_rate_applied' => 11.00,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'paid_amount' => 0.00,
            'currency' => 'IDR',
            'status' => 'unpaid',
            'payment_method' => 'Bank Transfer BCA',
            'payment_proof_path' => null,
            'paid_at' => null,
            'notes' => fake()->sentence(),
            'pdf_path' => null,
        ];
    }
}
