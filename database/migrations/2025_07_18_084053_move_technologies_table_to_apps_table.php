<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'technology_frameworks',
            'technology_middlewares',
            'technology_operating_systems',
            'technology_databases',
            'technology_platforms',
            'technology_programming_languages',
            'technology_third_parties',
            'technology_vendors',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'technology_id')) {
                    $table->dropForeign(['technology_id']);
                    $table->dropColumn('technology_id');
                }

                if (!Schema::hasColumn($tableName, 'app_id')) {
                    $table->integer('app_id')->after('id');
                    $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
                }
            });
        }

        Schema::table('apps', function (Blueprint $table) {
            if (!Schema::hasColumn('apps', 'app_type')) {
                $table->enum('app_type', ['cots', 'inhouse', 'outsource'])->nullable();
            } else {
                $table->enum('app_type', ['cots', 'inhouse', 'outsource'])->nullable()->change();
            }
            if (!Schema::hasColumn('apps', 'stratification')) {
                $table->enum('stratification', ['strategis', 'kritikal', 'umum'])->nullable();
            } else {
                $table->enum('stratification', ['strategis', 'kritikal', 'umum'])->nullable()->change();
            }
        });

        Schema::dropIfExists('technologies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the 'technologies' table first
        Schema::create('technologies', function (Blueprint $table) {
            $table->bigIncrements('technology_id');
            $table->string('name');
            $table->timestamps();
        });

        // Add back 'technology_id' columns and drop 'app_id' foreign keys
        $tables = [
            'technology_databases',
            'technology_operating_systems',
            'technology_middlewares',
            'technology_frameworks',
            'technology_platforms',
            'technology_programming_languages',
            'technology_third_parties',
            'technology_vendors',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'app_id')) {
                    $table->dropForeign(['app_id']);
                    $table->dropColumn('app_id');
                }
                
                if (!Schema::hasColumn($tableName, 'technology_id')) {
                    $table->integer('technology_id');
                    $table->foreign('technology_id')->references('technology_id')->on('technologies')->onDelete('cascade');
                }
            });
        }

        // Drop columns from 'apps' table
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('app_type');
            $table->dropColumn('stratification');
        });
    }
};
