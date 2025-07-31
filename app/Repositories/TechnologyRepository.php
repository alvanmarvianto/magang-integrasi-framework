<?php

namespace App\Repositories;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TechnologyRepository implements TechnologyRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    
    private const TECHNOLOGY_TYPE_MAPPINGS = [
        'vendors' => [
            'table' => 'technology_vendors',
            'model' => \App\Models\Vendor::class,
            'primary_key' => 'vendor_id',
        ],
        'operating_systems' => [
            'table' => 'technology_operating_systems',
            'model' => \App\Models\OperatingSystem::class,
            'primary_key' => 'os_id',
        ],
        'databases' => [
            'table' => 'technology_databases',
            'model' => \App\Models\Database::class,
            'primary_key' => 'database_id',
        ],
        'programming_languages' => [
            'table' => 'technology_programming_languages',
            'model' => \App\Models\ProgrammingLanguage::class,
            'primary_key' => 'language_id',
        ],
        'frameworks' => [
            'table' => 'technology_frameworks',
            'model' => \App\Models\Framework::class,
            'primary_key' => 'framework_id',
        ],
        'middlewares' => [
            'table' => 'technology_middlewares',
            'model' => \App\Models\Middleware::class,
            'primary_key' => 'middleware_id',
        ],
        'third_parties' => [
            'table' => 'technology_third_parties',
            'model' => \App\Models\ThirdParty::class,
            'primary_key' => 'third_party_id',
        ],
        'platforms' => [
            'table' => 'technology_platforms',
            'model' => \App\Models\Platform::class,
            'primary_key' => 'platform_id',
        ],
    ];

    public function getEnumValues(string $tableName): TechnologyEnumDTO
    {
        return Cache::remember(
            "technology.enum.{$tableName}",
            self::CACHE_TTL * 24, // Cache for 24 hours since enum values rarely change
            function () use ($tableName) {
                $type = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_NAME', $tableName)
                    ->where('COLUMN_NAME', 'name')
                    ->value('COLUMN_TYPE');

                if (!$type || !str_starts_with($type, 'enum')) {
                    return new TechnologyEnumDTO($tableName, []);
                }

                preg_match('/^enum\((.*)\)$/', $type, $matches);
                $values = str_getcsv($matches[1], ',', "'");

                $cleanValues = array_map(fn($value) => trim($value), $values);

                return new TechnologyEnumDTO($tableName, $cleanValues);
            }
        );
    }

    public function getTechnologyComponentsForApp(int $appId, string $technologyType): Collection
    {
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        
        return Cache::remember(
            "app.{$appId}.technology.{$technologyType}",
            self::CACHE_TTL,
            fn() => $mapping['model']::where('app_id', $appId)->get()
        );
    }

    public function saveTechnologyComponentsForApp(int $appId, string $technologyType, array $components): void
    {
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        $modelClass = $mapping['model'];

        DB::transaction(function () use ($appId, $technologyType, $components, $modelClass) {
            // Delete existing components
            $this->deleteTechnologyComponentsForApp($appId, $technologyType);

            // Create new components
            foreach ($components as $component) {
                $componentData = is_array($component) ? $component : $component->toArray();
                
                $modelClass::create([
                    'app_id' => $appId,
                    'name' => $componentData['name'],
                    'version' => $componentData['version'] ?? null,
                ]);
            }
        });

        $this->clearTechnologyCache($appId, $technologyType);
    }

    public function deleteTechnologyComponentsForApp(int $appId, string $technologyType): bool
    {
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        $modelClass = $mapping['model'];

        $deleted = $modelClass::where('app_id', $appId)->delete();
        
        if ($deleted) {
            $this->clearTechnologyCache($appId, $technologyType);
        }

        return $deleted > 0;
    }

    public function getAppsUsingTechnology(string $technologyType, string $technologyName): Collection
    {
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        $modelClass = $mapping['model'];

        return Cache::remember(
            "technology.{$technologyType}.{$technologyName}.apps",
            self::CACHE_TTL,
            function () use ($modelClass, $technologyName) {
                return $modelClass::where('name', $technologyName)
                    ->with('app.stream')
                    ->get()
                    ->pluck('app')
                    ->filter();
            }
        );
    }

    public function getTechnologyTypeMappings(): array
    {
        return self::TECHNOLOGY_TYPE_MAPPINGS;
    }

    public function bulkUpdateTechnologyComponents(int $appId, array $technologyData): void
    {
        DB::transaction(function () use ($appId, $technologyData) {
            foreach ($technologyData as $technologyType => $components) {
                if (!empty($components)) {
                    $this->saveTechnologyComponentsForApp($appId, $technologyType, $components);
                } else {
                    $this->deleteTechnologyComponentsForApp($appId, $technologyType);
                }
            }
        });
    }

    public function getTechnologyStatistics(): array
    {
        return Cache::remember(
            'technology.statistics',
            self::CACHE_TTL,
            function () {
                $statistics = [];

                foreach (self::TECHNOLOGY_TYPE_MAPPINGS as $type => $mapping) {
                    $modelClass = $mapping['model'];
                    
                    $statistics[$type] = [
                        'total_components' => $modelClass::count(),
                        'unique_technologies' => $modelClass::distinct('name')->count('name'),
                        'apps_using' => $modelClass::distinct('app_id')->count('app_id'),
                        'most_used' => $modelClass::select('name', DB::raw('count(*) as usage_count'))
                            ->groupBy('name')
                            ->orderByDesc('usage_count')
                            ->limit(5)
                            ->get()
                            ->toArray(),
                    ];
                }

                return $statistics;
            }
        );
    }

    public function searchTechnologiesByName(string $searchTerm): Collection
    {
        $results = collect();

        foreach (self::TECHNOLOGY_TYPE_MAPPINGS as $type => $mapping) {
            $modelClass = $mapping['model'];
            
            $typeResults = $modelClass::where('name', 'like', "%{$searchTerm}%")
                ->with('app')
                ->get()
                ->map(function ($item) use ($type) {
                    return [
                        'type' => $type,
                        'name' => $item->name,
                        'version' => $item->version,
                        'app' => $item->app ? [
                            'app_id' => $item->app->app_id,
                            'app_name' => $item->app->app_name,
                        ] : null,
                    ];
                });

            $results = $results->merge($typeResults);
        }

        return $results;
    }

    private function getTechnologyTypeMapping(string $technologyType): array
    {
        if (!isset(self::TECHNOLOGY_TYPE_MAPPINGS[$technologyType])) {
            throw new \InvalidArgumentException("Invalid technology type: {$technologyType}");
        }

        return self::TECHNOLOGY_TYPE_MAPPINGS[$technologyType];
    }

    private function clearTechnologyCache(int $appId, string $technologyType): void
    {
        Cache::forget("app.{$appId}.technology.{$technologyType}");
        Cache::forget('technology.statistics');
        
        // Clear enum cache if needed
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        Cache::forget("technology.enum.{$mapping['table']}");
    }
}