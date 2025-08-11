<?php

namespace App\Repositories;

use App\DTOs\StreamDTO;
use App\Models\Stream;
use App\Repositories\BaseRepository;
use App\Repositories\CacheConfig;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class StreamRepository extends BaseRepository implements StreamRepositoryInterface
{
    /**
     * Get allowed sort fields for streams
     */
    protected function getAllowedSortFields(): array
    {
        return [
            'stream_name',
            'stream_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Get default sort field for streams
     */
    protected function getDefaultSortField(): string
    {
        return 'stream_name';
    }

    /**
     * Get entity name for cache operations
     */
    protected function getEntityName(): string
    {
        return 'stream';
    }

    public function getAll(): Collection
    {
        $cacheKey = $this->buildCacheKey('streams', 'all');
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::orderBy('stream_name')->get()
        );
    }

    public function getAllWithApps(): Collection
    {
        $cacheKey = $this->buildCacheKey('streams', 'all_with_apps');
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::with('apps')->orderBy('stream_name')->get()
        );
    }

    public function getAllWithAppsLimited(array $allowedStreamNames = []): Collection
    {
        $sortedNames = $allowedStreamNames;
        sort($sortedNames);
        $cacheKey = $this->buildCacheKey('streams', 'all_with_apps_limited', md5(implode(',', $sortedNames)));
        
        return $this->handleCacheOperation(
            $cacheKey,
            function () use ($allowedStreamNames) {
                $query = Stream::with('apps');
                
                if (!empty($allowedStreamNames)) {
                    $query->whereIn('stream_name', $allowedStreamNames);
                    
                    // Order by the custom order defined in the allowed streams array
                    $orderCases = [];
                    foreach ($allowedStreamNames as $index => $streamName) {
                        $orderCases[] = "WHEN stream_name = '{$streamName}' THEN {$index}";
                    }
                    if (!empty($orderCases)) {
                        $orderByCase = 'CASE ' . implode(' ', $orderCases) . ' ELSE 999 END';
                        $query->orderByRaw($orderByCase);
                    }
                } else {
                    $query->orderBy('stream_name');
                }
                
                return $query->get();
            }
        );
    }

    public function getAllAsDTO(): Collection
    {
        try {
            $streams = $this->getAll();
            return $streams->map(fn($stream) => StreamDTO::fromModel($stream));
        } catch (\Exception $e) {
            Log::error('Failed to get all streams as DTO', [
                'exception' => $e->getMessage()
            ]);
            
            throw new RepositoryException('Failed to get streams as DTO: ' . $e->getMessage());
        }
    }

    public function findById(int $id): ?Stream
    {
        $this->validateId($id);
        
        $cacheKey = $this->buildCacheKey('stream', 'id', $id);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::find($id)
        );
    }

    public function findByIdAsDTO(int $id): ?StreamDTO
    {
        try {
            $stream = $this->findById($id);
            return $stream ? StreamDTO::fromModel($stream) : null;
        } catch (\Exception $e) {
            Log::error('Failed to find stream by ID as DTO', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::entityNotFound('stream', $id);
        }
    }

    public function findByName(string $name): ?Stream
    {
        $this->validateNotEmpty($name, 'stream name');
        
        $cacheKey = $this->buildCacheKey('stream', 'name', $name);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::where('stream_name', $name)->first()
        );
    }

    public function findByNameAsDTO(string $name): ?StreamDTO
    {
        try {
            $stream = $this->findByName($name);
            return $stream ? StreamDTO::fromModel($stream) : null;
        } catch (\Exception $e) {
            Log::error('Failed to find stream by name as DTO', [
                'name' => $name,
                'exception' => $e->getMessage()
            ]);
            
            throw new RepositoryException('Failed to find stream by name: ' . $e->getMessage());
        }
    }

    public function findByNameWithApps(string $name): ?Stream
    {
        $this->validateNotEmpty($name, 'stream name');
        
        $cacheKey = $this->buildCacheKey('stream', 'name_with_apps', $name);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::with('apps')->where('stream_name', $name)->first()
        );
    }

    public function create(array $data): Stream
    {
        $this->validateNotEmpty($data['stream_name'] ?? '', 'stream_name');

        try {
            $stream = Stream::create([
                'stream_name' => $data['stream_name'],
                'description' => $data['description'] ?? null,
                'is_allowed_for_diagram' => $data['is_allowed_for_diagram'] ?? false,
                'sort_order' => $data['sort_order'] ?? null,
                'color' => $data['color'] ?? null,
            ]);

            $this->clearEntityCache($this->getEntityName());
            
            // Note: Cache clearing will be handled by service layer

            return $stream;
        } catch (\Exception $e) {
            Log::error('Failed to create stream', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::createFailed('stream', $e->getMessage());
        }
    }

    public function createFromDTO(StreamDTO $streamDTO): Stream
    {
        return $this->create([
            'stream_name' => $streamDTO->streamName
        ]);
    }

    public function update(Stream $stream, array $data): bool
    {
        $this->validateNotEmpty($data['stream_name'] ?? '', 'stream_name');
        
        $oldName = $stream->stream_name;
        
        try {
            $updated = $stream->update([
                'stream_name' => $data['stream_name'],
                'description' => $data['description'] ?? $stream->description,
                'is_allowed_for_diagram' => $data['is_allowed_for_diagram'] ?? $stream->is_allowed_for_diagram,
                'sort_order' => $data['sort_order'] ?? $stream->sort_order,
                'color' => $data['color'] ?? $stream->color,
            ]);

            if ($updated) {
                $this->clearEntityCache($this->getEntityName(), $stream->stream_id);
                // Clear old name caches
                Cache::forget($this->buildCacheKey('stream', 'name', $oldName));
                Cache::forget($this->buildCacheKey('stream', 'name_with_apps', $oldName));
                
                // Note: Stream configuration cache clearing will be handled by service layer
            }

            return $updated;
        } catch (\Exception $e) {
            Log::error('Failed to update stream', [
                'streamId' => $stream->stream_id,
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::updateFailed('stream', $stream->stream_id, $e->getMessage());
        }
    }

    public function updateFromDTO(Stream $stream, StreamDTO $streamDTO): bool
    {
        return $this->update($stream, [
            'stream_name' => $streamDTO->streamName
        ]);
    }

    public function delete(Stream $stream): bool
    {
        $streamName = $stream->stream_name;
        $streamId = $stream->stream_id;
        
        try {
            $deleted = $stream->delete();

            if ($deleted) {
                $this->clearEntityCache($this->getEntityName(), $streamId);
                Cache::forget($this->buildCacheKey('stream', 'name', $streamName));
                Cache::forget($this->buildCacheKey('stream', 'name_with_apps', $streamName));
                
                // Note: Stream configuration cache clearing will be handled by service layer
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete stream', [
                'streamId' => $streamId,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::deleteFailed('stream', $streamId, $e->getMessage());
        }
    }

    public function getStreamsByNames(array $names): Collection
    {
        if (empty($names)) {
            return new Collection();
        }

        foreach ($names as $name) {
            $this->validateNotEmpty($name, 'stream name');
        }

        $sortedNames = $names;
        sort($sortedNames);
        $cacheKey = $this->buildCacheKey('streams', 'names', md5(implode(',', $sortedNames)));
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::whereIn('stream_name', $names)->get()
        );
    }

    public function existsByName(string $name): bool
    {
        $this->validateNotEmpty($name, 'stream name');
        
        $cacheKey = $this->buildCacheKey('stream', 'exists', $name);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::where('stream_name', $name)->exists()
        );
    }

    public function getStreamStatistics(): array
    {
        $cacheKey = $this->buildCacheKey('streams', 'statistics');
        
        return $this->handleCacheOperation(
            $cacheKey,
            function () {
                $streams = Stream::withCount('apps')->get();
                
                return [
                    'total_streams' => $streams->count(),
                    'streams_with_apps' => $streams->where('apps_count', '>', 0)->count(),
                    'streams_without_apps' => $streams->where('apps_count', 0)->count(),
                    'total_apps' => $streams->sum('apps_count'),
                    'average_apps_per_stream' => $streams->count() > 0 
                        ? round($streams->sum('apps_count') / $streams->count(), 2)
                        : 0,
                    'stream_details' => $streams->map(function ($stream) {
                        return [
                            'stream_id' => $stream->stream_id,
                            'stream_name' => $stream->stream_name,
                            'apps_count' => $stream->apps_count,
                        ];
                    })->toArray(),
                ];
            },
            CacheConfig::getTTL('statistics')
        );
    }
} 