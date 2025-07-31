<?php

namespace App\Repositories;

use App\Models\App;
use App\Models\AppIntegration;
use App\DTOs\IntegrationDTO;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class IntegrationRepository implements IntegrationRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function getPaginatedIntegrations(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'source_app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator {
        $query = AppIntegration::with(['connectionType', 'sourceApp', 'targetApp']);

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('sourceApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                })->orWhereHas('targetApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                })->orWhereHas('connectionType', function ($q) use ($search) {
                    $q->where('type_name', 'like', "%{$search}%");
                });
            });
        }

        $this->applySorting($query, $sortBy, $sortDesc);

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $integrationId): ?AppIntegration
    {
        return Cache::remember(
            "integration.{$integrationId}.with_relations",
            self::CACHE_TTL,
            fn() => AppIntegration::with(['connectionType', 'sourceApp.stream', 'targetApp.stream'])
                ->find($integrationId)
        );
    }

    public function create(IntegrationDTO $integrationData): AppIntegration
    {
        $integration = AppIntegration::create([
            'source_app_id' => $integrationData->sourceAppId,
            'target_app_id' => $integrationData->targetAppId,
            'connection_type_id' => $integrationData->connectionTypeId,
            'inbound' => $integrationData->inbound,
            'outbound' => $integrationData->outbound,
            'connection_endpoint' => $integrationData->connectionEndpoint,
            'direction' => $integrationData->direction,
        ]);

        $this->clearIntegrationCache($integrationData->sourceAppId, $integrationData->targetAppId);

        return $integration->load(['connectionType', 'sourceApp', 'targetApp']);
    }

    public function update(AppIntegration $integration, IntegrationDTO $integrationData): bool
    {
        $oldSourceAppId = $integration->source_app_id;
        $oldTargetAppId = $integration->target_app_id;

        $updated = $integration->update([
            'source_app_id' => $integrationData->sourceAppId,
            'target_app_id' => $integrationData->targetAppId,
            'connection_type_id' => $integrationData->connectionTypeId,
            'inbound' => $integrationData->inbound,
            'outbound' => $integrationData->outbound,
            'connection_endpoint' => $integrationData->connectionEndpoint,
            'direction' => $integrationData->direction,
        ]);

        if ($updated) {
            $this->clearIntegrationCache($oldSourceAppId, $oldTargetAppId);
            $this->clearIntegrationCache($integrationData->sourceAppId, $integrationData->targetAppId);
            Cache::forget("integration.{$integration->integration_id}.with_relations");
        }

        return $updated;
    }

    public function delete(AppIntegration $integration): bool
    {
        $sourceAppId = $integration->source_app_id;
        $targetAppId = $integration->target_app_id;
        $integrationId = $integration->integration_id;

        $deleted = $integration->delete();

        if ($deleted) {
            $this->clearIntegrationCache($sourceAppId, $targetAppId);
            Cache::forget("integration.{$integrationId}.with_relations");
        }

        return $deleted;
    }

    public function getIntegrationsForApp(int $appId): Collection
    {
        return Cache::remember(
            "app.{$appId}.integrations",
            self::CACHE_TTL,
            fn() => AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
                ->where('source_app_id', $appId)
                ->orWhere('target_app_id', $appId)
                ->get()
        );
    }

    public function getIntegrationsBetweenApps(int $sourceAppId, int $targetAppId): Collection
    {
        $cacheKey = "integrations.{$sourceAppId}.{$targetAppId}";
        
        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
                ->where(function ($query) use ($sourceAppId, $targetAppId) {
                    $query->where('source_app_id', $sourceAppId)
                          ->where('target_app_id', $targetAppId);
                })
                ->orWhere(function ($query) use ($sourceAppId, $targetAppId) {
                    $query->where('source_app_id', $targetAppId)
                          ->where('target_app_id', $sourceAppId);
                })
                ->get()
        );
    }

    public function integrationExistsBetweenApps(int $sourceAppId, int $targetAppId): bool
    {
        return AppIntegration::where(function ($query) use ($sourceAppId, $targetAppId) {
            $query->where('source_app_id', $sourceAppId)
                  ->where('target_app_id', $targetAppId);
        })->orWhere(function ($query) use ($sourceAppId, $targetAppId) {
            $query->where('source_app_id', $targetAppId)
                  ->where('target_app_id', $sourceAppId);
        })->exists();
    }

    public function getConnectedAppsForApp(int $appId): Collection
    {
        return Cache::remember(
            "app.{$appId}.connected_apps",
            self::CACHE_TTL,
            function () use ($appId) {
                $sourceAppIds = AppIntegration::where('target_app_id', $appId)
                    ->pluck('source_app_id');
                
                $targetAppIds = AppIntegration::where('source_app_id', $appId)
                    ->pluck('target_app_id');
                
                $connectedAppIds = $sourceAppIds->merge($targetAppIds)->unique();
                
                return App::whereIn('app_id', $connectedAppIds)
                    ->with('stream')
                    ->get();
            }
        );
    }

    public function getIntegrationsForApps(array $appIds): Collection
    {
        return AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
            ->where(function ($query) use ($appIds) {
                $query->whereIn('source_app_id', $appIds)
                      ->orWhereIn('target_app_id', $appIds);
            })
            ->get();
    }

    public function removeDuplicateIntegrations(): int
    {
        $duplicateCount = 0;
        $processed = collect();

        AppIntegration::chunk(100, function ($integrations) use (&$duplicateCount, &$processed) {
            foreach ($integrations as $integration) {
                $connectionKey = $this->generateConnectionKey(
                    $integration->source_app_id,
                    $integration->target_app_id
                );

                if ($processed->has($connectionKey)) {
                    $integration->delete();
                    $duplicateCount++;
                } else {
                    $processed->put($connectionKey, $integration->integration_id);
                }
            }
        });

        Cache::flush(); // Clear all cache after removing duplicates

        return $duplicateCount;
    }

    public function getExternalAppsConnectedToStream(array $streamAppIds): Collection
    {
        $connectedAppIds = collect();
        
        // Get apps that have integrations TO stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $streamAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);
        
        // Get apps that have integrations FROM stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $streamAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);
        
        // Remove stream app IDs to get only external apps
        $externalAppIds = $connectedAppIds->diff($streamAppIds)->unique();
        
        return App::whereIn('app_id', $externalAppIds)
            ->with('stream')
            ->get();
    }

    private function applySorting($query, string $sortBy, bool $sortDesc): void
    {
        $sortDirection = $sortDesc ? 'desc' : 'asc';
        
        switch ($sortBy) {
            case 'source_app_name':
                $query->leftJoin('apps as source_apps', 'appintegrations.source_app_id', '=', 'source_apps.app_id')
                      ->orderBy('source_apps.app_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            case 'target_app_name':
                $query->leftJoin('apps as target_apps', 'appintegrations.target_app_id', '=', 'target_apps.app_id')
                      ->orderBy('target_apps.app_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            case 'connection_type_name':
                $query->leftJoin('connectiontypes', 'appintegrations.connection_type_id', '=', 'connectiontypes.connection_type_id')
                      ->orderBy('connectiontypes.type_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            default:
                $query->orderBy('appintegrations.integration_id', $sortDirection);
                break;
        }
    }

    private function generateConnectionKey(int $sourceAppId, int $targetAppId): string
    {
        $appIds = [$sourceAppId, $targetAppId];
        sort($appIds);
        return implode('-', $appIds);
    }

    private function clearIntegrationCache(int $sourceAppId, int $targetAppId): void
    {
        Cache::forget("app.{$sourceAppId}.integrations");
        Cache::forget("app.{$targetAppId}.integrations");
        Cache::forget("app.{$sourceAppId}.connected_apps");
        Cache::forget("app.{$targetAppId}.connected_apps");
        Cache::forget("integrations.{$sourceAppId}.{$targetAppId}");
        Cache::forget("integrations.{$targetAppId}.{$sourceAppId}");
    }
}