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
        Schema::create('app_contract', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
            
            // Ensure unique combination of app and contract
            $table->unique(['app_id', 'contract_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_contract');
    }
};
