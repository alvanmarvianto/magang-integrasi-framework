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
        $this->validatePaginationParams($perPage);
        
        if ($search !== null) {
            $this->validateNotEmpty($search, 'search');
        }

        try {
            $query = App::with([
                'stream',
                'technologies',
                'appTechnologies.technology'
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
                'technologies',
                'appTechnologies.technology',
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
            'technologies',
            'appTechnologies.technology',
        ])->find($id);
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
                    'is_module' => $appData->isModule ?? false,
                ]);

                $this->technologyRepository->bulkUpdateTechnologyComponents(
                    $app->app_id,
                    $appData->technologyComponents
                );

                $this->clearAllAppCaches($app->app_id);

                return $app->load([
                    'stream',
                    'technologies',
                    'appTechnologies.technology',
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
                    'is_module' => $appData->isModule ?? false,
                ]);

                if ($updated) {
                    $this->technologyRepository->bulkUpdateTechnologyComponents(
                        $app->app_id,
                        $appData->technologyComponents
                    );
                }

                if ($updated) {
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
            $this->clearEntityCache($this->getEntityName(), $appId);
            
            $keysToForget = [
                "app.{$appId}",
                "app.{$appId}.with_relations", 
                "app.{$appId}.with_apps",
                "app.exists.{$appId}",
                "apps.all",
                "apps.statistics",
                "apps.with_integration_counts",
                "stream.apps",
                "stream.name_apps"
            ];
            
            foreach ($keysToForget as $key) {
                Cache::forget($key);
            }
            
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("apps.search.{$i}");
            }
            
            
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

    public function getIntegrationFunctionsGrouped(int $appId): array
    {
        $this->validateId($appId);

        try {
            $rows = DB::table('appintegration_functions')
                ->where('app_id', $appId)
                ->select('function_name', 'integration_id')
                ->orderBy('function_name')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            return $rows
                ->groupBy('function_name')
                ->map(function ($items, $fname) {
                    return [
                        'function_name' => $fname,
                        'integration_ids' => $items->pluck('integration_id')->unique()->values()->all(),
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error('Failed to get integration functions grouped', [
                'appId' => $appId,
                'exception' => $e->getMessage(),
            ]);
            throw RepositoryException::createFailed('integration functions fetch', $e->getMessage());
        }
    }

    public function replaceIntegrationFunctions(int $appId, array $functions): void
    {
        $this->validateId($appId);

        DB::transaction(function () use ($appId, $functions) {
            DB::table('appintegration_functions')->where('app_id', $appId)->delete();

            $rows = [];
            foreach ($functions as $f) {
                $name = trim((string)($f['function_name'] ?? ''));
                if ($name === '') continue;

                $ids = [];
                if (isset($f['integration_ids']) && is_array($f['integration_ids'])) {
                    $ids = array_values(array_unique(array_map('intval', $f['integration_ids'])));
                } elseif (!empty($f['integration_id'])) {
                    $ids = [intval($f['integration_id'])];
                }

                foreach ($ids as $integrationId) {
                    if (!$integrationId) continue;
                    $rows[] = [
                        'app_id' => $appId,
                        'integration_id' => $integrationId,
                        'function_name' => $name,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('appintegration_functions')->insert($rows);
            }
        });
    }
} 