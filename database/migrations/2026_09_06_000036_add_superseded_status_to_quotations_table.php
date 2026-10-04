<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft', 'pending_director_approval', 'approved', 'sent', 'expired', 'rejected', 'superseded') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft', 'pending_director_approval', 'approved', 'sent', 'expired', 'rejected') NOT NULL DEFAULT 'draft'");
        }
    }
};
