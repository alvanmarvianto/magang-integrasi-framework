<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, populate the technologies table with all unique technology names
        $this->populateTechnologies();
        
        // Then migrate data from old tables to new structure
        $this->migrateData();
        
        // Finally, drop the old tables
        $this->dropOldTables();
    }
    
    private function populateTechnologies(): void
    {
        $technologies = [
            // Vendors
            ['type' => 'vendors', 'name' => 'PT. Praweda Ciptakarsa Informatika'],
            ['type' => 'vendors', 'name' => 'CMA Small Systems AB'],
            ['type' => 'vendors', 'name' => 'PT Murni Solusindo Nusantara'],
            ['type' => 'vendors', 'name' => 'PT. Metrocom Global Solusi'],
            ['type' => 'vendors', 'name' => 'Intellect Design Arena'],
            ['type' => 'vendors', 'name' => 'In-House'],
            ['type' => 'vendors', 'name' => 'Lintasarta'],
            
            // Operating Systems
            ['type' => 'operating_systems', 'name' => 'Oracle Solaris'],
            ['type' => 'operating_systems', 'name' => 'Windows Server'],
            ['type' => 'operating_systems', 'name' => 'RHEL'],
            
            // Databases
            ['type' => 'databases', 'name' => 'Oracle Database'],
            ['type' => 'databases', 'name' => 'Microsoft SQL Server'],
            ['type' => 'databases', 'name' => 'SQL Server'],
            
            // Programming Languages
            ['type' => 'programming_languages', 'name' => 'C++'],
            ['type' => 'programming_languages', 'name' => 'Java'],
            ['type' => 'programming_languages', 'name' => 'ASP.Net'],
            ['type' => 'programming_languages', 'name' => 'C#'],
            ['type' => 'programming_languages', 'name' => '.Net Programming Languages'],
            
            // Third Parties
            ['type' => 'third_parties', 'name' => 'Jasper Studio'],
            ['type' => 'third_parties', 'name' => 'Crystal Reports'],
            ['type' => 'third_parties', 'name' => 'Jasper'],
            ['type' => 'third_parties', 'name' => 'IIS'],
            ['type' => 'third_parties', 'name' => 'Tomcat'],
            ['type' => 'third_parties', 'name' => 'SecureBackBox'],
            ['type' => 'third_parties', 'name' => 'LDAP'],
            ['type' => 'third_parties', 'name' => 'N/A'],
            
            // Middlewares
            ['type' => 'middlewares', 'name' => 'Apache Tomcat'],
            ['type' => 'middlewares', 'name' => 'Web Logic'],
            ['type' => 'middlewares', 'name' => 'IBM WAS ND'],
            ['type' => 'middlewares', 'name' => 'Apache Kafka'],
            ['type' => 'middlewares', 'name' => 'Openshift'],
            ['type' => 'middlewares', 'name' => 'RabbitMQ'],
            ['type' => 'middlewares', 'name' => 'Jboss-EAP'],
            ['type' => 'middlewares', 'name' => 'Kafka'],
            ['type' => 'middlewares', 'name' => 'Oracle Advanced Queuing'],
            ['type' => 'middlewares', 'name' => 'Weblogic'],
            ['type' => 'middlewares', 'name' => 'IIS Server'],
            ['type' => 'middlewares', 'name' => 'N/A'],
            
            // Frameworks
            ['type' => 'frameworks', 'name' => 'JDK'],
            ['type' => 'frameworks', 'name' => '.Net Framework'],
            ['type' => 'frameworks', 'name' => 'R2dbc'],
            ['type' => 'frameworks', 'name' => 'Springboot'],
            ['type' => 'frameworks', 'name' => 'Spring Boot'],
            ['type' => 'frameworks', 'name' => 'Thymeleaf'],
            ['type' => 'frameworks', 'name' => 'Panacea'],
            ['type' => 'frameworks', 'name' => 'ASP.NET Core'],
            ['type' => 'frameworks', 'name' => 'Spring'],
            ['type' => 'frameworks', 'name' => 'dotnet Core'],
            ['type' => 'frameworks', 'name' => 'dotnet MVC'],
            ['type' => 'frameworks', 'name' => 'Kafka'],
            ['type' => 'frameworks', 'name' => 'Angular WASM'],
            ['type' => 'frameworks', 'name' => 'Maven'],
            ['type' => 'frameworks', 'name' => 'Api Gateway'],
            
            // Platforms
            ['type' => 'platforms', 'name' => 'Web Based'],
            ['type' => 'platforms', 'name' => 'STP'],
            ['type' => 'platforms', 'name' => 'Desktop Based'],
            ['type' => 'platforms', 'name' => 'Microservices'],
            ['type' => 'platforms', 'name' => 'Monolithic'],
            ['type' => 'platforms', 'name' => 'Mobile based'],
        ];
        
        foreach ($technologies as $tech) {
            $tech['created_at'] = now();
            $tech['updated_at'] = now();
        }
        
        DB::table('technologies')->insert($technologies);
    }
    
    private function migrateData(): void
    {
        $oldTables = [
            'technology_vendors' => 'vendors',
            'technology_operating_systems' => 'operating_systems',
            'technology_databases' => 'databases',
            'technology_programming_languages' => 'programming_languages',
            'technology_third_parties' => 'third_parties',
            'technology_middlewares' => 'middlewares',
            'technology_frameworks' => 'frameworks',
            'technology_platforms' => 'platforms'
        ];
        
        foreach ($oldTables as $oldTable => $type) {
            if (Schema::hasTable($oldTable)) {
                $oldRecords = DB::table($oldTable)->get();
                
                foreach ($oldRecords as $record) {
                    // Find the technology ID
                    $technology = DB::table('technologies')
                        ->where('type', $type)
                        ->where('name', $record->name)
                        ->first();
                    
                    if ($technology) {
                        // Insert into app_technologies
                        DB::table('app_technologies')->insert([
                            'app_id' => $record->app_id,
                            'tech_id' => $technology->id,
                            'version' => $record->version,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
    
    private function dropOldTables(): void
    {
        $oldTables = [
            'technology_platforms',
            'technology_frameworks',
            'technology_middlewares',
            'technology_third_parties',
            'technology_programming_languages',
            'technology_databases',
            'technology_operating_systems',
            'technology_vendors'
        ];
        
        foreach ($oldTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate old tables structure
        $this->recreateOldTables();
        
        // Migrate data back from new structure to old structure
        $this->migrateDataBack();
        
        // Drop new tables
        Schema::dropIfExists('app_technologies');
        Schema::dropIfExists('technologies');
    }
    
    private function recreateOldTables(): void
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
    
    private function migrateDataBack(): void
    {
        $typeToTable = [
            'vendors' => 'technology_vendors',
            'operating_systems' => 'technology_operating_systems',
            'databases' => 'technology_databases',
            'programming_languages' => 'technology_programming_languages',
            'third_parties' => 'technology_third_parties',
            'middlewares' => 'technology_middlewares',
            'frameworks' => 'technology_frameworks',
            'platforms' => 'technology_platforms'
        ];
        
        // Get all app_technologies with their technology details
        $appTechnologies = DB::table('app_technologies')
            ->join('technologies', 'app_technologies.tech_id', '=', 'technologies.id')
            ->select('app_technologies.app_id', 'app_technologies.version', 'technologies.type', 'technologies.name')
            ->get();
        
        foreach ($appTechnologies as $appTech) {
            if (isset($typeToTable[$appTech->type])) {
                DB::table($typeToTable[$appTech->type])->insert([
                    'app_id' => $appTech->app_id,
                    'name' => $appTech->name,
                    'version' => $appTech->version,
                ]);
            }
        }
    }
};
