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
        Schema::create('appintegrations', function (Blueprint $table) {
            $table->integer('integration_id', true);
            $table->integer('source_app_id')->nullable();
            $table->integer('target_app_id')->nullable()->index('target_app_id');
            $table->integer('connection_type_id')->nullable()->index('connection_type_id');

            $table->unique(['source_app_id', 'target_app_id', 'connection_type_id'], 'source_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appintegrations');
    }
};
