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
            ['type' => 'vendor', 'name' => 'PT. Praweda Ciptakarsa Informatika'],
            ['type' => 'vendor', 'name' => 'CMA Small Systems AB'],
            ['type' => 'vendor', 'name' => 'PT Murni Solusindo Nusantara'],
            ['type' => 'vendor', 'name' => 'PT. Metrocom Global Solusi'],
            ['type' => 'vendor', 'name' => 'Intellect Design Arena'],
            ['type' => 'vendor', 'name' => 'In-House'],
            ['type' => 'vendor', 'name' => 'Lintasarta'],
            
            // Operating Systems
            ['type' => 'os', 'name' => 'Oracle Solaris'],
            ['type' => 'os', 'name' => 'Windows Server'],
            ['type' => 'os', 'name' => 'RHEL'],
            
            // Databases
            ['type' => 'database', 'name' => 'Oracle Database'],
            ['type' => 'database', 'name' => 'Microsoft SQL Server'],
            ['type' => 'database', 'name' => 'SQL Server'],
            
            // Programming Languages
            ['type' => 'language', 'name' => 'C++'],
            ['type' => 'language', 'name' => 'Java'],
            ['type' => 'language', 'name' => 'ASP.Net'],
            ['type' => 'language', 'name' => 'C#'],
            ['type' => 'language', 'name' => '.Net Programming Languages'],
            
            // Third Parties
            ['type' => 'third_party', 'name' => 'Jasper Studio'],
            ['type' => 'third_party', 'name' => 'Crystal Reports'],
            ['type' => 'third_party', 'name' => 'Jasper'],
            ['type' => 'third_party', 'name' => 'IIS'],
            ['type' => 'third_party', 'name' => 'Tomcat'],
            ['type' => 'third_party', 'name' => 'SecureBackBox'],
            ['type' => 'third_party', 'name' => 'LDAP'],
            ['type' => 'third_party', 'name' => 'N/A'],
            
            // Middlewares
            ['type' => 'middleware', 'name' => 'Apache Tomcat'],
            ['type' => 'middleware', 'name' => 'Web Logic'],
            ['type' => 'middleware', 'name' => 'IBM WAS ND'],
            ['type' => 'middleware', 'name' => 'Apache Kafka'],
            ['type' => 'middleware', 'name' => 'Openshift'],
            ['type' => 'middleware', 'name' => 'RabbitMQ'],
            ['type' => 'middleware', 'name' => 'Jboss-EAP'],
            ['type' => 'middleware', 'name' => 'Kafka'],
            ['type' => 'middleware', 'name' => 'Oracle Advanced Queuing'],
            ['type' => 'middleware', 'name' => 'Weblogic'],
            ['type' => 'middleware', 'name' => 'IIS Server'],
            ['type' => 'middleware', 'name' => 'N/A'],
            
            // Frameworks
            ['type' => 'framework', 'name' => 'JDK'],
            ['type' => 'framework', 'name' => '.Net Framework'],
            ['type' => 'framework', 'name' => 'R2dbc'],
            ['type' => 'framework', 'name' => 'Springboot'],
            ['type' => 'framework', 'name' => 'Spring Boot'],
            ['type' => 'framework', 'name' => 'Thymeleaf'],
            ['type' => 'framework', 'name' => 'Panacea'],
            ['type' => 'framework', 'name' => 'ASP.NET Core'],
            ['type' => 'framework', 'name' => 'Spring'],
            ['type' => 'framework', 'name' => 'dotnet Core'],
            ['type' => 'framework', 'name' => 'dotnet MVC'],
            ['type' => 'framework', 'name' => 'Kafka'],
            ['type' => 'framework', 'name' => 'Angular WASM'],
            ['type' => 'framework', 'name' => 'Maven'],
            ['type' => 'framework', 'name' => 'Api Gateway'],
            
            // Platforms
            ['type' => 'platform', 'name' => 'Web Based'],
            ['type' => 'platform', 'name' => 'STP'],
            ['type' => 'platform', 'name' => 'Desktop Based'],
            ['type' => 'platform', 'name' => 'Microservices'],
            ['type' => 'platform', 'name' => 'Monolithic'],
            ['type' => 'platform', 'name' => 'Mobile based'],
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
        $vendors = Technology::where('type', 'vendor')->pluck('id', 'name');
        $operatingSystems = Technology::where('type', 'os')->pluck('id', 'name');
        $databases = Technology::where('type', 'database')->pluck('id', 'name');
        $languages = Technology::where('type', 'language')->pluck('id', 'name');
        $thirdParties = Technology::where('type', 'third_party')->pluck('id', 'name');
        $middlewares = Technology::where('type', 'middleware')->pluck('id', 'name');
        $frameworks = Technology::where('type', 'framework')->pluck('id', 'name');
        $platforms = Technology::where('type', 'platform')->pluck('id', 'name');

        foreach ($apps as $app) {
            // Every app gets 1 vendor
            $vendorName = fake()->randomElement($vendors->keys()->toArray());
            AppTechnology::firstOrCreate([
                'app_id' => $app->app_id,
                'technology_id' => $vendors[$vendorName],
            ], [
                'version' => fake()->optional(0.7)->numerify('v#.#.#')
            ]);

            // Every app gets 1 operating system
            $osName = fake()->randomElement($operatingSystems->keys()->toArray());
            AppTechnology::firstOrCreate([
                'app_id' => $app->app_id,
                'technology_id' => $operatingSystems[$osName],
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
                        'technology_id' => $databases[$dbName],
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
                        'technology_id' => $languages[$langName],
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
                        'technology_id' => $thirdParties[$thirdPartyName],
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
                        'technology_id' => $middlewares[$middlewareName],
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
                        'technology_id' => $frameworks[$frameworkName],
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
                        'technology_id' => $platforms[$platformName],
                    ], [
                        'version' => fake()->optional(0.4)->numerify('#.#')
                    ]);
                }
            }
        }
    }
}
