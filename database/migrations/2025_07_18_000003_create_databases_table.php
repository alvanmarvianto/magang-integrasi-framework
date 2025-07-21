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
        Schema::create('technology_databases', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->enum('name', [
                'Oracle Database',
                'Microsoft SQL Server',
                'SQL Server'
            ]);
            $table->string('version')->nullable();

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technology_databases');
    }
};
