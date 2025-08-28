<?php

namespace App\Repositories;

use App\Models\Stream;
use App\Repositories\BaseRepository;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    public function findById(int $id): ?Stream
    {
        $this->validateId($id);
        
        $cacheKey = $this->buildCacheKey('stream', 'id', $id);
        
        return $this->handleCacheOperation(
            $cacheKey,
            fn() => Stream::find($id)
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

            return $stream;
        } catch (\Exception $e) {
            Log::error('Failed to create stream', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::createFailed('stream', $e->getMessage());
        }
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
                Cache::forget($this->buildCacheKey('stream', 'name', $oldName));
                Cache::forget($this->buildCacheKey('stream', 'name_with_apps', $oldName));
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
} 