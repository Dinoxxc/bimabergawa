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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('avatar_url', 255)->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('password');
            $table->json('preferences')->nullable()->after('is_active');
            $table->foreignId('freelance_sales_id')
                ->nullable()
                ->after('preferences')
                ->constrained('freelance_sales')
                ->onDelete('set null');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['freelance_sales_id']);
            $table->dropColumn(['phone', 'avatar_url', 'is_active', 'preferences', 'freelance_sales_id']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
