<?php

namespace App\Repositories;

use App\DTOs\AppLayoutDTO;
use App\Models\AppLayout;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use App\Repositories\CacheConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppLayoutRepository implements AppLayoutRepositoryInterface
{
    public function __construct(private readonly AppLayout $model) {}

    public function getAll(): Collection
    {
        $cacheKey = CacheConfig::buildKey('app_layout', 'all');
        $cacheTTL = CacheConfig::getTTL('default');
        return Cache::remember($cacheKey, $cacheTTL, function () {
            return $this->model->all()->map(fn($m) => AppLayoutDTO::fromModel($m));
        });
    }

    public function findById(int $id): ?AppLayoutDTO
    {
        if ($id <= 0) {
            return null;
        }
        $cacheKey = CacheConfig::buildKey('app_layout', 'id', $id);
        $cacheTTL = CacheConfig::getTTL('default');
        return Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
            $layout = $this->model->find($id);
            return $layout ? AppLayoutDTO::fromModel($layout) : null;
        });
    }

    public function findByAppId(int $appId): ?AppLayoutDTO
    {
        if ($appId <= 0) {
            return null;
        }
        $cacheKey = CacheConfig::buildKey('app_layout', 'app_id', $appId);
        $cacheTTL = CacheConfig::getTTL('default');
        return Cache::remember($cacheKey, $cacheTTL, function () use ($appId) {
            $layout = $this->model->where('app_id', $appId)->first();
            return $layout ? AppLayoutDTO::fromModel($layout) : null;
        });
    }

    public function create(AppLayoutDTO $dto): AppLayoutDTO
    {
        $layout = $this->model->create($dto->toArray());
        $this->clearAppLayoutCaches($layout->app_id ?? null);
        return AppLayoutDTO::fromModel($layout);
    }

    public function update(int $id, AppLayoutDTO $dto): ?AppLayoutDTO
    {
        $layout = $this->model->find($id);
        if (!$layout) { return null; }
        $layout->update($dto->toArray());
        $this->clearAppLayoutCaches($layout->app_id ?? null);
        return AppLayoutDTO::fromModel($layout);
    }

    public function saveLayoutByAppId(int $appId, array $nodesLayout, array $edgesLayout, array $appConfig): AppLayoutDTO
    {
        $layout = $this->model->updateOrCreate(
            ['app_id' => $appId],
            [
                'nodes_layout' => $nodesLayout,
                'edges_layout' => $edgesLayout,
                'app_config' => $appConfig,
            ]
        );
        $this->clearAppLayoutCaches($appId);
        return AppLayoutDTO::fromModel($layout);
    }

    public function delete(int $id): bool
    {
        $layout = $this->model->find($id);
        if (!$layout) { return false; }
        $appId = $layout->app_id;
        $deleted = (bool) $layout->delete();
        if ($deleted) { $this->clearAppLayoutCaches($appId); }
        return $deleted;
    }

    public function getStatistics(): array
    {
        $count = $this->model->count();
        return [
            'total_layouts' => $count,
        ];
    }

    private function clearAppLayoutCaches(?int $appId = null): void
    {
        Cache::forget(CacheConfig::buildKey('app_layout', 'all'));
        if ($appId) {
            Cache::forget(CacheConfig::buildKey('app_layout', 'app_id', $appId));
        }
    }

    /**
     * Public method to clear caches for a specific app
     */
    public function clearCaches(?int $appId = null): void
    {
        $this->clearAppLayoutCaches($appId);
    }
}
