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
            $table->dropColumn('starting_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appintegrations', function (Blueprint $table) {
            $table->enum('starting_point', ['source', 'target'])->nullable()->after('direction');
        });
    }
};
