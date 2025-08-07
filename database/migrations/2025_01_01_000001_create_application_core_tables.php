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
        });

        // Create connectiontypes table (referenced by appintegrations)
        Schema::create('connectiontypes', function (Blueprint $table) {
            $table->integer('connection_type_id', true);
            $table->string('type_name')->unique('type_name');
        });

        // Create apps table
        Schema::create('apps', function (Blueprint $table) {
            $table->integer('app_id', true);
            $table->string('app_name');
            $table->integer('stream_id')->nullable()->index('stream_id');
            $table->text('description')->nullable();
        });

        // Create appintegrations table
        Schema::create('appintegrations', function (Blueprint $table) {
            $table->integer('integration_id', true);
            $table->integer('source_app_id')->nullable();
            $table->integer('target_app_id')->nullable()->index('target_app_id');
            $table->integer('connection_type_id')->nullable()->index('connection_type_id');
            $table->text('inbound_description')->nullable();
            $table->text('outbound_description')->nullable();
            $table->text('endpoint')->nullable();

            $table->unique(['source_app_id', 'target_app_id', 'connection_type_id'], 'source_app_id');
        });

        // Create stream_layouts table
        Schema::create('stream_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('stream_name')->unique();
            $table->json('nodes_layout');
            $table->json('stream_config');
            $table->json('edges_layout')->nullable();
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
    }
};
