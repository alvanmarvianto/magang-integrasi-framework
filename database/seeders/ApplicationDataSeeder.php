<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stream;
use App\Models\ConnectionType;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Contract;
use App\Models\ContractPeriod;
use Illuminate\Support\Facades\DB;

class ApplicationDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {        
        $this->command->info('Seeding application data...');

        // 1. Create Streams (8 streams)
        $this->command->info('Creating streams...');
        $streams = [
            'sp',
            'mi',
            'ssk',
            'moneter',
            'market',
            'internal bi',
            'external bi',
            'middleware'
        ];
        
        foreach ($streams as $streamName) {
            Stream::create(['stream_name' => $streamName]);
        }

        // 2. Create Connection Types (exactly 4 types)
        $this->command->info('Creating connection types...');
        $connectionTypes = [
            ['type_name' => 'direct', 'color' => '#000000'],
            ['type_name' => 'sftp', 'color' => '#002ac0'],
            ['type_name' => 'soa', 'color' => '#02a330'],
            ['type_name' => 'soa-sftp', 'color' => '#6b7280']
        ];
        
        foreach ($connectionTypes as $connectionType) {
            ConnectionType::create($connectionType);
        }

        // 3. Create Apps (24 apps - 3 per stream to ensure every stream has apps)
        $this->command->info('Creating apps...');
        $streamIds = Stream::pluck('stream_id')->toArray();
        $appNames = [
            // SP Stream Apps
            'Core Banking System', 'Payment Gateway', 'Transaction Processing',
            // MI Stream Apps  
            'Market Data System', 'Trading Platform', 'Settlement Engine',
            // SSK Stream Apps
            'Surveillance System', 'Communication Hub', 'Monitoring Dashboard',
            // Moneter Stream Apps
            'Monetary Policy System', 'Interest Rate Manager', 'Reserve Calculator',
            // Market Stream Apps
            'Market Operations', 'Bond Trading', 'Auction System',
            // Internal BI Stream Apps
            'Internal Analytics', 'Performance Dashboard', 'Risk Analytics',
            // External BI Stream Apps
            'External Reporting', 'Regulatory Reports', 'Public Dashboard',
            // Middleware Stream Apps
            'Message Broker', 'API Gateway', 'Service Bus'
        ];

        // Distribute apps evenly across streams (3 apps per stream)
        for ($i = 0; $i < count($appNames); $i++) {
            $streamIndex = $i % count($streamIds); // Cycle through streams
            App::create([
                'app_name' => $appNames[$i],
                'stream_id' => $streamIds[$streamIndex],
                'description' => fake()->sentence(10),
                'app_type' => fake()->randomElement(['cots', 'inhouse', 'outsource']),
                'stratification' => fake()->randomElement(['strategis', 'kritikal', 'umum'])
            ]);
        }

        // 4. Create App Integrations (ensure every app has at least 1-2 integrations)
        $this->command->info('Creating app integrations...');
        $appIds = App::pluck('app_id')->toArray();
        $connectionTypeIds = ConnectionType::pluck('connection_type_id')->toArray();

        // First, ensure every app has at least 1 integration as source
        foreach ($appIds as $sourceAppId) {
            $targetAppId = fake()->randomElement(array_diff($appIds, [$sourceAppId]));
            $connectionTypeId = fake()->randomElement($connectionTypeIds);

            AppIntegration::create([
                'source_app_id' => $sourceAppId,
                'target_app_id' => $targetAppId,
                'connection_type_id' => $connectionTypeId,
                'inbound_description' => fake()->sentence(8),
                'outbound_description' => fake()->sentence(8),
                'endpoint' => fake()->url(),
                'direction' => fake()->randomElement(['one_way', 'both_ways'])
            ]);
        }

