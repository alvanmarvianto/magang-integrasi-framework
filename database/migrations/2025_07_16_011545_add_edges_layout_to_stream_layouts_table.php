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
        Schema::table('stream_layouts', function (Blueprint $table) {
            if (!Schema::hasColumn('stream_layouts', 'edges_layout')) {
                $table->json('edges_layout')->nullable()->after('stream_config'); // Store edge positions and waypoints {edge_id: {points: [...], style: {...}}}
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stream_layouts', function (Blueprint $table) {
            $table->dropColumn('edges_layout');
        });
    }
};
