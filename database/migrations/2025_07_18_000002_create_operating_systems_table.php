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
            $table->integer('technology_id');
            $table->enum('name', [
                'Oracle Solaris',
                'Red Hat Enterprise Linux',
                'Windows Server',
                'Linux RHEL'
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
        Schema::dropIfExists('technology_operating_systems');
    }
};