        // Add additional random integrations
        for ($i = 0; $i < 15; $i++) {
            $sourceAppId = fake()->randomElement($appIds);
            $targetAppId = fake()->randomElement(array_diff($appIds, [$sourceAppId]));
            $connectionTypeId = fake()->randomElement($connectionTypeIds);

            // Check if this combination already exists
            $exists = AppIntegration::where('source_app_id', $sourceAppId)
                ->where('target_app_id', $targetAppId)
                ->where('connection_type_id', $connectionTypeId)
                ->exists();

            if (!$exists) {
                AppIntegration::create([
                    'source_app_id' => $sourceAppId,
                    'target_app_id' => $targetAppId,
                    'connection_type_id' => $connectionTypeId,
                    'inbound_description' => fake()->sentence(8),
                    'outbound_description' => fake()->sentence(8),
                    'endpoint' => fake()->url(),
                    'direction' => fake()->randomElement(['one_way', 'both_ways'])
                ]);
            }
        }

        // 5. Create Contracts (12 contracts)
        $this->command->info('Creating contracts...');
        for ($i = 0; $i < 12; $i++) {
            $currencyType = fake()->randomElement(['rp', 'non_rp']);
            
            $contract = Contract::create([
                'title' => fake()->sentence(5),
                'contract_number' => 'CT-' . fake()->unique()->numerify('####-###'),
                'currency_type' => $currencyType,
                'contract_value_rp' => $currencyType === 'rp' ? fake()->randomFloat(2, 100000000, 5000000000) : null,
                'contract_value_non_rp' => $currencyType === 'non_rp' ? fake()->randomFloat(2, 100000, 5000000) : null,
                'lumpsum_value_rp' => fake()->randomFloat(2, 50000000, 1000000000),
                'unit_value_rp' => fake()->randomFloat(2, 1000000, 100000000)
            ]);

            // Create 11-15 contract periods for each contract (more than 10)
            $periodsCount = fake()->numberBetween(11, 15);
            $currentDate = now();
            
            // Determine contract type based on index:
            // - Contracts 0-1 (2 contracts): Have near due periods but no overdue
            // - Contracts 2-7 (6 contracts): Safe - no overdue or near due periods
            // - Contracts 8-11 (4 contracts): Have overdue periods
            
            if ($i < 2) {
                // Contracts 0-1: Have near due periods but no overdue
                for ($j = 0; $j < $periodsCount; $j++) {
                    $statusPercentage = (($j + 1) / $periodsCount) * 100;
                    
                    if ($statusPercentage <= 50) {
                        // 50% of periods are paid and well past due (safe)
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } elseif ($statusPercentage <= 80) {
                        // 30% of periods are near due date but not paid (7-14 days from now)
                        $paymentStatus = fake()->randomElement(['unpaid', 'has_issue', 'not_due']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(7, 14));
                    } else {
                        // 20% of periods are well in advance (safe)
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                }
            } elseif ($i < 8) {
                // Contracts 2-7: Safe contracts - no overdue or near due periods
                for ($j = 0; $j < $periodsCount; $j++) {
                    if ($j < ($periodsCount * 0.6)) {
                        // 60% of periods are paid and well past due
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } else {
                        // 40% of periods are well in advance (not near due)
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process', 'mka_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                }
            } else {
                // Contracts 8-11: Have overdue periods
                for ($j = 0; $j < $periodsCount; $j++) {
                    $statusPercentage = (($j + 1) / $periodsCount) * 100;
                    
                    if ($statusPercentage <= 30) {
                        // 30% of periods are paid and past due
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } elseif ($statusPercentage <= 60) {
                        // 30% of periods are overdue and unpaid (1-30 days past due)
                        $paymentStatus = fake()->randomElement(['unpaid', 'has_issue']);
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(1, 30));
                    } else {
                        // 40% of periods are well in advance (safe)
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                }
            }
            
            for ($j = 0; $j < $periodsCount; $j++) {
                // Set the appropriate dates and status based on contract type above
                if ($i < 2) {
                    // Near due contracts
                    $statusPercentage = (($j + 1) / $periodsCount) * 100;
                    if ($statusPercentage <= 50) {
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } elseif ($statusPercentage <= 80) {
                        $paymentStatus = fake()->randomElement(['unpaid', 'has_issue', 'not_due']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(7, 14));
                    } else {
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                } elseif ($i < 8) {
                    // Safe contracts
                    if ($j < ($periodsCount * 0.6)) {
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } else {
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process', 'mka_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                } else {
                    // Overdue contracts
                    $statusPercentage = (($j + 1) / $periodsCount) * 100;
                    if ($statusPercentage <= 30) {
                        $paymentStatus = 'paid';
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(30, 180));
                    } elseif ($statusPercentage <= 60) {
                        $paymentStatus = fake()->randomElement(['unpaid', 'has_issue']);
                        $endDate = $currentDate->copy()->subDays(fake()->numberBetween(1, 30));
                    } else {
                        $paymentStatus = fake()->randomElement(['not_due', 'ba_process']);
                        $endDate = $currentDate->copy()->addDays(fake()->numberBetween(30, 365));
                    }
                }
                
                $startDate = $endDate->copy()->subMonths(1);
                
                ContractPeriod::create([
                    'contract_id' => $contract->id,
                    'period_name' => 'Period ' . ($j + 1),
                    'budget_type' => fake()->randomElement(['AO', 'RI']),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'payment_value_rp' => fake()->randomFloat(2, 10000000, 500000000),
                    'payment_value_non_rp' => fake()->numberBetween(10000, 500000),
                    'payment_status' => $paymentStatus
                ]);
            }
        }

        // 6. Create App-Contract relationships (ensure every app has a chance to have contracts)
        $this->command->info('Creating app-contract relationships...');
        $contractIds = Contract::pluck('id')->toArray();
        
        // Give each app a 60% chance of having a contract
        foreach ($appIds as $appId) {
            if (fake()->boolean(60)) { // 60% chance
                $contractId = fake()->randomElement($contractIds);
                
                // Check if this combination already exists
                $exists = \DB::table('app_contract')
                    ->where('app_id', $appId)
                    ->where('contract_id', $contractId)
                    ->exists();

                if (!$exists) {
                    \DB::table('app_contract')->insert([
                        'app_id' => $appId,
                        'contract_id' => $contractId
                    ]);
                }
            }
        }

        // Add some apps with multiple contracts
        for ($i = 0; $i < 8; $i++) {
            $appId = fake()->randomElement($appIds);
            $contractId = fake()->randomElement($contractIds);

            // Check if this combination already exists
            $exists = \DB::table('app_contract')
                ->where('app_id', $appId)
                ->where('contract_id', $contractId)
                ->exists();

            if (!$exists) {
                \DB::table('app_contract')->insert([
                    'app_id' => $appId,
                    'contract_id' => $contractId
                ]);
            }
        }

        // 7. Create Technology Data (ensure every app has comprehensive technology stack)
        $this->command->info('Creating technology data...');
        
        // Define technology options for each category
        $technologyOptions = [
            'vendors' => [
                'PT. Praweda Ciptakarsa Informatika',
                'CMA Small Systems AB',
                'PT Murni Solusindo Nusantara',
                'PT. Metrocom Global Solusi',
                'Intellect Design Arena',
                'In-House',
                'Lintasarta'
            ],
            'operating_systems' => ['Oracle Solaris', 'Windows Server', 'RHEL'],
            'databases' => ['Oracle Database', 'Microsoft SQL Server', 'SQL Server'],
            'programming_languages' => ['C++', 'Java', 'ASP.Net', 'C#', '.Net Programming Languages'],
            'third_parties' => [
                'Jasper Studio', 'Crystal Reports', 'Jasper', 'IIS', 'Tomcat',
                'SecureBackBox', 'LDAP', 'N/A'
            ],
            'middlewares' => [
                'Apache Tomcat', 'Web Logic', 'IBM WAS ND', 'Apache Kafka',
                'Openshift', 'RabbitMQ', 'Jboss-EAP', 'Kafka',
                'Oracle Advanced Queuing', 'Weblogic', 'IIS Server', 'N/A'
            ],
            'frameworks' => [
                'JDK', '.Net Framework', 'R2dbc', 'Springboot', 'Spring Boot',
                'Thymeleaf', 'Panacea', 'ASP.NET Core', 'Spring', 'dotnet Core',
                'dotnet MVC', 'Kafka', 'Angular WASM', 'Maven', 'Api Gateway'
            ],
            'platforms' => [
                'Web Based', 'STP', 'Desktop Based', 'Microservices',
                'Monolithic', 'Mobile based'
            ]
        ];

        // For each app, assign comprehensive technology stack
        foreach ($appIds as $appId) {
            // Every app gets 1 vendor
            \DB::table('technology_vendors')->insert([
                'app_id' => $appId,
                'name' => fake()->randomElement($technologyOptions['vendors']),
                'version' => fake()->optional(0.7)->numerify('v#.#.#')
            ]);

            // Every app gets 1 operating system
            \DB::table('technology_operating_systems')->insert([
                'app_id' => $appId,
                'name' => fake()->randomElement($technologyOptions['operating_systems']),
                'version' => fake()->optional(0.8)->numerify('#.#')
            ]);

            // Every app gets 1-2 databases
            $dbCount = fake()->numberBetween(1, 2);
            for ($j = 0; $j < $dbCount; $j++) {
                \DB::table('technology_databases')->insert([
                    'app_id' => $appId,
                    'name' => fake()->randomElement($technologyOptions['databases']),
                    'version' => fake()->optional(0.8)->numerify('#.#')
                ]);
            }

            // Every app gets 1-2 programming languages
            $langCount = fake()->numberBetween(1, 2);
            $usedLanguages = [];
            for ($j = 0; $j < $langCount; $j++) {
                $language = fake()->randomElement($technologyOptions['programming_languages']);
                if (!in_array($language, $usedLanguages)) {
                    $usedLanguages[] = $language;
                    \DB::table('technology_programming_languages')->insert([
                        'app_id' => $appId,
                        'name' => $language,
                        'version' => fake()->optional(0.6)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 third party tools
            $thirdPartyCount = fake()->numberBetween(1, 2);
            for ($j = 0; $j < $thirdPartyCount; $j++) {
                \DB::table('technology_third_parties')->insert([
                    'app_id' => $appId,
                    'name' => fake()->randomElement($technologyOptions['third_parties']),
                    'version' => fake()->optional(0.5)->numerify('#.#')
                ]);
            }

            // Every app gets 1-2 middlewares
            $middlewareCount = fake()->numberBetween(1, 2);
            for ($j = 0; $j < $middlewareCount; $j++) {
                \DB::table('technology_middlewares')->insert([
                    'app_id' => $appId,
                    'name' => fake()->randomElement($technologyOptions['middlewares']),
                    'version' => fake()->optional(0.7)->numerify('#.#')
                ]);
            }

            // Every app gets 1-3 frameworks
            $frameworkCount = fake()->numberBetween(1, 3);
            $usedFrameworks = [];
            for ($j = 0; $j < $frameworkCount; $j++) {
                $framework = fake()->randomElement($technologyOptions['frameworks']);
                if (!in_array($framework, $usedFrameworks)) {
                    $usedFrameworks[] = $framework;
                    \DB::table('technology_frameworks')->insert([
                        'app_id' => $appId,
                        'name' => $framework,
                        'version' => fake()->optional(0.8)->numerify('#.#')
                    ]);
                }
            }

            // Every app gets 1-2 platforms
            $platformCount = fake()->numberBetween(1, 2);
            for ($j = 0; $j < $platformCount; $j++) {
                \DB::table('technology_platforms')->insert([
                    'app_id' => $appId,
                    'name' => fake()->randomElement($technologyOptions['platforms']),
                    'version' => fake()->optional(0.4)->numerify('#.#')
                ]);
            }
        }

        $this->command->info('Application data seeding completed!');
    }
}
