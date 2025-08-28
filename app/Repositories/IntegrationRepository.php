<?php

namespace App\Repositories;

use App\Models\AppIntegration;
use App\DTOs\IntegrationDTO;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class IntegrationRepository implements IntegrationRepositoryInterface
{
    public function getIntegrationOptions(): array
    {
        $cacheKey = CacheConfig::buildKey('integrations', 'options');
        $ttl = CacheConfig::getTTL('default');

        return Cache::remember($cacheKey, $ttl, function () {
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

            if ($updated && is_array($integrationData->connections)) {
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
        Cache::forget(CacheConfig::buildKey('app', 'integrations', $sourceAppId));
        Cache::forget(CacheConfig::buildKey('app', 'integrations', $targetAppId));
        Cache::forget(CacheConfig::buildKey('app', 'connected_apps', $sourceAppId));
        Cache::forget(CacheConfig::buildKey('app', 'connected_apps', $targetAppId));
        Cache::forget(CacheConfig::buildKey('integrations', 'between_apps', $sourceAppId, $targetAppId));
        Cache::forget(CacheConfig::buildKey('integrations', 'between_apps', $targetAppId, $sourceAppId));
        Cache::forget(CacheConfig::buildKey('integrations', 'options'));
    }
}