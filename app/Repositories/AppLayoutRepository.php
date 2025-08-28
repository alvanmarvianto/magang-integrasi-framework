<?php

namespace App\Repositories;

use App\DTOs\AppLayoutDTO;
use App\Models\AppLayout;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use App\Repositories\CacheConfig;
use Illuminate\Support\Facades\Cache;

class AppLayoutRepository implements AppLayoutRepositoryInterface
{
    public function __construct(private readonly AppLayout $model) {}

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

    private function clearAppLayoutCaches(?int $appId = null): void
    {
        if ($appId) {
            Cache::forget(CacheConfig::buildKey('app_layout', 'app_id', $appId));
        }
    }
}
