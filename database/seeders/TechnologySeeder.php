<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Technology;
use App\Models\AppTechnology;
use App\Models\App;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding technology data...');

        // Create base technologies in the technologies table
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

        // Insert base technologies
        foreach ($technologies as $tech) {
            Technology::firstOrCreate(
                ['type' => $tech['type'], 'name' => $tech['name']],
                $tech
            );
        }

        $this->command->info('Base technologies created successfully.');

        // Create sample app-technology relationships if apps exist
        $apps = App::all();
        if ($apps->count() > 0) {
            $this->command->info('Creating sample app-technology relationships...');
            $this->createSampleAppTechnologies($apps);
        }

        $this->command->info('Technology seeding completed!');
    }

    /**
     * Create sample app-technology relationships
     */
    private function createSampleAppTechnologies($apps)
    {
        // Get technology IDs by type for easy reference
        $vendors = Technology::where('type', 'vendors')->pluck('id', 'name');
        $operatingSystems = Technology::where('type', 'operating_systems')->pluck('id', 'name');
        $databases = Technology::where('type', 'databases')->pluck('id', 'name');
        $languages = Technology::where('type', 'programming_languages')->pluck('id', 'name');
        $thirdParties = Technology::where('type', 'third_parties')->pluck('id', 'name');
        $middlewares = Technology::where('type', 'middlewares')->pluck('id', 'name');
        $frameworks = Technology::where('type', 'frameworks')->pluck('id', 'name');
        $platforms = Technology::where('type', 'platforms')->pluck('id', 'name');

        foreach ($apps as $app) {
            // Every app gets 1 vendor
            $vendorName = fake()->randomElement($vendors->keys()->toArray());
            AppTechnology::firstOrCreate([
                'app_id' => $app->app_id,
                'tech_id' => $vendors[$vendorName],
            ], [
                'version' => fake()->optional(0.7)->numerify('v#.#.#')
            ]);

            // Every app gets 1 operating system
            $osName = fake()->randomElement($operatingSystems->keys()->toArray());
            AppTechnology::firstOrCreate([
                'app_id' => $app->app_id,
                'tech_id' => $operatingSystems[$osName],
            ], [
                'version' => fake()->optional(0.8)->numerify('#.#')
            ]);

            // Every app gets 1-2 databases
            $dbCount = fake()->numberBetween(1, 2);
            $usedDatabases = [];
            for ($j = 0; $j < $dbCount; $j++) {
                $dbName = fake()->randomElement($databases->keys()->toArray());
                if (!in_array($dbName, $usedDatabases)) {
                    $usedDatabases[] = $dbName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $databases[$dbName],
                    ], [
                        'version' => fake()->optional(0.8)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 programming languages
            $langCount = fake()->numberBetween(1, 2);
            $usedLanguages = [];
            for ($j = 0; $j < $langCount; $j++) {
                $langName = fake()->randomElement($languages->keys()->toArray());
                if (!in_array($langName, $usedLanguages)) {
                    $usedLanguages[] = $langName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $languages[$langName],
                    ], [
                        'version' => fake()->optional(0.6)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 third party tools
            $thirdPartyCount = fake()->numberBetween(1, 2);
            $usedThirdParties = [];
            for ($j = 0; $j < $thirdPartyCount; $j++) {
                $thirdPartyName = fake()->randomElement($thirdParties->keys()->toArray());
                if (!in_array($thirdPartyName, $usedThirdParties)) {
                    $usedThirdParties[] = $thirdPartyName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $thirdParties[$thirdPartyName],
                    ], [
                        'version' => fake()->optional(0.5)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 middlewares
            $middlewareCount = fake()->numberBetween(1, 2);
            $usedMiddlewares = [];
            for ($j = 0; $j < $middlewareCount; $j++) {
                $middlewareName = fake()->randomElement($middlewares->keys()->toArray());
                if (!in_array($middlewareName, $usedMiddlewares)) {
                    $usedMiddlewares[] = $middlewareName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $middlewares[$middlewareName],
                    ], [
                        'version' => fake()->optional(0.7)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-3 frameworks
            $frameworkCount = fake()->numberBetween(1, 3);
            $usedFrameworks = [];
            for ($j = 0; $j < $frameworkCount; $j++) {
                $frameworkName = fake()->randomElement($frameworks->keys()->toArray());
                if (!in_array($frameworkName, $usedFrameworks)) {
                    $usedFrameworks[] = $frameworkName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $frameworks[$frameworkName],
                    ], [
                        'version' => fake()->optional(0.8)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 platforms
            $platformCount = fake()->numberBetween(1, 2);
            $usedPlatforms = [];
            for ($j = 0; $j < $platformCount; $j++) {
                $platformName = fake()->randomElement($platforms->keys()->toArray());
                if (!in_array($platformName, $usedPlatforms)) {
                    $usedPlatforms[] = $platformName;
                    AppTechnology::firstOrCreate([
                        'app_id' => $app->app_id,
                        'tech_id' => $platforms[$platformName],
                    ], [
                        'version' => fake()->optional(0.4)->numerify('#.#')
                    ]);
                }
            }
        }
    }
}
