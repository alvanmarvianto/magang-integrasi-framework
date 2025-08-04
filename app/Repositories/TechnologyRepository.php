<?php

namespace App\Repositories;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TechnologyRepository implements TechnologyRepositoryInterface
{    
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
        if (empty(trim($tableName))) {
            throw new \InvalidArgumentException('Table name cannot be empty');
        }

        $cacheKey = CacheConfig::buildKey('technology', 'enum', $tableName);
        $cacheTTL = CacheConfig::getTTL('long'); // Cache for longer since enum values rarely change
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function () use ($tableName) {
                try {
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
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed("technology enum values for {$tableName}", $e->getMessage());
                }
            }
        );
    }

    public function getTechnologyComponentsForApp(int $appId, string $technologyType): Collection
    {
        if ($appId <= 0) {
            throw new \InvalidArgumentException('App ID must be a positive integer');
        }

        $mapping = $this->getTechnologyTypeMapping($technologyType);
        
        $cacheKey = CacheConfig::buildKey('app', $appId, 'technology', $technologyType);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($appId, $mapping) {
                try {
                    return $mapping['model']::where('app_id', $appId)->get();
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed("technology components for app {$appId}", $e->getMessage());
                }
            }
        );
    }

    public function saveTechnologyComponentsForApp(int $appId, string $technologyType, array $components): void
    {
        if ($appId <= 0) {
            throw new \InvalidArgumentException('App ID must be a positive integer');
        }

        $mapping = $this->getTechnologyTypeMapping($technologyType);
        $modelClass = $mapping['model'];

        try {
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
        } catch (\Exception $e) {
            throw RepositoryException::createFailed("technology components for app {$appId}", $e->getMessage());
        }
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

        $cacheKey = CacheConfig::buildKey('technology', $technologyType, $technologyName, 'apps');
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
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
        $cacheKey = CacheConfig::buildKey('technology', 'statistics');
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
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
        $mapping = $this->getTechnologyTypeMapping($technologyType);
        
        // Clear app-specific technology cache
        $appCacheKey = CacheConfig::buildKey('app', $appId, 'technology', $technologyType);
        Cache::forget($appCacheKey);
        
        // Clear statistics cache
        $statisticsCacheKey = CacheConfig::buildKey('technology', 'statistics');
        Cache::forget($statisticsCacheKey);
        
        // Clear enum cache if needed
        $enumCacheKey = CacheConfig::buildKey('technology', 'enum', $mapping['table']);
        Cache::forget($enumCacheKey);
    }
}