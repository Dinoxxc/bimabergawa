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
        Schema::create('commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number', 30)->unique()->nullable(); // COM-YYYYMM-XXXX
            $table->foreignId('freelance_sales_id')->constrained('freelance_sales')->onDelete('restrict');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('restrict');
            $table->decimal('amount', 15, 2)->default(0.00); // Nominal Komisi (Rp)
            $table->enum('type', ['earned', 'requested', 'paid', 'deducted'])->default('earned');
            $table->enum('status', ['pending_payment', 'payable', 'paid', 'rejected'])->default('pending_payment')->index();
            $table->string('transfer_proof_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['freelance_sales_id', 'status'], 'idx_comm_sales_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_payouts');
    }
};
