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
        if (Schema::hasTable('appintegration_functions')) {
            return; // table already exists
        }

        Schema::create('appintegration_functions', function (Blueprint $table) {
            $table->id();
            // Match parent column signedness (both are signed INT in core migration)
            $table->integer('app_id');
            $table->integer('integration_id');
            $table->string('function_name');
            $table->timestamps();

            $table->foreign('app_id')
                ->references('app_id')
                ->on('apps')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('integration_id')
                ->references('integration_id')
                ->on('appintegrations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['app_id', 'integration_id', 'function_name'], 'uniq_appintegration_function');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appintegration_functions');
    }
};
