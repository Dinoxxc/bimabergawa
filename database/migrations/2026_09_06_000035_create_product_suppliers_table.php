<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel pivot Multi-Sourcing Vendor Matrix (Many-to-Many antara products dan suppliers).
     */
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('supplier_sku', 100)->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('IDR');
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->boolean('is_primary')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Cegah duplikasi rekanan vendor yang sama pada 1 SKU produk
            $table->unique(['product_id', 'supplier_id'], 'uk_prod_sup');

            // Indeks komposit performa pencarian HPP & supplier utama
            $table->index(['product_id', 'is_primary', 'purchase_price'], 'idx_prod_sup_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
