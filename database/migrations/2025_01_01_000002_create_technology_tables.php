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
        // Create technology_vendors table
        Schema::create('technology_vendors', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
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

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });

        // Create technology_operating_systems table
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

        // Create technology_databases table
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

        // Create technology_programming_languages table
        Schema::create('technology_programming_languages', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->enum('name', [
                'C++',
                'Java',
                'ASP.Net',
                'C#',
                '.Net Programming Languages'
            ]);
            $table->string('version')->nullable();

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });

        // Create technology_third_parties table
        Schema::create('technology_third_parties', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
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

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });

        // Create technology_middlewares table
        Schema::create('technology_middlewares', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->enum('name', [
                'Apache Tomcat',
                'Web Logic',
                'IBM WAS ND',
                'Apache Kafka',
                'Openshift',
                'RabbitMQ',
                'Jboss-EAP',
                'Kafka',
                'Oracle Advanced Queuing',
                'Weblogic',
                'IIS Server',
                'N/A'
            ]);
            $table->string('version')->nullable();

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });

        // Create technology_frameworks table
        Schema::create('technology_frameworks', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
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

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
        });

        // Create technology_platforms table
        Schema::create('technology_platforms', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id');
            $table->enum('name', [
                'Web Based',
                'STP',
                'Desktop Based',
                'Microservices',
                'Monolithic',
                'Mobile based'
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
        Schema::dropIfExists('technology_platforms');
        Schema::dropIfExists('technology_frameworks');
        Schema::dropIfExists('technology_middlewares');
        Schema::dropIfExists('technology_third_parties');
        Schema::dropIfExists('technology_programming_languages');
        Schema::dropIfExists('technology_databases');
        Schema::dropIfExists('technology_operating_systems');
        Schema::dropIfExists('technology_vendors');
    }
};
