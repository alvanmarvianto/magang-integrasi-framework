<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stream;
use App\Models\ConnectionType;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\AppIntegrationFunction;
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

        // 1. Create Streams with allowed diagram configuration
        $this->command->info('Creating streams...');
        $streamsData = [
            [
                'stream_name' => 'Stream SP',
                'description' => 'Aplikasi Sistem Pembayaran',
                'is_allowed_for_diagram' => true,
                'sort_order' => 1,
                'color' => '#FF6B35'
            ],
            [
                'stream_name' => 'Stream MI',
                'description' => 'Aplikasi Market',
                'is_allowed_for_diagram' => true,
                'sort_order' => 2,
                'color' => '#F7931E'
            ],
            [
                'stream_name' => 'Stream SSK',
                'description' => 'Aplikasi SSK',
                'is_allowed_for_diagram' => true,
                'sort_order' => 3,
                'color' => '#FFD23F'
            ],
            [
                'stream_name' => 'Stream Moneter',
                'description' => 'Aplikasi Moneter',
                'is_allowed_for_diagram' => true,
                'sort_order' => 4,
                'color' => '#06FFA5'
            ],
            [
                'stream_name' => 'Stream Market',
                'description' => 'Aplikasi Market',
                'is_allowed_for_diagram' => true,
                'sort_order' => 5,
                'color' => '#118AB2'
            ],
            [
                'stream_name' => 'Internal BI',
                'description' => 'Aplikasi Internal BI',
                'is_allowed_for_diagram' => false,
                'sort_order' => null,
                'color' => '#999999'
            ],
            [
                'stream_name' => 'External BI',
                'description' => 'Aplikasi External BI',
                'is_allowed_for_diagram' => false,
                'sort_order' => null,
                'color' => '#777777'
            ],
            [
                'stream_name' => 'Middleware',
                'description' => 'Aplikasi Middleware',
                'is_allowed_for_diagram' => true,
                'sort_order' => 6,
                'color' => '#8E44AD'
            ]
        ];
        
        foreach ($streamsData as $streamData) {
            Stream::create($streamData);
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
        // Some apps will be modules (is_module = true) to support function-based diagrams
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
        // Mark some apps as modules (every 3rd app will be a module)
        for ($i = 0; $i < count($appNames); $i++) {
            $streamIndex = $i % count($streamIds); // Cycle through streams
            $isModule = ($i % 3 === 0); // Every 3rd app is a module
            
            App::create([
                'app_name' => $appNames[$i],
                'stream_id' => $streamIds[$streamIndex],
                'description' => fake()->sentence(10),
                'app_type' => fake()->randomElement(['cots', 'inhouse', 'outsource']),
                'stratification' => fake()->randomElement(['strategis', 'kritikal', 'umum']),
                'is_module' => $isModule
            ]);
        }

        // 4. Create App Integrations (ensure every app has at least 1-2 integrations)
        // Using the new structure with appintegration_connections table
        $this->command->info('Creating app integrations...');
        $appIds = App::pluck('app_id')->toArray();
        $connectionTypeIds = ConnectionType::pluck('connection_type_id')->toArray();

        // First, ensure every app has at least 1 integration as source
        foreach ($appIds as $sourceAppId) {
            $targetAppId = fake()->randomElement(array_diff($appIds, [$sourceAppId]));

            // Create the integration first
            $integration = AppIntegration::create([
                'source_app_id' => $sourceAppId,
                'target_app_id' => $targetAppId,
                'connection_type_id' => null // Will be set via connections
            ]);

            // Add 1-2 connection types for this integration
            $connectionCount = fake()->numberBetween(1, 2);
            $usedConnectionTypes = [];
            
            for ($i = 0; $i < $connectionCount; $i++) {
                $connectionTypeId = fake()->randomElement($connectionTypeIds);
                
                // Avoid duplicate connection types for the same integration
                if (!in_array($connectionTypeId, $usedConnectionTypes)) {
                    $usedConnectionTypes[] = $connectionTypeId;
                    
                    // Create connection with detailed inbound/outbound for both apps
                    DB::table('appintegration_connections')->insert([
                        'integration_id' => $integration->integration_id,
                        'connection_type_id' => $connectionTypeId,
                        'source_inbound' => fake()->sentence(8),
                        'source_outbound' => fake()->sentence(8),
                        'target_inbound' => fake()->sentence(8),
                        'target_outbound' => fake()->sentence(8),
                    ]);
                }
            }
        }

        // Add additional random integrations
        for ($i = 0; $i < 15; $i++) {
            $sourceAppId = fake()->randomElement($appIds);
            $targetAppId = fake()->randomElement(array_diff($appIds, [$sourceAppId]));

            // Check if this app pair already has an integration
            $exists = AppIntegration::where('source_app_id', $sourceAppId)
                ->where('target_app_id', $targetAppId)
                ->exists();

            if (!$exists) {
                // Create the integration
                $integration = AppIntegration::create([
                    'source_app_id' => $sourceAppId,
                    'target_app_id' => $targetAppId,
                    'connection_type_id' => null // Will be set via connections
                ]);

                // Add 1-2 connection types for this integration
                $connectionCount = fake()->numberBetween(1, 2);
                $usedConnectionTypes = [];
                
                for ($j = 0; $j < $connectionCount; $j++) {
                    $connectionTypeId = fake()->randomElement($connectionTypeIds);
                    
                    // Avoid duplicate connection types for the same integration
                    if (!in_array($connectionTypeId, $usedConnectionTypes)) {
                        $usedConnectionTypes[] = $connectionTypeId;
                        
                        // Create connection with detailed inbound/outbound for both apps
                        DB::table('appintegration_connections')->insert([
                            'integration_id' => $integration->integration_id,
                            'connection_type_id' => $connectionTypeId,
                            'source_inbound' => fake()->sentence(8),
                            'source_outbound' => fake()->sentence(8),
                            'target_inbound' => fake()->sentence(8),
                            'target_outbound' => fake()->sentence(8),
                        ]);
                    }
                }
            }
        }

        // 5. Create App Integration Functions for module apps
        $this->command->info('Creating app integration functions...');
        $moduleApps = App::where('is_module', true)->get();
        $integrationIds = AppIntegration::pluck('integration_id')->toArray();
        
        // Function names that can be used for modules
        $functionNames = [
            'Authentication', 'Authorization', 'Data Processing', 'Report Generation',
            'File Transfer', 'Message Handling', 'Data Validation', 'Encryption',
            'Logging', 'Monitoring', 'Cache Management', 'Session Management',
            'Email Service', 'Notification Service', 'Backup Service', 'Sync Service'
        ];

        foreach ($moduleApps as $moduleApp) {
            // Each module app gets 2-4 functions
            $functionCount = fake()->numberBetween(2, 4);
            $usedFunctions = [];
            
            for ($i = 0; $i < $functionCount; $i++) {
                $functionName = fake()->randomElement($functionNames);
                
                // Avoid duplicate function names for the same app
                if (!in_array($functionName, $usedFunctions)) {
                    $usedFunctions[] = $functionName;
                    $integrationId = fake()->randomElement($integrationIds);
                    
                    // Check if this combination already exists
                    $exists = AppIntegrationFunction::where('app_id', $moduleApp->app_id)
                        ->where('integration_id', $integrationId)
                        ->where('function_name', $functionName)
                        ->exists();
                    
                    if (!$exists) {
                        AppIntegrationFunction::create([
                            'app_id' => $moduleApp->app_id,
                            'integration_id' => $integrationId,
                            'function_name' => $functionName
                        ]);
                    }
                }
            }
        }

        // 6. Create Contracts (12 contracts)
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

        // 7. Create App-Contract relationships (ensure every app has a chance to have contracts)
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

        // 8. Technology data will be handled by TechnologySeeder
        $this->command->info('Technology data will be seeded separately by TechnologySeeder...');

        $this->command->info('Application data seeding completed!');
    }
}
