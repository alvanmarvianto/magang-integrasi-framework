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
        Schema::create('technology_vendors', function (Blueprint $table) {
            $table->id();
            $table->integer('technology_id');
            $table->enum('name', [
                'PT. Praweda Ciptakarsa Informatika',
                'CMA Small Systems AB',
                'PT Murni Solusindo Nusantara',
                'PT. Metrocom Global Solusi',
                'Intellect Design Arena',
                'In-House',
                'Lintasarta'
            ]);
            $table->string('version')->nullable();
            $table->timestamps();

            $table->foreign('technology_id')->references('technology_id')->on('technologies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technology_vendors');
    }
};
