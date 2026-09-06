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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20)->index(); // INQ, QUO, SO, DO, COM
            $table->string('year_month', 6)->index();     // YYYYMM
            $table->unsignedInteger('current_sequence')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'year_month'], 'uq_doc_seq_type_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
