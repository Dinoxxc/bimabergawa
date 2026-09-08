<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Invoice remaining balance accessor and child items relation.
     */
    public function test_invoice_calculates_remaining_balance_accurately(): void
    {
        $salesPic = User::create([
            'name' => 'Sales PIC Test',
            'email' => 'sales_pic_'.time().'@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-TEST-'.time(),
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Energi Cemerlang',
            'company_name' => 'PT Energi Cemerlang',
            'subtotal' => 10000000.00,
            'dpp' => 10000000.00,
            'vat_rate_applied' => 12.00,
            'tax_amount' => 1200000.00,
            'grand_total' => 11200000.00,
            'status' => 'approved',
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-TEST-'.time(),
            'quotation_id' => $quotation->id,
            'customer_po_number' => 'PO-CUST-999',
            'total_amount' => 11200000.00,
            'vat_rate_applied' => 12.00,
            'status' => 'processing',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.date('Ym').'-'.substr((string) time(), -4),
            'sales_order_id' => $salesOrder->id,
            'quotation_id' => $quotation->id,
            'customer_name' => 'PT Energi Cemerlang',
            'company_name' => 'PT Energi Cemerlang',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 10000000.00,
            'vat_rate_applied' => 12.00,
            'tax_amount' => 1200000.00,
            'grand_total' => 11200000.00,
            'paid_amount' => 5000000.00,
            'currency' => 'IDR',
            'status' => 'partially_paid',
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_name' => 'MCB 16A Schneider',
            'model_number' => 'A9F74116',
            'quantity' => 100,
            'unit_price' => 100000.00,
            'total_price' => 10000000.00,
        ]);

        $this->assertEquals(6200000.00, $invoice->remaining_balance);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals('MCB 16A Schneider', $invoice->items->first()->item_name);

        // Pay remaining balance in full
        $invoice->update(['paid_amount' => 11200000.00, 'status' => 'paid']);
        $this->assertEquals(0.0, $invoice->remaining_balance);
    }
}
