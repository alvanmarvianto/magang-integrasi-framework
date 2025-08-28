<?php

namespace App\Repositories;

use App\DTOs\StreamLayoutDTO;
use App\Models\Stream;
use App\Models\StreamLayout;
use App\Repositories\CacheConfig;
use App\Repositories\Exceptions\RepositoryException;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StreamLayoutRepository implements StreamLayoutRepositoryInterface
{
    public function __construct(
        private readonly StreamLayout $model
    ) {}

    /**
     * Get all stream layouts
     */
    public function getAll(): Collection
    {
        $cacheKey = CacheConfig::buildKey('stream_layout', 'all');
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() {
                try {
                    return $this->model->all()->map(fn($layout) => StreamLayoutDTO::fromModel($layout));
                } catch (\Exception $e) {
                    throw RepositoryException::createFailed('stream layouts list', $e->getMessage());
                }
            }
        );
    }

    /**
     * Find stream layout by stream ID
     */
    public function findByStreamId(int $streamId): ?StreamLayoutDTO
    {
        $cacheKey = CacheConfig::buildKey('stream_layout', 'stream_id', $streamId);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($streamId) {
                $layout = $this->model->where('stream_id', $streamId)->first();
                return $layout ? StreamLayoutDTO::fromModel($layout) : null;
            }
        );
    }

    /**
     * Find stream layout by stream name (for backward compatibility)
     */
    public function findByStreamName(string $streamName): ?StreamLayoutDTO
    {
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }
        
        $stream = Stream::where('stream_name', $streamName)->first();
        
        if (!$stream) {
            $stream = Stream::where('stream_name', 'Stream ' . ucfirst($cleanStreamName))->first();
        }
        
        if (!$stream) {
            $stream = Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->first();
        }
        
        if (!$stream) {
            $stream = Stream::whereRaw('LOWER(stream_name) = ?', ['stream ' . $cleanStreamName])->first();
        }
        
        if (!$stream) {
            return null;
        }
        
        return $this->findByStreamId($stream->stream_id);
    }

    /**
     * Update an existing stream layout
     */
    public function update(int $id, StreamLayoutDTO $dto): ?StreamLayoutDTO
    {
        $layout = $this->model->find($id);
        
        if (!$layout) {
            return null;
        }
        
        $layout->update($dto->toArray());
        
        $this->clearCaches();
        Cache::forget(CacheConfig::buildKey('stream_layout', 'id', $id));
        Cache::forget(CacheConfig::buildKey('stream_layout', 'stream_id', $dto->streamId));
        
        return StreamLayoutDTO::fromModel($layout->fresh());
    }

    /**
     * Save layout for a specific stream (create or update) - backward compatibility
     */
    public function saveLayout(string $streamName, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO
    {
        return $this->saveLayoutByName($streamName, $nodesLayout, $edgesLayout, $streamConfig);
    }

    /**
     * Save layout for a specific stream by ID
     */
    public function saveLayoutById(int $streamId, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO
    {
        $layout = $this->model->updateOrCreate(
            ['stream_id' => $streamId],
            [
                'nodes_layout' => $nodesLayout,
                'edges_layout' => $edgesLayout,
                'stream_config' => $streamConfig,
            ]
        );
        
        $this->clearCaches();
        Cache::forget(CacheConfig::buildKey('stream_layout', 'stream_id', $streamId));
        
        return StreamLayoutDTO::fromModel($layout);
    }

    /**
     * Save layout for a specific stream by name (for backward compatibility)
     */
    public function saveLayoutByName(string $streamName, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO
    {
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }
        
        $stream = Stream::where('stream_name', $streamName)->first();
        
        if (!$stream) {
            $stream = Stream::where('stream_name', 'Stream ' . ucfirst($cleanStreamName))->first();
        }
        
        if (!$stream) {
            $stream = Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->first();
        }
        
        if (!$stream) {
            $stream = Stream::whereRaw('LOWER(stream_name) = ?', ['stream ' . $cleanStreamName])->first();
        }
        
        if (!$stream) {
            throw new \InvalidArgumentException("Unknown stream name: {$streamName}");
        }
        
        return $this->saveLayoutById($stream->stream_id, $nodesLayout, $edgesLayout, $streamConfig);
    }

    /**
     * Remove app from all stream layouts
     */
    public function removeAppFromLayouts(int $appId): void
    {
        $layouts = $this->model->all();
        
        foreach ($layouts as $layout) {
            $updated = false;
            $nodesLayout = $layout->nodes_layout ?? [];
            $edgesLayout = $layout->edges_layout ?? [];
            $streamConfig = $layout->stream_config ?? [];
            
            if (isset($nodesLayout[(string)$appId])) {
                unset($nodesLayout[(string)$appId]);
                $updated = true;
            }
            
            $originalEdgeCount = count($edgesLayout);
            $edgesLayout = array_filter($edgesLayout, function($edge) use ($appId) {
                return $edge['source'] !== (string)$appId && $edge['target'] !== (string)$appId;
            });
            
            if (count($edgesLayout) !== $originalEdgeCount) {
                $updated = true;
            }
            
            if (isset($streamConfig['totalNodes'])) {
                $streamConfig['totalNodes'] = count($nodesLayout);
                $updated = true;
            }
            
            if (isset($streamConfig['totalEdges'])) {
                $streamConfig['totalEdges'] = count($edgesLayout);
                $updated = true;
            }
            
            if ($updated) {
                $layout->update([
                    'nodes_layout' => $nodesLayout,
                    'edges_layout' => array_values($edgesLayout),
                    'stream_config' => $streamConfig,
                ]);
            }
        }
        
        $this->clearCaches();
    }

    /**
     * Get layout data for a specific stream by ID
     */
    public function getLayoutDataById(int $streamId): ?array
    {
        $layout = $this->findByStreamId($streamId);
        
        return $layout ? [
            'nodes_layout' => $layout->nodesLayout,
            'edges_layout' => $layout->edgesLayout,
            'stream_config' => $layout->streamConfig,
        ] : null;
    }

    /**
     * Clear all caches related to stream layouts
     */
    private function clearCaches(): void
    {
        Cache::forget(CacheConfig::buildKey('stream_layout', 'all'));
        Cache::forget(CacheConfig::buildKey('stream_layout', 'statistics'));
        
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget(CacheConfig::buildKey('stream_layout', 'most_nodes', $i));
            Cache::forget(CacheConfig::buildKey('stream_layout', 'most_edges', $i));
        }
    }
}