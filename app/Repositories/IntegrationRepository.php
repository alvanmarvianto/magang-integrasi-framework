<?php

namespace App\Repositories;

use App\Models\App;
use App\Models\AppIntegration;
use App\DTOs\IntegrationDTO;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class IntegrationRepository implements IntegrationRepositoryInterface
{
    public function getIntegrationOptions(): array
    {
        $cacheKey = CacheConfig::buildKey('integrations', 'options');
        $ttl = CacheConfig::getTTL('default');

        return Cache::remember($cacheKey, $ttl, function () {
            // Join with apps to build a human-friendly label
            $rows = AppIntegration::query()
                ->leftJoin('apps as s', 'appintegrations.source_app_id', '=', 's.app_id')
                ->leftJoin('apps as t', 'appintegrations.target_app_id', '=', 't.app_id')
                ->orderBy('s.app_name')
                ->orderBy('t.app_name')
                ->get([
                    'appintegrations.integration_id',
                    'appintegrations.source_app_id',
                    'appintegrations.target_app_id',
                    's.app_name as source_name',
                    't.app_name as target_name'
                ]);

            $options = $rows->map(function ($r) {
                $label = trim(($r->source_name ?? 'Unknown') . ' → ' . ($r->target_name ?? 'Unknown'));
                return [
                    'integration_id' => (int) $r->integration_id,
                    'label' => $label,
                    'source_app_id' => (int) $r->source_app_id,
                    'target_app_id' => (int) $r->target_app_id,
                    'source_name' => (string) ($r->source_name ?? ''),
                    'target_name' => (string) ($r->target_name ?? ''),
                ];
            })->toArray();

            // Ensure alphabetical ordering by label
            usort($options, fn($a, $b) => strcmp($a['label'], $b['label']));
            return $options;
        });
    }
    public function getPaginatedIntegrations(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'source_app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator {
        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('Per page must be between 1 and 100');
        }

        try {
            $query = AppIntegration::with(['connections.connectionType', 'sourceApp', 'targetApp']);

            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('sourceApp', function ($q) use ($search) {
                        $q->where('app_name', 'like', "%{$search}%");
                    })->orWhereHas('targetApp', function ($q) use ($search) {
                        $q->where('app_name', 'like', "%{$search}%");
                    })->orWhereHas('connections.connectionType', function ($q) use ($search) {
                        $q->where('type_name', 'like', "%{$search}%");
                    });
                });
            }

            $this->applySorting($query, $sortBy, $sortDesc);

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw RepositoryException::createFailed('paginated integrations', $e->getMessage());
        }
    }

    public function findWithRelations(int $integrationId): ?AppIntegration
    {
        if ($integrationId <= 0) {
            throw new \InvalidArgumentException('Integration ID must be a positive integer');
        }

        $cacheKey = CacheConfig::buildKey('integration', 'with_relations', $integrationId);
        $cacheTTL = CacheConfig::getTTL('relations');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($integrationId) {
                try {
                    return AppIntegration::with(['connections.connectionType', 'sourceApp.stream', 'targetApp.stream'])
                        ->find($integrationId);
                } catch (\Exception $e) {
                    throw RepositoryException::entityNotFound('integration', $integrationId);
                }
            }
        );
    }

    public function create(IntegrationDTO $integrationData): AppIntegration
    {
        try {
            $integration = AppIntegration::create([
                'source_app_id' => $integrationData->sourceAppId,
                'target_app_id' => $integrationData->targetAppId,
            ]);

            // Insert connection rows (skip null connection_type_id)
            foreach ($integrationData->connections as $conn) {
                $ctId = $conn['connection_type_id'] ?? null;
                if ($ctId === null) {
                    continue;
                }
                \DB::table('appintegration_connections')->insert([
                    'integration_id' => $integration->integration_id,
                    'connection_type_id' => $ctId,
                    'source_inbound' => $conn['source_inbound'] ?? null,
                    'source_outbound' => $conn['source_outbound'] ?? null,
                    'target_inbound' => $conn['target_inbound'] ?? null,
                    'target_outbound' => $conn['target_outbound'] ?? null,
                ]);
            }

            $this->clearIntegrationCache($integrationData->sourceAppId, $integrationData->targetAppId);

            return $integration->load(['connections.connectionType', 'sourceApp', 'targetApp']);
        } catch (\Exception $e) {
            throw RepositoryException::createFailed('integration', $e->getMessage());
        }
    }

    public function update(AppIntegration $integration, IntegrationDTO $integrationData): bool
    {
        $oldSourceAppId = $integration->source_app_id;
        $oldTargetAppId = $integration->target_app_id;

        try {
            $updated = $integration->update([
                'source_app_id' => $integrationData->sourceAppId,
                'target_app_id' => $integrationData->targetAppId,
            ]);

            // If connections provided, replace them atomically
            if ($updated && is_array($integrationData->connections) && count($integrationData->connections) > 0) {
                \DB::table('appintegration_connections')
                    ->where('integration_id', $integration->integration_id)
                    ->delete();

                $rows = [];
                foreach ($integrationData->connections as $conn) {
                    $ctId = $conn['connection_type_id'] ?? null;
                    if ($ctId === null) {
                        continue;
                    }
                    $rows[] = [
                        'integration_id' => $integration->integration_id,
                        'connection_type_id' => $ctId,
                        'source_inbound' => $conn['source_inbound'] ?? null,
                        'source_outbound' => $conn['source_outbound'] ?? null,
                        'target_inbound' => $conn['target_inbound'] ?? null,
                        'target_outbound' => $conn['target_outbound'] ?? null,
                    ];
                }
                if (!empty($rows)) {
                    \DB::table('appintegration_connections')->insert($rows);
                }
            }

            if ($updated) {
                $this->clearIntegrationCache($oldSourceAppId, $oldTargetAppId);
                $this->clearIntegrationCache($integrationData->sourceAppId, $integrationData->targetAppId);
                // Clear the specific integration cache using the same key builder as retrieval
                Cache::forget(CacheConfig::buildKey('integration', 'with_relations', $integration->integration_id));
            }

            return $updated;
        } catch (\Exception $e) {
            throw RepositoryException::updateFailed('integration', $integration->integration_id, $e->getMessage());
        }
    }

    public function delete(AppIntegration $integration): bool
    {
        $sourceAppId = $integration->source_app_id;
        $targetAppId = $integration->target_app_id;
        $integrationId = $integration->integration_id;

        try {
            $deleted = $integration->delete();

            if ($deleted) {
                $this->clearIntegrationCache($sourceAppId, $targetAppId);
                Cache::forget(CacheConfig::buildKey('integration', 'with_relations', $integrationId));
            }

            return $deleted;
        } catch (\Exception $e) {
            throw RepositoryException::deleteFailed('integration', $integrationId, $e->getMessage());
        }
    }

    public function getIntegrationsForApp(int $appId): Collection
    {
        $cacheKey = CacheConfig::buildKey('app', 'integrations', $appId);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            fn() => AppIntegration::with(['connections.connectionType', 'sourceApp', 'targetApp'])
                ->where('source_app_id', $appId)
                ->orWhere('target_app_id', $appId)
                ->get()
        );
    }

    public function getIntegrationsBetweenApps(int $sourceAppId, int $targetAppId): Collection
    {
        $cacheKey = CacheConfig::buildKey('integrations', 'between_apps', $sourceAppId, $targetAppId);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            fn() => AppIntegration::with(['connections.connectionType', 'sourceApp', 'targetApp'])
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
        $cacheKey = CacheConfig::buildKey('app', 'connected_apps', $appId);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
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
    return AppIntegration::with(['connections.connectionType', 'sourceApp', 'targetApp'])
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
            // Join through appintegration_connections to sort by the first connection type name
            $query->leftJoin('appintegration_connections as aic', 'appintegrations.integration_id', '=', 'aic.integration_id')
                ->leftJoin('connectiontypes', 'aic.connection_type_id', '=', 'connectiontypes.connection_type_id')
                ->orderBy('connectiontypes.type_name', $sortDirection)
                ->select('appintegrations.*')
                ->groupBy('appintegrations.integration_id');
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
        // Clear using CacheConfig format
        Cache::forget(CacheConfig::buildKey('app', 'integrations', $sourceAppId));
        Cache::forget(CacheConfig::buildKey('app', 'integrations', $targetAppId));
        Cache::forget(CacheConfig::buildKey('app', 'connected_apps', $sourceAppId));
        Cache::forget(CacheConfig::buildKey('app', 'connected_apps', $targetAppId));
        Cache::forget(CacheConfig::buildKey('integrations', 'between_apps', $sourceAppId, $targetAppId));
        Cache::forget(CacheConfig::buildKey('integrations', 'between_apps', $targetAppId, $sourceAppId));
    Cache::forget(CacheConfig::buildKey('integrations', 'options'));
        // Do NOT attempt to clear integration-with-relations by app IDs; that cache is keyed by integration_id
    }
}