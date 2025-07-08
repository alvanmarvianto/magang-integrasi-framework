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
        Schema::create('technologies', function (Blueprint $table) {
            $table->integer('technology_id', true);
            $table->integer('app_id')->unique('app_id');
            $table->string('vendor');
            $table->enum('app_type', ['cots', 'inhouse', 'outsource']);
            $table->enum('stratification', ['strategis', 'kritikal', 'umum']);
            $table->string('os');
            $table->string('database');
            $table->string('language');
            $table->enum('drc', ['-', '1', '2', '12']);
            $table->enum('failover', ['aktif-pasif', 'no', '-']);
            $table->string('third_party');
            $table->string('middleware');
            $table->string('framework');
            $table->string('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
