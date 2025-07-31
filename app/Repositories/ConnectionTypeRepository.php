<?php

namespace App\Repositories;

use App\Models\ConnectionType;
use App\DTOs\ConnectionTypeDTO;
use App\Repositories\Interfaces\ConnectionTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ConnectionTypeRepository implements ConnectionTypeRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function getAll(): Collection
    {
        return Cache::remember(
            'connection_types.all',
            self::CACHE_TTL,
            fn() => ConnectionType::orderBy('type_name')->get()
        );
    }

    public function getAllWithUsageCounts(): Collection
    {
        return Cache::remember(
            'connection_types.all_with_usage',
            self::CACHE_TTL,
            fn() => ConnectionType::withCount('appIntegrations')
                ->orderBy('type_name')
                ->get()
        );
    }

    public function findById(int $id): ?ConnectionType
    {
        return Cache::remember(
            "connection_type.{$id}",
            self::CACHE_TTL,
            fn() => ConnectionType::find($id)
        );
    }

    public function findByName(string $name): ?ConnectionType
    {
        return Cache::remember(
            "connection_type.name.{$name}",
            self::CACHE_TTL,
            fn() => ConnectionType::where('type_name', $name)->first()
        );
    }

    public function create(ConnectionTypeDTO $connectionTypeData): ConnectionType
    {
        $connectionType = ConnectionType::create([
            'type_name' => $connectionTypeData->typeName,
            'description' => $connectionTypeData->description,
        ]);

        $this->clearConnectionTypeCache();

        return $connectionType;
    }

    public function update(ConnectionType $connectionType, ConnectionTypeDTO $connectionTypeData): bool
    {
        $oldName = $connectionType->type_name;
        
        $updated = $connectionType->update([
            'type_name' => $connectionTypeData->typeName,
            'description' => $connectionTypeData->description,
        ]);

        if ($updated) {
            $this->clearConnectionTypeCache();
            Cache::forget("connection_type.name.{$oldName}");
        }

        return $updated;
    }

    public function delete(ConnectionType $connectionType): bool
    {
        $connectionTypeId = $connectionType->connection_type_id;
        $typeName = $connectionType->type_name;
        
        $deleted = $connectionType->delete();

        if ($deleted) {
            $this->clearConnectionTypeCache();
            Cache::forget("connection_type.{$connectionTypeId}");
            Cache::forget("connection_type.name.{$typeName}");
        }

        return $deleted;
    }

    public function existsByName(string $name): bool
    {
        return Cache::remember(
            "connection_type.exists.{$name}",
            self::CACHE_TTL,
            fn() => ConnectionType::where('type_name', $name)->exists()
        );
    }

    public function getConnectionTypeStatistics(): array
    {
        return Cache::remember(
            'connection_types.statistics',
            self::CACHE_TTL,
            function () {
                $connectionTypes = ConnectionType::withCount('appIntegrations')->get();
                
                return [
                    'total_connection_types' => $connectionTypes->count(),
                    'used_connection_types' => $connectionTypes->where('app_integrations_count', '>', 0)->count(),
                    'unused_connection_types' => $connectionTypes->where('app_integrations_count', 0)->count(),
                    'total_integrations' => $connectionTypes->sum('app_integrations_count'),
                    'average_integrations_per_type' => $connectionTypes->count() > 0 
                        ? round($connectionTypes->sum('app_integrations_count') / $connectionTypes->count(), 2)
                        : 0,
                    'most_used_types' => $connectionTypes
                        ->sortByDesc('app_integrations_count')
                        ->take(5)
                        ->map(function ($type) {
                            return [
                                'connection_type_id' => $type->connection_type_id,
                                'type_name' => $type->type_name,
                                'usage_count' => $type->app_integrations_count,
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            }
        );
    }

    public function getMostUsedConnectionTypes(int $limit = 10): Collection
    {
        return Cache::remember(
            "connection_types.most_used.{$limit}",
            self::CACHE_TTL,
            fn() => ConnectionType::withCount('appIntegrations')
                ->orderByDesc('app_integrations_count')
                ->limit($limit)
                ->get()
        );
    }

    private function clearConnectionTypeCache(): void
    {
        Cache::forget('connection_types.all');
        Cache::forget('connection_types.all_with_usage');
        Cache::forget('connection_types.statistics');
        
        // Clear most used cache for common limits
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("connection_types.most_used.{$i}");
        }
    }
}