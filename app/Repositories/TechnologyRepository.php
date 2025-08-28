<?php

namespace App\Repositories;

use App\DTOs\TechnologyEnumDTO;
use App\Models\Technology;
use App\Models\AppTechnology;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
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

    public function saveTechnologyComponentsForApp(int $appId, string $technologyType, array $components): void
    {
        if ($appId <= 0) {
            throw new \InvalidArgumentException('App ID must be a positive integer');
        }

        $type = $this->mapToTechnologyType($technologyType);

        try {
            DB::transaction(function () use ($appId, $type, $components) {
                AppTechnology::where('app_id', $appId)
                    ->whereHas('technology', function ($query) use ($type) {
                        $query->where('type', $type);
                    })
                    ->delete();

                foreach ($components as $component) {
                    $componentData = is_array($component) ? $component : $component->toArray();
                    
                    $technology = Technology::firstOrCreate([
                        'type' => $type,
                        'name' => $componentData['name'],
                    ]);

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

    private function mapToTechnologyType(string $input): string
    {
        if (str_starts_with($input, 'technology_')) {
            $input = str_replace('technology_', '', $input);
        }

        return self::TECHNOLOGY_TYPE_MAPPINGS[$input] ?? $input;
    }

    private function clearTechnologyCache(int $appId, string $type): void
    {
        $appCacheKey = CacheConfig::buildKey('app', $appId, 'technology', $type);
        Cache::forget($appCacheKey);
        
        $statisticsCacheKey = CacheConfig::buildKey('technology', 'statistics');
        Cache::forget($statisticsCacheKey);
        
        $enumCacheKey = CacheConfig::buildKey('technology', 'enum', $type);
        Cache::forget($enumCacheKey);
    }
}