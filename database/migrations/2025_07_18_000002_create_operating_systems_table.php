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
        Schema::create('technology_operating_systems', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->enum('name', [
                'Oracle Solaris',
                'Windows Server',
                'RHEL'
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
        Schema::dropIfExists('technology_operating_systems');
    }
};
