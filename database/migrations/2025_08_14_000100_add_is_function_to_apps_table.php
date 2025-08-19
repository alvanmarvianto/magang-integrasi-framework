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
        Schema::table('apps', function (Blueprint $table) {
            if (!Schema::hasColumn('apps', 'is_module')) {
                $table->boolean('is_module')->default(false)->after('stratification');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            if (Schema::hasColumn('apps', 'is_module')) {
                $table->dropColumn('is_module');
            }
        });
    }
};
