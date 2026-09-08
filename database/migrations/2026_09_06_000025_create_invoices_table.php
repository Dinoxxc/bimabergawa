<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 35)->unique(); // INV-YYYYMM-XXXX
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('restrict');
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->onDelete('restrict');
            $table->foreignId('payment_term_id')->nullable()->constrained('quotation_payment_terms')->onDelete('set null');
            $table->string('customer_name', 150);
            $table->string('company_name', 200);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('vat_rate_applied', 5, 2)->default(11.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00); // Akumulasi pembayaran yang telah diterima
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('unpaid')->index();
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_proof_path', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date'], 'idx_invoices_status_due');
            $table->index(['sales_order_id'], 'idx_invoices_sales_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
