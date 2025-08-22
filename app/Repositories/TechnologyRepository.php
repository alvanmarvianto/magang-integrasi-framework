<?php

namespace App\Repositories;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use App\Models\Technology;
use App\Models\AppTechnology;
use App\Models\App;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TechnologyRepository implements TechnologyRepositoryInterface
{    
    private const TECHNOLOGY_TYPE_MAPPINGS = [
        'vendors' => 'vendors',
        'operating_systems' => 'operating_systems',
        'databases' => 'databases',
        'programming_languages' => 'programming_languages',
        'frameworks' => 'frameworks',
        'middlewares' => 'middlewares',
        'third_parties' => 'third_parties',
        'platforms' => 'platforms',
    ];

    public function getEnumValues(string $technologyType): TechnologyEnumDTO
    {
        if (empty(trim($technologyType))) {
            throw new \InvalidArgumentException('Technology type cannot be empty');
        }

        // Map old table names to new type system
        $type = $this->mapToTechnologyType($technologyType);

        $cacheKey = CacheConfig::buildKey('technology', 'enum', $type);
        $cacheTTL = CacheConfig::getTTL('long');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function () use ($type, $technologyType) {
                try {
                    $technologies = Technology::where('type', $type)->pluck('name')->toArray();
                    return new TechnologyEnumDTO($technologyType, $technologies);
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed("technology enum values for {$technologyType}", $e->getMessage());
                }
            }
        );
    }

    public function getTechnologyComponentsForApp(int $appId, string $technologyType): Collection
    {
        if ($appId <= 0) {
            throw new \InvalidArgumentException('App ID must be a positive integer');
        }

        $type = $this->mapToTechnologyType($technologyType);
        
        $cacheKey = CacheConfig::buildKey('app', $appId, 'technology', $type);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($appId, $type) {
                try {
                    return AppTechnology::with('technology')
                        ->where('app_id', $appId)
                        ->whereHas('technology', function ($query) use ($type) {
                            $query->where('type', $type);
                        })
                        ->get()
                        ->map(function ($appTech) {
                            return new TechnologyComponentDTO(
                                id: $appTech->technology->id,
                                name: $appTech->technology->name,
                                type: $appTech->technology->type,
                                version: $appTech->version
                            );
                        });
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

        $type = $this->mapToTechnologyType($technologyType);

        try {
            DB::transaction(function () use ($appId, $type, $components) {
                // Delete existing components of this type for the app
                AppTechnology::where('app_id', $appId)
                    ->whereHas('technology', function ($query) use ($type) {
                        $query->where('type', $type);
                    })
                    ->delete();

                // Create new components
                foreach ($components as $component) {
                    $componentData = is_array($component) ? $component : $component->toArray();
                    
                    // Find or create the technology
                    $technology = Technology::firstOrCreate([
                        'type' => $type,
                        'name' => $componentData['name'],
                    ]);

                    // Create the app-technology relationship
                    AppTechnology::create([
                        'app_id' => $appId,
                        'tech_id' => $technology->id,
                        'version' => $componentData['version'] ?? null,
                    ]);
                }
            });

            $this->clearTechnologyCache($appId, $type);
        } catch (\Exception $e) {
            throw RepositoryException::createFailed("technology components for app {$appId}", $e->getMessage());
        }
    }

    public function deleteTechnologyComponentsForApp(int $appId, string $technologyType): bool
    {
        $type = $this->mapToTechnologyType($technologyType);

        $deleted = AppTechnology::where('app_id', $appId)
            ->whereHas('technology', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->delete();
        
        if ($deleted > 0) {
            $this->clearTechnologyCache($appId, $type);
        }

        return $deleted > 0;
    }

    public function getAppsUsingTechnology(string $technologyType, string $technologyName): Collection
    {
        $type = $this->mapToTechnologyType($technologyType);

        $cacheKey = CacheConfig::buildKey('technology', $type, $technologyName, 'apps');
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function () use ($type, $technologyName) {
                $technology = Technology::where('type', $type)
                    ->where('name', $technologyName)
                    ->first();

                if (!$technology) {
                    return collect();
                }

                return AppTechnology::where('tech_id', $technology->id)
                    ->with(['app.stream'])
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

                foreach (self::TECHNOLOGY_TYPE_MAPPINGS as $key => $type) {
                    $techCount = Technology::where('type', $type)->count();
                    $appTechCount = AppTechnology::whereHas('technology', function ($query) use ($type) {
                        $query->where('type', $type);
                    })->count();
                    
                    $uniqueApps = AppTechnology::whereHas('technology', function ($query) use ($type) {
                        $query->where('type', $type);
                    })->distinct('app_id')->count('app_id');

                    $mostUsed = AppTechnology::select('tech_id', DB::raw('count(*) as usage_count'))
                        ->whereHas('technology', function ($query) use ($type) {
                            $query->where('type', $type);
                        })
                        ->with('technology')
                        ->groupBy('tech_id')
                        ->orderByDesc('usage_count')
                        ->limit(5)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'name' => $item->technology->name,
                                'usage_count' => $item->usage_count,
                            ];
                        })
                        ->toArray();
                    
                    $statistics[$key] = [
                        'total_components' => $appTechCount,
                        'unique_technologies' => $techCount,
                        'apps_using' => $uniqueApps,
                        'most_used' => $mostUsed,
                    ];
                }

                return $statistics;
            }
        );
    }

    public function searchTechnologiesByName(string $searchTerm): Collection
    {
        $appTechnologies = AppTechnology::with(['technology', 'app'])
            ->whereHas('technology', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            })
            ->get();

        return $appTechnologies->map(function ($appTech) {
            return [
                'type' => $appTech->technology->type,
                'name' => $appTech->technology->name,
                'version' => $appTech->version,
                'app' => $appTech->app ? [
                    'app_id' => $appTech->app->app_id,
                    'app_name' => $appTech->app->app_name,
                ] : null,
            ];
        });
    }

    private function mapToTechnologyType(string $input): string
    {
        // Handle old table names
        if (str_starts_with($input, 'technology_')) {
            $input = str_replace('technology_', '', $input);
        }

        // Map old keys to new types
        return self::TECHNOLOGY_TYPE_MAPPINGS[$input] ?? $input;
    }

    private function clearTechnologyCache(int $appId, string $type): void
    {
        // Clear app-specific technology cache
        $appCacheKey = CacheConfig::buildKey('app', $appId, 'technology', $type);
        Cache::forget($appCacheKey);
        
        // Clear statistics cache
        $statisticsCacheKey = CacheConfig::buildKey('technology', 'statistics');
        Cache::forget($statisticsCacheKey);
        
        // Clear enum cache
        $enumCacheKey = CacheConfig::buildKey('technology', 'enum', $type);
        Cache::forget($enumCacheKey);
    }
}