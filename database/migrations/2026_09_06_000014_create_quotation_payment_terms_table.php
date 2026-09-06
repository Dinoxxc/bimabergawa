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
        Schema::create('quotation_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->string('phase_name', 150); // DP 30% PO, 50% Sebelum Kirim, 20% Retensi
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order'], 'idx_quo_terms_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_payment_terms');
    }
};
