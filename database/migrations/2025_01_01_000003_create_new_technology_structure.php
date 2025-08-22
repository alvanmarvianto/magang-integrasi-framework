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
        // Create technologies table
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'vendors',
                'operating_systems',
                'databases',
                'programming_languages',
                'third_parties',
                'middlewares',
                'frameworks',
                'platforms'
            ]);
            $table->string('name');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate technology names within the same type
            $table->unique(['type', 'name']);
        });

        // Create app_technologies table
        Schema::create('app_technologies', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->foreignId('tech_id')->constrained('technologies')->onDelete('cascade');
            $table->string('version')->nullable();
            $table->timestamps();

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
            
            // Add unique constraint to prevent duplicate tech assignments to the same app
            $table->unique(['app_id', 'tech_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_technologies');
        Schema::dropIfExists('technologies');
    }
};
