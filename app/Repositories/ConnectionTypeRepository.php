<?php

namespace App\Repositories;

use App\Models\ConnectionType;
use App\DTOs\ConnectionTypeDTO;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\ConnectionTypeRepositoryInterface;
use App\Repositories\CacheConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ConnectionTypeRepository implements ConnectionTypeRepositoryInterface
{
    public function getAll(): Collection
    {
        $cacheKey = CacheConfig::buildKey('connection_types', 'all');
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() {
                try {
                    return ConnectionType::orderBy('type_name')->get();
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('connection types list', $e->getMessage());
                }
            }
        );
    }

    public function getAllWithUsageCounts(): Collection
    {
        $cacheKey = CacheConfig::buildKey('connection_types', 'all_with_usage');
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() {
                try {
                    return ConnectionType::withCount('appIntegrationConnections')
                        ->orderBy('type_name')
                        ->get();
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('connection types with usage counts', $e->getMessage());
                }
            }
        );
    }

    public function getAllWithUsageCount(): Collection
    {
        return $this->getAllWithUsageCounts();
    }

    public function findById(int $id): ?ConnectionType
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID must be a positive integer');
        }

        $cacheKey = CacheConfig::buildKey('connection_type', 'id', $id);
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($id) {
                try {
                    return ConnectionType::find($id);
                } catch (\Exception $e) {
                    throw RepositoryException::entityNotFound('connection type', $id);
                }
            }
        );
    }

    public function findByName(string $name): ?ConnectionType
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $cacheKey = CacheConfig::buildKey('connection_type', 'name', $name);
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($name) {
                try {
                    return ConnectionType::where('type_name', $name)->first();
                } catch (\Exception $e) {
                    throw RepositoryException::entityNotFound('connection type', $name);
                }
            }
        );
    }

    public function create(ConnectionTypeDTO $connectionTypeData): ConnectionType
    {
        try {
            $connectionType = ConnectionType::create([
                'type_name' => $connectionTypeData->typeName,
                'color' => $connectionTypeData->color,
                'description' => $connectionTypeData->description,
            ]);

            $this->clearConnectionTypeCache();

            return $connectionType;
        } catch (\Exception $e) {
            throw RepositoryException::createFailed('connection type', $e->getMessage());
        }
    }

    public function update(ConnectionType $connectionType, ConnectionTypeDTO $connectionTypeData): bool
    {
        $oldName = $connectionType->type_name;
        
        try {
            $updated = $connectionType->update([
                'type_name' => $connectionTypeData->typeName,
                'color' => $connectionTypeData->color,
                'description' => $connectionTypeData->description,
            ]);

            if ($updated) {
                $this->clearConnectionTypeCache();
                Cache::forget("connection_type.name.{$oldName}");
            }

            return $updated;
        } catch (\Exception $e) {
            throw RepositoryException::updateFailed('connection type', $connectionType->connection_type_id, $e->getMessage());
        }
    }

    public function delete(ConnectionType $connectionType): bool
    {
        $connectionTypeId = $connectionType->connection_type_id;
        $typeName = $connectionType->type_name;
        
        try {
            $deleted = $connectionType->delete();

            if ($deleted) {
                $this->clearConnectionTypeCache();
                Cache::forget("connection_type.{$connectionTypeId}");
                Cache::forget("connection_type.name.{$typeName}");
            }

            return $deleted;
        } catch (\Exception $e) {
            throw RepositoryException::deleteFailed('connection type', $connectionTypeId, $e->getMessage());
        }
    }

    public function existsByName(string $name): bool
    {
        $cacheKey = CacheConfig::buildKey('connection_type', 'exists', $name);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            fn() => ConnectionType::where('type_name', $name)->exists()
        );
    }

    public function getConnectionTypeStatistics(): array
    {
        $cacheKey = CacheConfig::buildKey('connection_types', 'statistics');
        $cacheTTL = CacheConfig::getTTL('statistics');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function () {
                try {
                    $connectionTypes = ConnectionType::withCount('appIntegrationConnections')->get();
                    
                    return [
                        'total_connection_types' => $connectionTypes->count(),
                        'used_connection_types' => $connectionTypes->where('app_integration_connections_count', '>', 0)->count(),
                        'unused_connection_types' => $connectionTypes->where('app_integration_connections_count', 0)->count(),
                        'total_integrations' => $connectionTypes->sum('app_integration_connections_count'),
                        'average_integrations_per_type' => $connectionTypes->count() > 0 
                            ? round($connectionTypes->sum('app_integration_connections_count') / $connectionTypes->count(), 2)
                            : 0,
                        'most_used_types' => $connectionTypes
                            ->sortByDesc('app_integration_connections_count')
                            ->take(5)
                            ->map(function ($type) {
                                return [
                                    'connection_type_id' => $type->connection_type_id,
                                    'type_name' => $type->type_name,
                                    'usage_count' => $type->app_integration_connections_count,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('connection type statistics', $e->getMessage());
                }
            }
        );
    }

    public function getMostUsedConnectionTypes(int $limit = 10): Collection
    {
        $cacheKey = CacheConfig::buildKey('connection_types', 'most_used', $limit);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            fn() => ConnectionType::withCount('appIntegrationConnections')
                ->orderByDesc('app_integration_connections_count')
                ->limit($limit)
                ->get()
        );
    }

    public function isBeingUsed(int $id): bool
    {
        $cacheKey = CacheConfig::buildKey('connection_type', 'is_used', $id);
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($id) {
                try {
                    $connectionType = ConnectionType::find($id);
                    return $connectionType ? $connectionType->appIntegrationConnections()->exists() : false;
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('check connection type usage', $e->getMessage());
                }
            }
        );
    }

    public function getUsageDetails(int $id): array
    {
        $cacheKey = CacheConfig::buildKey('connection_type', 'usage_details', $id);
        $cacheTTL = CacheConfig::getTTL('default');

        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($id) {
                try {
                    $connectionType = ConnectionType::with(['appIntegrationConnections.integration.sourceApp', 'appIntegrationConnections.integration.targetApp'])
                        ->find($id);
                    
                    if (!$connectionType) {
                        return [
                            'is_used' => false,
                            'usage_count' => 0,
                            'integrations' => collect()
                        ];
                    }

                    $connections = $connectionType->appIntegrationConnections;
                    
                    return [
                        'is_used' => $connections->count() > 0,
                        'usage_count' => $connections->count(),
                        'integrations' => $connections
                    ];
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('get connection type usage details', $e->getMessage());
                }
            }
        );
    }

    private function clearConnectionTypeCache(): void
    {
        // Clear main cache keys using CacheConfig
        Cache::forget(CacheConfig::buildKey('connection_types', 'all'));
        Cache::forget(CacheConfig::buildKey('connection_types', 'all_with_usage'));
        Cache::forget(CacheConfig::buildKey('connection_types', 'statistics'));
        
        // Clear most used cache for common limits
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget(CacheConfig::buildKey('connection_types', 'most_used', $i));
        }

        // Clear diagram-related caches for all streams since connection types affect colors
        try {
            // Get stream names from database instead of hardcoded list
            $streams = \App\Models\Stream::pluck('stream_name')->toArray();
            foreach ($streams as $streamName) {
                Cache::forget("diagram_data.{$streamName}");
                Cache::forget("vue_flow_data.{$streamName}");
                Cache::forget("stream_layout.{$streamName}");
            }
        } catch (\Exception $e) {
            // Stream table might not be available, continue without diagram cache clearing
        }
    }
}