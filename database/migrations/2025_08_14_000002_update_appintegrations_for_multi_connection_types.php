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
        // 1) New table to hold multiple connection types per integration, with per-app inbound/outbound fields
        Schema::create('appintegration_connections', function (Blueprint $table) {
            $table->id();
            $table->integer('integration_id'); // FK -> appintegrations.integration_id
            $table->integer('connection_type_id'); // FK -> connectiontypes.connection_type_id

            // Per-app payload/description fields
            $table->text('source_inbound')->nullable();
            $table->text('source_outbound')->nullable();
            $table->text('target_inbound')->nullable();
            $table->text('target_outbound')->nullable();

            // Enforce no duplicate connection type within the same integration
            $table->unique(['integration_id', 'connection_type_id'], 'integration_connectiontype_unique');
        });

        // Add foreign keys for the new table
        Schema::table('appintegration_connections', function (Blueprint $table) {
            $table->foreign('integration_id', 'aic_ibfk_integration')
                ->references('integration_id')->on('appintegrations')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('connection_type_id', 'aic_ibfk_connection_type')
                ->references('connection_type_id')->on('connectiontypes')
                ->onUpdate('cascade')->onDelete('cascade');
        });

        // 2) Trim appintegrations to only keep integration_id, source_app_id, target_app_id
        Schema::table('appintegrations', function (Blueprint $table) {
            // Drop FK on connection_type_id first (added in initial migration)
            if (Schema::hasColumn('appintegrations', 'connection_type_id')) {
                // Named in the original migration as 'appintegrations_ibfk_3'
                $table->dropForeign('appintegrations_ibfk_3');
            }

            // Drop the unique constraint across (source_app_id, target_app_id, connection_type_id)
            // Named 'source_app_id' in the initial migration
            $table->dropUnique('source_app_id');

            // Drop the index on connection_type_id if present (named explicitly 'connection_type_id')
            if (Schema::hasColumn('appintegrations', 'connection_type_id')) {
                $table->dropIndex('connection_type_id');
            }

            // Remove columns now modeled in the new junction table
            $columnsToDrop = array_filter([
                Schema::hasColumn('appintegrations', 'connection_type_id') ? 'connection_type_id' : null,
                Schema::hasColumn('appintegrations', 'inbound') ? 'inbound' : null,
                Schema::hasColumn('appintegrations', 'outbound') ? 'outbound' : null,
                Schema::hasColumn('appintegrations', 'connection_endpoint') ? 'connection_endpoint' : null,
                Schema::hasColumn('appintegrations', 'direction') ? 'direction' : null,
            ]);

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new connections table
        Schema::dropIfExists('appintegration_connections');

        // Restore the previous columns on appintegrations
        Schema::table('appintegrations', function (Blueprint $table) {
            // Recreate dropped columns
            $table->integer('connection_type_id')->nullable()->index('connection_type_id')->after('target_app_id');
            $table->text('inbound')->nullable()->after('connection_type_id');
            $table->text('outbound')->nullable()->after('inbound');
            $table->text('connection_endpoint')->nullable()->after('outbound');
            $table->enum('direction', ['one_way', 'both_ways'])->after('connection_endpoint');

            // Restore unique across (source_app_id, target_app_id, connection_type_id)
            $table->unique(['source_app_id', 'target_app_id', 'connection_type_id'], 'source_app_id');
        });

        // Restore FK on connection_type_id
        Schema::table('appintegrations', function (Blueprint $table) {
            $table->foreign('connection_type_id', 'appintegrations_ibfk_3')
                ->references('connection_type_id')->on('connectiontypes')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }
};
