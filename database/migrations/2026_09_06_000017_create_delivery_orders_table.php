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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 35)->unique(); // DO-YYYYMM-XXXX/01
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->string('courier_name', 150); // Baraka, Indah Cargo, Dakota
            $table->string('tracking_number', 100)->nullable();
            $table->string('tracking_url', 255)->nullable();
            $table->text('shipping_address');
            $table->string('recipient_pic_name', 150);
            $table->string('recipient_phone', 50);
            $table->integer('total_packages')->default(1);
            $table->decimal('total_weight_kg', 8, 2)->nullable();
            $table->enum('status', ['prepared', 'shipped', 'delivered'])->default('prepared')->index();
            $table->string('proof_of_delivery_path', 255)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->index(['sales_order_id', 'status'], 'idx_do_order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
