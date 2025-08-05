<?php

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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
            $table->string('title'); // Judul Kontrak/Nama Pekerjaan
            $table->string('contract_number'); // No. Kontrak
            $table->enum('currency_type', ['rp', 'non_rp']); // Currency type
            $table->decimal('contract_value_rp', 15, 2)->nullable(); // Nilai Kontrak (Rp)
            $table->decimal('contract_value_non_rp', 15, 2)->nullable(); // Nilai Kontrak (Non Rp)
            $table->decimal('lumpsum_value_rp', 15, 2)->nullable(); // Nilai Kontrak Lumpsum (Rp)
            $table->decimal('unit_value_rp', 15, 2)->nullable(); // Nilai Kontrak Satuan (Rp)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
