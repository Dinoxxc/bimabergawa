<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionTier;
use App\Models\DeliveryOrder;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\InternalComment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
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

    /**
     * TC-ERP-INV-017: Perlindungan Catatan Internal Staf & Modal HPP dari Akses Mitra Agen
     */
    public function test_internal_comments_are_hidden_from_freelance_sales_agents(): void
    {
        $roleAgent = Role::firstOrCreate(['name' => 'freelance_sales']);

        $tier = CommissionTier::create([
            'name' => 'Comment Shield Tier',
            'min_monthly_margin' => 10000000.00,
            'commission_rate' => 7.50,
        ]);

        $agent = FreelanceSales::create([
            'commission_tier_id' => $tier->id,
            'name' => 'Agent Chat Shield',
            'referral_code' => 'REF-CHAT-'.time(),

            'nik' => '3171010101010004',
            'npwp_number' => '01.234.567.8-014.000',
            'bank_name' => 'BCA',
            'bank_account_number' => '9988776655',
            'bank_account_name' => 'Agent Chat',
            'status' => 'approved',
        ]);

        $agentUser = User::create([
            'name' => 'Agent User Chat',
            'email' => 'agent_chat_'.time().'@example.com',
            'freelance_sales_id' => $agent->id,
        ]);
        $agentUser->assignRole($roleAgent);

        $roleSalesPic = Role::firstOrCreate(['name' => 'sales_pic']);

        $staffUser = User::create([
            'name' => 'Staff Sales PIC',
            'email' => 'staff_pic_'.time().'@example.com',
        ]);
        $staffUser->assignRole($roleSalesPic);

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-CHAT-'.time(),
            'freelance_sales_id' => $agent->id,
            'customer_name' => 'PT Proyek Rahasia',
            'company_name' => 'PT Proyek Rahasia',
            'phone_wa' => '081122334455',
            'status' => 'new',
        ]);

        // 1. Internal comment (contains sensitive HPP modal)
        InternalComment::create([
            'user_id' => $staffUser->id,
            'commentable_type' => 'inquiry',
            'commentable_id' => $inquiry->id,
            'comment' => 'HPP Schneider ini Rp 50.000, jangan beri diskon lebih dari 10%',
            'type' => 'internal',
        ]);

        // 2. Agent visible comment
        InternalComment::create([
            'user_id' => $staffUser->id,
            'commentable_type' => 'inquiry',
            'commentable_id' => $inquiry->id,
            'comment' => 'Mohon lampirkan spesifikasi panel yang dibutuhkan',
            'type' => 'agent_visible',
        ]);

        // Verification 1: Staff user sees BOTH comments (2 comments)
        $this->actingAs($staffUser);
        $staffComments = InternalComment::where('commentable_id', $inquiry->id)->get();
        $this->assertCount(2, $staffComments);

        // Verification 2: Agent user ONLY sees the agent_visible comment (1 comment)
        $this->actingAs($agentUser);
        $agentComments = InternalComment::where('commentable_id', $inquiry->id)->get();
        $this->assertCount(1, $agentComments);
        $this->assertEquals('agent_visible', $agentComments->first()->type);
        $this->assertStringContainsString('Mohon lampirkan', $agentComments->first()->comment);
    }

    /**
     * TC-ERP-INV-019: Akses Rantai Induk Transaksi Historis yang Di-Soft-Delete (Full-Chain withTrashed)
     */
    public function test_full_chain_historical_relations_retain_parent_after_soft_delete(): void
    {
        $salesPic = User::create([
            'name' => 'Sales Chain Test',
            'email' => 'sales_chain_'.time().'@example.com',
        ]);

        $inquiry = Inquiry::create([
            'inquiry_number' => 'INQ-CHAIN-'.time(),
            'customer_name' => 'PT Audit Historis',
            'company_name' => 'PT Audit Historis',
            'phone_wa' => '081987654321',
            'status' => 'deal',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-CHAIN-'.time(),
            'inquiry_id' => $inquiry->id,
            'user_id' => $salesPic->id,
            'customer_name' => 'PT Audit Historis',
            'company_name' => 'PT Audit Historis',
            'subtotal' => 50000000.00,
            'status' => 'approved',
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'SO-CHAIN-'.time(),
            'quotation_id' => $quotation->id,
            'total_amount' => 50000000.00,
            'status' => 'completed',
        ]);

        $deliveryOrder = DeliveryOrder::create([
            'delivery_number' => 'DO-CHAIN-'.time(),
            'sales_order_id' => $salesOrder->id,
            'courier_name' => 'Internal Fleet',
            'shipping_address' => 'Jl. Industri No. 10, Cikarang',
            'recipient_pic_name' => 'Bpk Budi',
            'recipient_phone' => '08123456789',
            'status' => 'delivered',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-CHAIN-'.time(),
            'sales_order_id' => $salesOrder->id,
            'quotation_id' => $quotation->id,
            'customer_name' => 'PT Audit Historis',
            'company_name' => 'PT Audit Historis',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'grand_total' => 50000000.00,
            'status' => 'paid',
        ]);

        // Soft delete the parents: Inquiry, Quotation, and SalesOrder
        $inquiry->delete();
        $quotation->delete();
        $salesOrder->delete();

        // Verification: Full-chain resolution works flawlessly without null errors
        $freshInvoice = Invoice::find($invoice->id);
        $this->assertNotNull($freshInvoice->salesOrder);
        $this->assertEquals('completed', $freshInvoice->salesOrder->status);
        $this->assertNotNull($freshInvoice->quotation);
        $this->assertEquals('approved', $freshInvoice->quotation->status);

        $freshDO = DeliveryOrder::find($deliveryOrder->id);
        $this->assertNotNull($freshDO->salesOrder);
        $this->assertEquals($salesOrder->id, $freshDO->salesOrder->id);

        $freshSO = SalesOrder::withTrashed()->find($salesOrder->id);
        $this->assertNotNull($freshSO->quotation);
        $this->assertEquals($quotation->id, $freshSO->quotation->id);

        $freshQuo = Quotation::withTrashed()->find($quotation->id);
        $this->assertNotNull($freshQuo->inquiry);
        $this->assertEquals($inquiry->id, $freshQuo->inquiry->id);
    }
}
