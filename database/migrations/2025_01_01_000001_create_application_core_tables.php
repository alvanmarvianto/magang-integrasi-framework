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
        // Create streams table first (referenced by apps)
        Schema::create('streams', function (Blueprint $table) {
            $table->integer('stream_id', true);
            $table->string('stream_name')->unique('stream_name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#000000'); // Hex color code for allowed streams
            $table->boolean('is_allowed_for_diagram')->default(false);
            $table->integer('sort_order')->nullable(); // Only for allowed streams
        });

        // Create connectiontypes table (referenced by appintegrations)
        Schema::create('connectiontypes', function (Blueprint $table) {
            $table->integer('connection_type_id', true);
            $table->string('type_name')->unique('type_name');
            $table->string('color', 7)->default('#000000'); // Hex color code
        });

        // Create apps table
        Schema::create('apps', function (Blueprint $table) {
            $table->integer('app_id', true);
            $table->string('app_name');
            $table->integer('stream_id')->nullable()->index('stream_id');
            $table->text('description')->nullable();
            $table->enum('app_type', ['cots', 'inhouse', 'outsource']);
            $table->enum('stratification', ['strategis', 'kritikal', 'umum']);
        });

        // Create appintegrations table
        Schema::create('appintegrations', function (Blueprint $table) {
            $table->integer('integration_id', true);
            $table->integer('source_app_id')->nullable();
            $table->integer('target_app_id')->nullable()->index('target_app_id');
            $table->integer('connection_type_id')->nullable()->index('connection_type_id');

            $table->unique(['source_app_id', 'target_app_id', 'connection_type_id'], 'source_app_id');
        });

        // New table to hold multiple connection types per integration, with per-app inbound/outbound fields
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

        // Create stream_layouts table
        Schema::create('stream_layouts', function (Blueprint $table) {
            $table->id();
            $table->integer('stream_id')->unique();
            $table->json('nodes_layout');
            $table->json('stream_config');
            $table->json('edges_layout')->nullable();
            $table->timestamps();
        });

        // Create app_layouts table
        Schema::create('app_layouts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('app_id')->index('app_id');
            $table->json('nodes_layout')->nullable();
            $table->json('edges_layout')->nullable();
            $table->json('app_config')->nullable();
            $table->timestamps();
        });

        // Add foreign key constraints
        Schema::table('apps', function (Blueprint $table) {
            $table->foreign(['stream_id'], 'apps_ibfk_1')->references(['stream_id'])->on('streams')->onUpdate('restrict')->onDelete('restrict');
        });

        Schema::table('appintegrations', function (Blueprint $table) {
            $table->foreign(['source_app_id'], 'appintegrations_ibfk_1')->references(['app_id'])->on('apps')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['target_app_id'], 'appintegrations_ibfk_2')->references(['app_id'])->on('apps')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['connection_type_id'], 'appintegrations_ibfk_3')->references(['connection_type_id'])->on('connectiontypes')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('stream_layouts', function (Blueprint $table) {
            $table->foreign(['stream_id'], 'stream_layouts_ibfk_1')->references(['stream_id'])->on('streams')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('app_layouts', function (Blueprint $table) {
            $table->foreign(['app_id'], 'app_layouts_ibfk_1')
                ->references(['app_id'])->on('apps')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appintegrations');
        Schema::dropIfExists('stream_layouts');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('connectiontypes');
        Schema::dropIfExists('streams');
        Schema::dropIfExists('appintegration_connections');
        Schema::dropIfExists('app_layouts');
    }
};
