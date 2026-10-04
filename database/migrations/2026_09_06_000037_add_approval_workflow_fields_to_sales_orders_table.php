<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->text('director_notes')->nullable()->after('approved_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending_director_approval', 'approved', 'processing', 'indent_principal', 'ready_to_ship', 'shipped', 'partially_fulfilled', 'completed', 'cancelled') NOT NULL DEFAULT 'processing'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'director_notes']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('processing', 'indent_principal', 'ready_to_ship', 'shipped', 'partially_fulfilled', 'completed', 'cancelled') NOT NULL DEFAULT 'processing'");
        }
    }
};
