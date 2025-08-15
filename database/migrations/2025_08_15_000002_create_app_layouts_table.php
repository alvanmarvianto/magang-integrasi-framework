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
        Schema::create('app_layouts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('app_id')->index('app_id');
            $table->json('nodes_layout')->nullable();
            $table->json('edges_layout')->nullable();
            $table->json('app_config')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('app_layouts');
    }
};
