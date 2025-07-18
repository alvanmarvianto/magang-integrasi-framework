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
        Schema::create('technology_frameworks', function (Blueprint $table) {
            $table->id();
            $table->integer('technology_id');
            $table->enum('name', [
                'JDK',
                '.Net Framework',
                'R2dbc',
                'Springboot',
                'Spring Boot',
                'Thymeleaf',
                'Panacea',
                'ASP.NET Core',
                'Spring',
                'dotnet Core',
                'dotnet MVC',
                'Kafka',
                'Angular WASM',
                'Maven',
                'Api Gateway'
            ]);
            $table->string('version')->nullable();

            $table->foreign('technology_id')->references('technology_id')->on('technologies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technology_frameworks');
    }
};
