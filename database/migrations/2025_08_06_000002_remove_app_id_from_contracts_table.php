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
        Schema::table('contracts', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['app_id']);
            // Then drop the column
            $table->dropColumn('app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->integer('app_id')->after('id');
            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });
    }
};
