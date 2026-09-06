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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('item_name', 255);
            $table->string('model_number', 150)->nullable();
            $table->string('lead_time', 100)->nullable(); // Ready Stock, Indent 4-6 Minggu
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('cost_price', 15, 2)->default(0.00); // HPP Satuan Snapshot
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('total_price', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['quotation_id', 'product_id'], 'idx_quo_items_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
