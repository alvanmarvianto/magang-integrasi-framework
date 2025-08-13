<?php

namespace App\Repositories;

use App\DTOs\StreamLayoutDTO;
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
     * Find stream layout by ID
     */
    public function findById(int $id): ?StreamLayoutDTO
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID must be a positive integer');
        }

        $cacheKey = CacheConfig::buildKey('stream_layout', 'id', $id);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($id) {
                try {
                    $layout = $this->model->find($id);
                    return $layout ? StreamLayoutDTO::fromModel($layout) : null;
                } catch (\Exception $e) {
                    throw RepositoryException::entityNotFound('stream layout', $id);
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
        // Clean up the input stream name
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7); // Remove "stream " prefix
        }
        
        // Try to find stream by exact match first
        $stream = \App\Models\Stream::where('stream_name', $streamName)->first();
        
        // If not found, try with "Stream " prefix
        if (!$stream) {
            $stream = \App\Models\Stream::where('stream_name', 'Stream ' . ucfirst($cleanStreamName))->first();
        }
        
        // If still not found, try case-insensitive search
        if (!$stream) {
            $stream = \App\Models\Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->first();
        }
        
        // If still not found, try case-insensitive search with "Stream " prefix
        if (!$stream) {
            $stream = \App\Models\Stream::whereRaw('LOWER(stream_name) = ?', ['stream ' . $cleanStreamName])->first();
        }
        
        if (!$stream) {
            return null;
        }
        
        return $this->findByStreamId($stream->stream_id);
    }

    /**
     * Create a new stream layout
     */
    public function create(StreamLayoutDTO $dto): StreamLayoutDTO
    {
        $layout = $this->model->create($dto->toArray());
        
        // Clear relevant caches
        $this->clearCaches();
        
        return StreamLayoutDTO::fromModel($layout);
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
        
        // Clear relevant caches
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
        
        // Clear relevant caches
        $this->clearCaches();
        Cache::forget(CacheConfig::buildKey('stream_layout', 'stream_id', $streamId));
        
        return StreamLayoutDTO::fromModel($layout);
    }

    /**
     * Save layout for a specific stream by name (for backward compatibility)
     */
    public function saveLayoutByName(string $streamName, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO
    {
        // Clean up the input stream name
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }
        
        // Try to find stream by exact match first
        $stream = \App\Models\Stream::where('stream_name', $streamName)->first();
        
        // If not found, try with "Stream " prefix
        if (!$stream) {
            $stream = \App\Models\Stream::where('stream_name', 'Stream ' . ucfirst($cleanStreamName))->first();
        }
        
        // If still not found, try case-insensitive search
        if (!$stream) {
            $stream = \App\Models\Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->first();
        }
        
        // If still not found, try case-insensitive search with "Stream " prefix
        if (!$stream) {
            $stream = \App\Models\Stream::whereRaw('LOWER(stream_name) = ?', ['stream ' . $cleanStreamName])->first();
        }
        
        if (!$stream) {
            throw new \InvalidArgumentException("Unknown stream name: {$streamName}");
        }
        
        return $this->saveLayoutById($stream->stream_id, $nodesLayout, $edgesLayout, $streamConfig);
    }

    /**
     * Delete stream layout by ID
     */
    public function delete(int $id): bool
    {
        $layout = $this->model->find($id);
        
        if (!$layout) {
            return false;
        }
        
        $streamId = $layout->stream_id;
        $deleted = $layout->delete();
        
        if ($deleted) {
            // Clear relevant caches
            $this->clearCaches();
            Cache::forget(CacheConfig::buildKey('stream_layout', 'id', $id));
            Cache::forget(CacheConfig::buildKey('stream_layout', 'stream_id', $streamId));
        }
        
        return $deleted;
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
            
            // Remove app node if it exists
            if (isset($nodesLayout[(string)$appId])) {
                unset($nodesLayout[(string)$appId]);
                $updated = true;
            }
            
            // Remove edges that involve this app
            $originalEdgeCount = count($edgesLayout);
            $edgesLayout = array_filter($edgesLayout, function($edge) use ($appId) {
                return $edge['source'] !== (string)$appId && $edge['target'] !== (string)$appId;
            });
            
            if (count($edgesLayout) !== $originalEdgeCount) {
                $updated = true;
            }
            
            // Update total nodes count in stream config
            if (isset($streamConfig['totalNodes'])) {
                $streamConfig['totalNodes'] = count($nodesLayout);
                $updated = true;
            }
            
            // Update total edges count in stream config
            if (isset($streamConfig['totalEdges'])) {
                $streamConfig['totalEdges'] = count($edgesLayout);
                $updated = true;
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $layout->update([
                    'nodes_layout' => $nodesLayout,
                    'edges_layout' => array_values($edgesLayout), // Re-index array
                    'stream_config' => $streamConfig,
                ]);
            }
        }
        
        // Clear all caches since multiple layouts may have been updated
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
     * Get layout data for a specific stream
     */
    public function getLayoutData(string $streamName): ?array
    {
        $layout = $this->findByStreamName($streamName);
        
        return $layout ? [
            'nodes_layout' => $layout->nodesLayout,
            'edges_layout' => $layout->edgesLayout,
            'stream_config' => $layout->streamConfig,
        ] : null;
    }

    /**
     * Get statistics about stream layouts
     */
    public function getStatistics(): array
    {
        $cacheKey = CacheConfig::buildKey('stream_layout', 'statistics');
        $cacheTTL = CacheConfig::getTTL('statistics');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() {
                $totalLayouts = $this->model->count();
                
                if ($totalLayouts === 0) {
                    return [
                        'total_layouts' => 0,
                        'avg_nodes_per_layout' => 0,
                        'avg_edges_per_layout' => 0,
                        'total_nodes' => 0,
                        'total_edges' => 0,
                        'largest_layout_nodes' => 0,
                        'largest_layout_edges' => 0,
                    ];
                }

                $layouts = $this->model->all();
                $totalNodes = 0;
                $totalEdges = 0;
                $maxNodes = 0;
                $maxEdges = 0;

                foreach ($layouts as $layout) {
                    $nodeCount = count($layout->nodes_layout ?? []);
                    $edgeCount = count($layout->edges_layout ?? []);
                    
                    $totalNodes += $nodeCount;
                    $totalEdges += $edgeCount;
                    $maxNodes = max($maxNodes, $nodeCount);
                    $maxEdges = max($maxEdges, $edgeCount);
                }

                return [
                    'total_layouts' => $totalLayouts,
                    'avg_nodes_per_layout' => round($totalNodes / $totalLayouts, 2),
                    'avg_edges_per_layout' => round($totalEdges / $totalLayouts, 2),
                    'total_nodes' => $totalNodes,
                    'total_edges' => $totalEdges,
                    'largest_layout_nodes' => $maxNodes,
                    'largest_layout_edges' => $maxEdges,
                ];
            }
        );
    }    /**
     * Get streams with most nodes
     */
    public function getStreamsWithMostNodes(int $limit = 10): Collection
    {
        $cacheKey = CacheConfig::buildKey('stream_layout', 'most_nodes', $limit);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($limit) {
                return $this->model->all()
                    ->map(function($layout) {
                        return [
                            'stream_name' => $layout->stream_name,
                            'node_count' => count($layout->nodes_layout ?? []),
                            'edge_count' => count($layout->edges_layout ?? []),
                        ];
                    })
                    ->sortByDesc('node_count')
                    ->take($limit)
                    ->values();
            }
        );
    }

    /**
     * Get streams with most edges
     */
    public function getStreamsWithMostEdges(int $limit = 10): Collection
    {
        $cacheKey = CacheConfig::buildKey('stream_layout', 'most_edges', $limit);
        $cacheTTL = CacheConfig::getTTL('default');
        
        return Cache::remember(
            $cacheKey,
            $cacheTTL,
            function() use ($limit) {
                return $this->model->all()
                    ->map(function($layout) {
                        return [
                            'stream_name' => $layout->stream_name,
                            'node_count' => count($layout->nodes_layout ?? []),
                            'edge_count' => count($layout->edges_layout ?? []),
                        ];
                    })
                    ->sortByDesc('edge_count')
                    ->take($limit)
                    ->values();
            }
        );
    }

    /**
     * Clear all caches related to stream layouts
     */
    private function clearCaches(): void
    {
        Cache::forget(CacheConfig::buildKey('stream_layout', 'all'));
        Cache::forget(CacheConfig::buildKey('stream_layout', 'statistics'));
        
        // Clear cached lists
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget(CacheConfig::buildKey('stream_layout', 'most_nodes', $i));
            Cache::forget(CacheConfig::buildKey('stream_layout', 'most_edges', $i));
        }
    }
}