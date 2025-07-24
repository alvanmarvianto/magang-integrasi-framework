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
        Schema::table('appintegrations', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign('appintegrations_ibfk_1');
            $table->dropForeign('appintegrations_ibfk_2');
            $table->dropForeign('appintegrations_ibfk_3');
            
            // Recreate foreign key constraints with cascade
            $table->foreign(['source_app_id'], 'appintegrations_ibfk_1')->references(['app_id'])->on('apps')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['target_app_id'], 'appintegrations_ibfk_2')->references(['app_id'])->on('apps')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['connection_type_id'], 'appintegrations_ibfk_3')->references(['connection_type_id'])->on('connectiontypes')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appintegrations', function (Blueprint $table) {
            // Drop cascade foreign key constraints
            $table->dropForeign('appintegrations_ibfk_1');
            $table->dropForeign('appintegrations_ibfk_2');
            $table->dropForeign('appintegrations_ibfk_3');
            
            // Recreate foreign key constraints with restrict (original behavior)
            $table->foreign(['source_app_id'], 'appintegrations_ibfk_1')->references(['app_id'])->on('apps')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['target_app_id'], 'appintegrations_ibfk_2')->references(['app_id'])->on('apps')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['connection_type_id'], 'appintegrations_ibfk_3')->references(['connection_type_id'])->on('connectiontypes')->onUpdate('restrict')->onDelete('restrict');
        });
    }
};
