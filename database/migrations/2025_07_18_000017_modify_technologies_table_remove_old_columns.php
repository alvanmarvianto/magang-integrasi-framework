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
            // Remove the old columns that are now in separate tables
            $table->dropColumn([
                'vendor',
                'os',
                'database',
                'language',
                'drc',
                'failover',
                'third_party',
                'middleware',
                'framework',
                'platform'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            // Add back the old columns
            $table->string('vendor')->nullable();
            $table->enum('app_type', ['cots', 'inhouse', 'outsource'])->nullable();
            $table->enum('stratification', ['strategis', 'kritikal', 'umum'])->nullable();
            $table->string('os')->nullable();
            $table->string('database')->nullable();
            $table->string('language')->nullable();
            $table->string('drc')->nullable();
            $table->string('failover')->nullable();
            $table->string('third_party')->nullable();
            $table->string('middleware')->nullable();
            $table->string('framework')->nullable();
            $table->string('platform')->nullable();
        });
    }
};
