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
            $table->text('description')->nullable()->after('connection_type_id');
            $table->string('connection_endpoint')->nullable()->after('description');
            $table->enum('direction', ['one_way', 'both_ways'])->default('one_way')->after('connection_endpoint');
            $table->enum('starting_point', ['source', 'target'])->nullable()->after('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appintegrations', function (Blueprint $table) {
            $table->dropColumn(['description', 'connection_endpoint', 'direction', 'starting_point']);
        });
    }
};
