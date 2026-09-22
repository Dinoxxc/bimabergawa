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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->string('status', 35)->default('prepared')->change();
            $table->text('discrepancy_notes')->nullable()->after('proof_of_delivery_path');
            $table->string('return_proof_path', 255)->nullable()->after('discrepancy_notes');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->integer('quantity_received')->default(0)->after('quantity_shipped');
            $table->integer('quantity_defective')->default(0)->after('quantity_received');
            $table->text('defect_notes')->nullable()->after('quantity_defective');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_received', 'quantity_defective', 'defect_notes']);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['discrepancy_notes', 'return_proof_path']);
        });
    }
};
