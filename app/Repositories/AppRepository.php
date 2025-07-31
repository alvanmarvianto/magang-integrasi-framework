<?php

namespace App\Repositories;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppRepository implements AppRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    protected TechnologyRepositoryInterface $technologyRepository;

    public function __construct(TechnologyRepositoryInterface $technologyRepository)
    {
        $this->technologyRepository = $technologyRepository;
    }

    public function getPaginatedApps(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator {
        $query = App::with('stream');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('app_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('stream', function ($streamQuery) use ($search) {
                      $streamQuery->where('stream_name', 'like', "%{$search}%");
                  });
            });
        }

        $this->applySorting($query, $sortBy, $sortDesc);

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id): ?App
    {
        return Cache::remember(
            "app.{$id}.with_relations",
            self::CACHE_TTL,
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

    public function findAsDTO(int $id): ?AppDTO
    {
        $app = $this->findWithRelations($id);
        return $app ? AppDTO::fromModel($app) : null;
    }

    public function createWithTechnology(AppDTO $appData): App
    {
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

            $this->clearAppCache($app->app_id);

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
    }

    public function updateWithTechnology(App $app, AppDTO $appData): bool
    {
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
                $this->clearAppCache($app->app_id);
            }

            return $updated;
        });
    }

    public function delete(App $app): bool
    {
        $appId = $app->app_id;
        
        $deleted = $app->delete();

        if ($deleted) {
            $this->clearAppCache($appId);
        }

        return $deleted;
    }

    public function getAppsByStreamId(int $streamId): Collection
    {
        return Cache::remember(
            "stream.{$streamId}.apps",
            self::CACHE_TTL,
            fn() => App::where('stream_id', $streamId)
                ->with('stream')
                ->orderBy('app_name')
                ->get()
        );
    }

    public function getAppsByStreamName(string $streamName): Collection
    {
        return Cache::remember(
            "stream.name.{$streamName}.apps",
            self::CACHE_TTL,
            fn() => App::whereHas('stream', function ($query) use ($streamName) {
                $query->where('stream_name', $streamName);
            })->with('stream')->orderBy('app_name')->get()
        );
    }

    public function getAppsByIds(array $appIds): Collection
    {
        return App::whereIn('app_id', $appIds)
            ->with('stream')
            ->orderBy('app_name')
            ->get();
    }

    public function searchAppsByName(string $searchTerm): Collection
    {
        return App::where('app_name', 'like', "%{$searchTerm}%")
            ->with('stream')
            ->orderBy('app_name')
            ->limit(20)
            ->get();
    }

    public function getAppsWithIntegrationCounts(): Collection
    {
        return Cache::remember(
            'apps.with_integration_counts',
            self::CACHE_TTL,
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
            }
        );
    }

    public function existsByName(string $appName): bool
    {
        return Cache::remember(
            "app.exists.{$appName}",
            self::CACHE_TTL,
            fn() => App::where('app_name', $appName)->exists()
        );
    }

    public function getAppStatistics(): array
    {
        return Cache::remember(
            'apps.statistics',
            self::CACHE_TTL,
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
            }
        );
    }

    public function bulkUpdateApps(array $appData): bool
    {
        return DB::transaction(function () use ($appData) {
            $updated = 0;
            
            foreach ($appData as $data) {
                if (isset($data['app_id'])) {
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
    }

    private function applySorting($query, string $sortBy, bool $sortDesc): void
    {
        $sortDirection = $sortDesc ? 'desc' : 'asc';
        
        switch ($sortBy) {
            case 'stream':
            case 'stream_name':
                $query->leftJoin('streams', 'apps.stream_id', '=', 'streams.stream_id')
                      ->orderBy('streams.stream_name', $sortDirection)
                      ->select('apps.*');
                break;
            case 'app_name':
            case 'app_type':
            case 'stratification':
            case 'description':
                $query->orderBy($sortBy, $sortDirection);
                break;
            default:
                $query->orderBy('app_name', $sortDirection);
                break;
        }
    }

    private function clearAppCache(int $appId): void
    {
        Cache::forget("app.{$appId}.with_relations");
        Cache::forget('apps.with_integration_counts');
        Cache::forget('apps.statistics');
        
        // Clear stream-related caches
        $app = App::find($appId);
        if ($app && $app->stream_id) {
            Cache::forget("stream.{$app->stream_id}.apps");
            if ($app->stream) {
                Cache::forget("stream.name.{$app->stream->stream_name}.apps");
            }
        }
    }
} 