<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
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

    /**
     * TC-ERP-INV-013: Relasi Item Transaksi Historis terhadap Produk Discontinue (withTrashed)
     */
    public function test_transactional_item_retains_product_relation_even_after_product_soft_deleted(): void
    {
        $brand = Brand::create([
            'name' => 'Schneider Historical',
            'slug' => 'schneider-hist-'.time(),
        ]);

        $category = Category::create([
            'name' => 'Historical Category',
            'slug' => 'hist-cat-'.time(),
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Legacy Discontinued Contactor',
            'slug' => 'legacy-contactor-'.time(),
            'model_number' => 'LC1D09M7',
            'cost_price' => 150000.00,
            'availability_status' => 'discontinued',
            'is_published' => false,
        ]);

        $salesPic = User::create([
            'name' => 'Sales PIC Hist',
            'email' => 'sales_hist_'.time().'@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-HIST-'.time(),
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Pelanggan Lama',
            'company_name' => 'PT Pelanggan Lama',
            'subtotal' => 150000.00,
            'status' => 'approved',
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-HIST-'.time(),
            'quotation_id' => $quotation->id,
            'total_amount' => 150000.00,
            'status' => 'completed',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-HIST-'.time(),
            'sales_order_id' => $salesOrder->id,
            'customer_name' => 'PT Pelanggan Lama',
            'company_name' => 'PT Pelanggan Lama',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'grand_total' => 150000.00,
            'status' => 'paid',
        ]);

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'item_name' => 'Legacy Discontinued Contactor',
            'model_number' => 'LC1D09M7',
            'quantity' => 1,
            'unit_price' => 200000.00,
            'total_price' => 200000.00,
        ]);

        // Soft delete the product (e.g. principal discontinued the SKU)
        $product->delete();
        $this->assertNotNull($product->fresh()->deleted_at);

        // Verification: invoiceItem still successfully resolves the product relation
        $loadedItem = InvoiceItem::find($invoiceItem->id);
        $this->assertNotNull($loadedItem->product);
        $this->assertEquals('Legacy Discontinued Contactor', $loadedItem->product->name);
        $this->assertEquals($product->id, $loadedItem->product->id);
    }
}
