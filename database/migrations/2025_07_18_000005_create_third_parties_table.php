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
        Schema::create('technology_third_parties', function (Blueprint $table) {
            $table->id();
            $table->integer('technology_id');
            $table->enum('name', [
                'Jasper Studio',
                'Crystal Reports',
                'Jasper',
                'IIS',
                'Tomcat',
                'SecureBackBox',
                'LDAP',
                'N/A'
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
        Schema::dropIfExists('technology_third_parties');
    }
};
