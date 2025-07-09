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
        Schema::table('technologies', function (Blueprint $table) {
            // Change drc from enum to varchar and make nullable
            $table->string('drc')->nullable()->change();
            
            // Change failover from enum to varchar and make nullable
            $table->string('failover')->nullable()->change();
            
            // Make all other attributes nullable except technology_id and app_id
            $table->string('vendor')->nullable()->change();
            $table->enum('app_type', ['cots', 'inhouse', 'outsource'])->nullable()->change();
            $table->enum('stratification', ['strategis', 'kritikal', 'umum'])->nullable()->change();
            $table->string('os')->nullable()->change();
            $table->string('database')->nullable()->change();
            $table->string('language')->nullable()->change();
            $table->string('third_party')->nullable()->change();
            $table->string('middleware')->nullable()->change();
            $table->string('framework')->nullable()->change();
            $table->string('platform')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            // Revert drc back to enum and make not nullable
            $table->enum('drc', ['-', '1', '2', '12'])->change();
            
            // Revert failover back to enum and make not nullable
            $table->enum('failover', ['aktif-pasif', 'no', '-'])->change();
            
            // Make all other attributes not nullable again
            $table->string('vendor')->nullable(false)->change();
            $table->enum('app_type', ['cots', 'inhouse', 'outsource'])->nullable(false)->change();
            $table->enum('stratification', ['strategis', 'kritikal', 'umum'])->nullable(false)->change();
            $table->string('os')->nullable(false)->change();
            $table->string('database')->nullable(false)->change();
            $table->string('language')->nullable(false)->change();
            $table->string('third_party')->nullable(false)->change();
            $table->string('middleware')->nullable(false)->change();
            $table->string('framework')->nullable(false)->change();
            $table->string('platform')->nullable(false)->change();
        });
    }
};
