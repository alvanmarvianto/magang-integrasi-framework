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
            // Drop the description column
            $table->dropColumn('description');
            
            // Add inbound and outbound columns
            $table->text('inbound')->nullable()->after('connection_type_id');
            $table->text('outbound')->nullable()->after('inbound');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appintegrations', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn(['inbound', 'outbound']);
            
            // Add back the description column
            $table->text('description')->nullable()->after('connection_type_id');
        });
    }
};
