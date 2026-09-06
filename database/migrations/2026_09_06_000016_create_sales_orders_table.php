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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique(); // SO-YYYYMM-XXXX
            $table->foreignId('quotation_id')->unique()->constrained('quotations')->onDelete('restrict');
            $table->string('customer_po_number', 100)->nullable();
            $table->date('customer_po_date')->nullable();
            $table->enum('status', [
                'processing',
                'indent_principal',
                'ready_to_ship',
                'shipped',
                'partially_fulfilled',
                'completed',
                'cancelled'
            ])->default('processing')->index();
            $table->decimal('vat_rate_applied', 5, 2)->default(11.00); // Snapshot PPN transaksi
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->boolean('is_approved_by_director')->default(false); // Wajib Direktur jika >= 100 Juta
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_so_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
