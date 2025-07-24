<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Models\StreamLayout;
use Illuminate\Support\Collection;

class DiagramService
{
    private const ALLOWED_STREAMS = ['sp', 'mi', 'ssk', 'moneter', 'market'];

    /**
     * Validate stream name
     */
    public function validateStreamName(string $streamName): bool
    {
        return in_array(strtolower($streamName), array_map('strtolower', self::ALLOWED_STREAMS));
    }

    /**
     * Get allowed streams
     */
    public function getAllowedStreams(): array
    {
        return self::ALLOWED_STREAMS;
    }

    /**
     * Get stream model by name
     */
    public function getStream(string $streamName): ?Stream
    {
        return Stream::where('stream_name', $streamName)->first();
    }

    /**
     * Get apps in a specific stream
     */
    public function getStreamApps(Stream $stream): Collection
    {
        return App::where('stream_id', $stream->getAttribute('stream_id'))->get();
    }

    /**
     * Get connected external apps for a stream
     */
    public function getConnectedExternalApps(array $homeAppIds): Collection
    {
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();

        // Get apps that have integrations FROM home stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();

        // Combine and remove home stream app IDs to get only external apps
        $externalAppIds = $sourceAppIds->merge($targetAppIds)
            ->diff($homeAppIds)
            ->unique()
            ->values();

        return App::whereIn('app_id', $externalAppIds)->with('stream')->get();
    }

    /**
     * Get integrations involving specific apps
     */
    public function getIntegrations(array $appIds, array $homeAppIds = []): Collection
    {
        $integrations = AppIntegration::whereIn('source_app_id', $appIds)
            ->whereIn('target_app_id', $appIds)
            ->with(['connectionType', 'sourceApp', 'targetApp'])
            ->get();

        // If homeAppIds provided, filter to only include connections involving home stream apps
        if (!empty($homeAppIds)) {
            $integrations = $integrations->filter(function ($integration) use ($homeAppIds) {
                $sourceIsHome = in_array($integration->source_app_id, $homeAppIds);
                $targetIsHome = in_array($integration->target_app_id, $homeAppIds);
                
                return $sourceIsHome || $targetIsHome;
            });
        }

        return $integrations;
    }

    /**
     * Get saved layout for a stream
     */
    public function getSavedLayout(string $streamName): ?array
    {
        $streamLayout = StreamLayout::where('stream_name', $streamName)->first();
        
        if (!$streamLayout) {
            return null;
        }

        return [
            'nodes_layout' => $streamLayout->nodes_layout,
            'edges_layout' => $streamLayout->edges_layout,
            'stream_config' => $streamLayout->stream_config,
        ];
    }

    /**
     * Save layout configuration
     */
    public function saveLayout(string $streamName, array $nodesLayout, array $streamConfig, array $edgesLayout = []): void
    {
        if (!$this->validateStreamName($streamName)) {
            throw new \InvalidArgumentException('Invalid stream name');
        }

        StreamLayout::saveLayout($streamName, $nodesLayout, $streamConfig, $edgesLayout);
    }

    /**
     * Remove duplicate nodes by ID
     */
    public function removeDuplicateNodes(array $nodes): array
    {
        $uniqueNodes = [];
        $seenIds = [];
        
        foreach ($nodes as $node) {
            $nodeId = $node['id'];
            if (!in_array($nodeId, $seenIds)) {
                $uniqueNodes[] = $node;
                $seenIds[] = $nodeId;
            }
        }
        
        return $uniqueNodes;
    }

    /**
     * Get Vue Flow data for a specific stream
     */
    public function getVueFlowData(string $streamName, bool $isUserView = false): array
    {
        // Validate stream
        if (!$this->validateStreamName($streamName)) {
            return [
                'nodes' => [],
                'edges' => [],
                'error' => "Stream '{$streamName}' not found"
            ];
        }

        $stream = $this->getStream($streamName);
        if (!$stream) {
            return [
                'nodes' => [],
                'edges' => [],
                'error' => "Stream '{$streamName}' not found"
            ];
        }

        // Get apps in this stream
        $streamApps = $this->getStreamApps($stream);
        $streamAppIds = $streamApps->pluck('app_id')->toArray();

        // Get all integrations involving these stream apps
        $integrations = AppIntegration::with(['sourceApp', 'targetApp', 'connectionType'])
            ->where(function ($query) use ($streamAppIds) {
                $query->whereIn('source_app_id', $streamAppIds)
                      ->orWhereIn('target_app_id', $streamAppIds);
            })
            ->get();

        // Get connected external apps
        $externalApps = $this->getConnectedExternalApps($streamAppIds);

        // Combine all apps
        $allApps = $streamApps->merge($externalApps)->keyBy('app_id');

        // Create nodes using transformer
        $nodeTransformer = new \App\Services\NodeTransformer();
        $nodes = [];
        
        // Add stream parent node
        $nodes[] = $nodeTransformer->createStreamNode($streamName, !$isUserView);
        
        // Add stream apps
        $streamNodeApps = $nodeTransformer->transformHomeStreamApps($streamApps, $streamName, !$isUserView);
        $nodes = array_merge($nodes, $streamNodeApps);
        
        // Add external apps
        $externalNodeApps = $nodeTransformer->transformExternalApps($externalApps, !$isUserView);
        $nodes = array_merge($nodes, $externalNodeApps);

        // Create edges using transformer
        $edgeTransformer = new \App\Services\EdgeTransformer();
        $edges = $isUserView 
            ? $edgeTransformer->transformForUser($integrations)
            : $edgeTransformer->transformForAdmin($integrations);

        // Get saved layout
        $savedLayout = StreamLayout::where('stream_name', $streamName)->first();

        // Apply saved positions if available
        if ($savedLayout && $savedLayout->nodes_layout) {
            foreach ($nodes as &$node) {
                if (isset($savedLayout->nodes_layout[$node['id']])) {
                    $savedPosition = $savedLayout->nodes_layout[$node['id']];
                    $node['position'] = [
                        'x' => $savedPosition['x'] ?? $node['position']['x'],
                        'y' => $savedPosition['y'] ?? $node['position']['y']
                    ];
                }
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'savedLayout' => $savedLayout,
        ];
    }
}
