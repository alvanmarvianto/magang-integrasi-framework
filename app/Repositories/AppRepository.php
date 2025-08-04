<?php

namespace App\Repositories;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Repositories\BaseRepository;
use App\Repositories\CacheConfig;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AppRepository extends BaseRepository implements AppRepositoryInterface
{
    protected TechnologyRepositoryInterface $technologyRepository;

    public function __construct(TechnologyRepositoryInterface $technologyRepository)
    {
        $this->technologyRepository = $technologyRepository;
    }

    /**
     * Get allowed sort fields for apps
     */
    protected function getAllowedSortFields(): array
    {
        return [
            'app_name',
            'app_type', 
            'stratification',
            'description',
            'created_at',
            'updated_at',
            'stream_name',
            'stream'
        ];
    }

    /**
     * Get default sort field for apps
     */
    protected function getDefaultSortField(): string
    {
        return 'app_name';
    }

    /**
     * Get entity name for cache operations
     */
    protected function getEntityName(): string
    {
        return 'app';
    }

    /**
     * Apply custom sorting logic for apps
     */
    protected function applySortingLogic($query, string $sortBy, string $direction): void
    {
        switch ($sortBy) {
            case 'stream':
            case 'stream_name':
                $query->leftJoin('streams', 'apps.stream_id', '=', 'streams.stream_id')
                      ->orderBy('streams.stream_name', $direction)
                      ->select('apps.*');
                break;
            default:
                $query->orderBy($sortBy, $direction);
                break;
        }
    }

    public function getPaginatedApps(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator {
        // Validate parameters
        $this->validatePaginationParams($perPage);
        
        if ($search !== null) {
            $this->validateNotEmpty($search, 'search');
        }

        try {
            $query = App::with([
                'stream',
                'vendors',
                'operatingSystems',
                'databases',
                'programmingLanguages',
                'frameworks',
                'middlewares',
                'thirdParties',
                'platforms'
            ]);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('stream', function ($streamQuery) use ($search) {
                          $streamQuery->where('stream_name', 'like', "%{$search}%");
                      });
                });
            }

            $this->applySorting($query, $sortBy, $sortDesc ? 'desc' : 'asc');

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to get paginated apps', [
                'search' => $search,
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDesc' => $sortDesc,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::createFailed('app pagination', $e->getMessage());
        }
    }

    public function findWithRelations(int $id): ?App
    {
        $this->validateId($id);
        
        $cacheKey = $this->buildCacheKey('app', 'with_relations', $id);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => App::with([
                'stream',
                'vendors',
                'operatingSystems',
                'databases',
                'programmingLanguages',
                'frameworks',
                'middlewares',
                'thirdParties',
                'platforms',
            ])->find($id)
        );
    }

    /**
     * Find app with relations bypassing cache (for immediate fresh data)
     */
    public function findWithRelationsFresh(int $id): ?App
    {
        $this->validateId($id);
        
        return App::with([
            'stream',
            'vendors',
            'operatingSystems',
            'databases',
            'programmingLanguages',
            'frameworks',
            'middlewares',
            'thirdParties',
            'platforms',
        ])->find($id);
    }

    public function findAsDTO(int $id): ?AppDTO
    {
        $this->validateId($id);
        
        try {
            $app = $this->findWithRelations($id);
            return $app ? AppDTO::fromModel($app) : null;
        } catch (\Exception $e) {
            Log::error('Failed to find app as DTO', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::entityNotFound('app', $id);
        }
    }

    /**
     * Find app by ID and return as DTO with fresh data (bypassing cache)
     */
    public function findAsDTOFresh(int $id): ?AppDTO
    {
        $this->validateId($id);
        
        try {
            $app = $this->findWithRelationsFresh($id);
            return $app ? AppDTO::fromModel($app) : null;
        } catch (\Exception $e) {
            Log::error('Failed to find app as DTO (fresh)', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::entityNotFound('app', $id);
        }
    }

    public function createWithTechnology(AppDTO $appData): App
    {
        // Validate required fields
        $this->validateNotEmpty($appData->appName, 'appName');
        $this->validateId($appData->streamId);

        try {
            return DB::transaction(function () use ($appData) {
                $app = App::create([
                    'app_name' => $appData->appName,
                    'description' => $appData->description,
                    'stream_id' => $appData->streamId,
                    'app_type' => $appData->appType,
                    'stratification' => $appData->stratification,
                ]);

                if (!empty($appData->technologyComponents)) {
                    $this->technologyRepository->bulkUpdateTechnologyComponents(
                        $app->app_id,
                        $appData->technologyComponents
                    );
                }

                $this->clearAllAppCaches($app->app_id);

                return $app->load([
                    'stream',
                    'vendors',
                    'operatingSystems',
                    'databases',
                    'programmingLanguages',
                    'frameworks',
                    'middlewares',
                    'thirdParties',
                    'platforms',
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to create app with technology', [
                'appData' => $appData->toArray(),
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::createFailed('app', $e->getMessage());
        }
    }

    public function updateWithTechnology(App $app, AppDTO $appData): bool
    {
        // Validate required fields
        $this->validateNotEmpty($appData->appName, 'appName');
        $this->validateId($appData->streamId);

        try {
            return DB::transaction(function () use ($app, $appData) {
                $updated = $app->update([
                    'app_name' => $appData->appName,
                    'description' => $appData->description,
                    'stream_id' => $appData->streamId,
                    'app_type' => $appData->appType,
                    'stratification' => $appData->stratification,
                ]);

                if ($updated && !empty($appData->technologyComponents)) {
                    $this->technologyRepository->bulkUpdateTechnologyComponents(
                        $app->app_id,
                        $appData->technologyComponents
                    );
                }

                if ($updated) {
                    // Clear all app-related caches comprehensively
                    $this->clearAllAppCaches($app->app_id);
                }

                return $updated;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update app with technology', [
                'appId' => $app->app_id,
                'appData' => $appData->toArray(),
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::updateFailed('app', $app->app_id, $e->getMessage());
        }
    }

    /**
     * Comprehensive cache clearing for app updates
     */
    private function clearAllAppCaches(int $appId): void
    {
        try {
            // Clear the basic entity cache
            $this->clearEntityCache($this->getEntityName(), $appId);
            
            // Clear specific app-related cache keys
            $keysToForget = [
                "app.{$appId}",
                "app.{$appId}.with_relations", 
                "app.{$appId}.with_apps",
                "app.exists.{$appId}",
                // Clear general app caches that might include this app
                "apps.all",
                "apps.statistics",
                "apps.with_integration_counts",
                // Clear stream-related caches since apps belong to streams
                "stream.apps",
                "stream.name_apps"
            ];
            
            foreach ($keysToForget as $key) {
                Cache::forget($key);
            }
            
            // Clear any search result caches (limited scope)
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("apps.search.{$i}");
            }
            
            Log::info("Cleared comprehensive app caches for app ID: {$appId}");
            
        } catch (\Exception $e) {
            Log::warning("Failed to clear comprehensive app caches for app {$appId}: " . $e->getMessage());
        }
    }

    public function delete(App $app): bool
    {
        $appId = $app->app_id;
        
        try {
            $deleted = $app->delete();

            if ($deleted) {
                $this->clearAllAppCaches($appId);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete app', [
                'appId' => $appId,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::deleteFailed('app', $appId, $e->getMessage());
        }
    }

    public function getAppsByStreamId(int $streamId): Collection
    {
        $this->validateId($streamId);
        
        $cacheKey = $this->buildCacheKey('stream', 'apps', $streamId);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => App::where('stream_id', $streamId)
                ->with('stream')
                ->orderBy('app_name')
                ->get()
        );
    }

    public function getAppsByStreamName(string $streamName): Collection
    {
        $this->validateNotEmpty($streamName, 'streamName');
        
        $cacheKey = $this->buildCacheKey('stream', 'name_apps', $streamName);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => App::whereHas('stream', function ($query) use ($streamName) {
                $query->where('stream_name', $streamName);
            })->with('stream')->orderBy('app_name')->get()
        );
    }

    public function getAppsByIds(array $appIds): Collection
    {
        if (empty($appIds)) {
            return new Collection();
        }

        // Validate all IDs are positive integers
        foreach ($appIds as $id) {
            $this->validateId($id);
        }

        try {
            return App::whereIn('app_id', $appIds)
                ->with('stream')
                ->orderBy('app_name')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get apps by IDs', [
                'appIds' => $appIds,
                'exception' => $e->getMessage()
            ]);
            
            throw new RepositoryException('Failed to get apps by IDs: ' . $e->getMessage());
        }
    }

    public function searchAppsByName(string $searchTerm): Collection
    {
        $this->validateNotEmpty($searchTerm, 'searchTerm');

        if (strlen($searchTerm) < 2) {
            throw new InvalidArgumentException('Search term must be at least 2 characters');
        }

        try {
            $cacheKey = $this->buildCacheKey('apps', 'search', $searchTerm);
            
            return $this->handleCacheOperation(
                $cacheKey,
                fn() => App::where('app_name', 'like', "%{$searchTerm}%")
                    ->with('stream')
                    ->orderBy('app_name')
                    ->limit(20)
                    ->get(),
                CacheConfig::getTTL('search')
            );
        } catch (\Exception $e) {
            Log::error('Failed to search apps by name', [
                'searchTerm' => $searchTerm,
                'exception' => $e->getMessage()
            ]);
            
            throw new RepositoryException('Failed to search apps: ' . $e->getMessage());
        }
    }

    public function getAppsWithIntegrationCounts(): Collection
    {
        $cacheKey = $this->buildCacheKey('apps', 'with_integration_counts');
        
        return $this->handleCacheOperation(
            $cacheKey,
            function () {
                return App::withCount([
                    'integrations',
                    'integratedBy',
                ])
                ->with('stream')
                ->get()
                ->map(function ($app) {
                    $app->total_integrations = $app->integrations_count + $app->integrated_by_count;
                    return $app;
                });
            },
            CacheConfig::getTTL('statistics')
        );
    }

    public function existsByName(string $appName): bool
    {
        $this->validateNotEmpty($appName, 'appName');
        
        $cacheKey = $this->buildCacheKey('app', 'exists', $appName);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => App::where('app_name', $appName)->exists()
        );
    }

    public function getAppStatistics(): array
    {
        $cacheKey = $this->buildCacheKey('apps', 'statistics');
        
        return $this->handleCacheOperation(
            $cacheKey,
            function () {
                $apps = App::with('stream')->get();
                
                return [
                    'total_apps' => $apps->count(),
                    'apps_by_type' => $apps->groupBy('app_type')->map(fn($group) => $group->count())->toArray(),
                    'apps_by_stratification' => $apps->groupBy('stratification')->map(fn($group) => $group->count())->toArray(),
                    'apps_by_stream' => $apps->groupBy('stream.stream_name')->map(fn($group) => $group->count())->toArray(),
                    'apps_with_description' => $apps->whereNotNull('description')->count(),
                    'apps_without_description' => $apps->whereNull('description')->count(),
                ];
            },
            CacheConfig::getTTL('statistics')
        );
    }

    public function bulkUpdateApps(array $appData): bool
    {
        if (empty($appData)) {
            return false;
        }

        try {
            return DB::transaction(function () use ($appData) {
                $updated = 0;
                
                foreach ($appData as $data) {
                    if (isset($data['app_id'])) {
                        $this->validateId($data['app_id']);
                        
                        $app = App::find($data['app_id']);
                        if ($app) {
                            $appDTO = AppDTO::fromArray($data);
                            if ($this->updateWithTechnology($app, $appDTO)) {
                                $updated++;
                            }
                        }
                    }
                }

                return $updated > 0;
            });
        } catch (\Exception $e) {
            Log::error('Failed to bulk update apps', [
                'appDataCount' => count($appData),
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::updateFailed('bulk apps', 'multiple', $e->getMessage());
        }
    }
} 