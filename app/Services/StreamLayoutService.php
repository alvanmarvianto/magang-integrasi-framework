<?php

namespace App\Services;

use App\DTOs\DiagramEdgeDTO;
use App\DTOs\StreamLayoutDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Models\StreamLayout;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StreamLayoutService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {}

    /**
     * Update stream layouts when integration data changes
     */
    public function updateStreamLayoutsForIntegration(AppIntegration $integration): void
    {
        // Load relationships including the stream for each app
        $integration->load(['sourceApp.stream', 'targetApp.stream', 'connectionType']);
        
        // Get all stream layouts
        $layouts = $this->streamLayoutRepository->getAll();
        
        foreach ($layouts as $layoutDto) {
            $updated = false;
            $edgesLayout = $layoutDto->edgesLayout;
            $streamName = $layoutDto->streamName;
            
            // Check if this integration involves apps from this stream
            $sourceAppStream = $integration->sourceApp->stream->stream_name ?? null;
            $targetAppStream = $integration->targetApp->stream->stream_name ?? null;
            
            // Only process if at least one app belongs to this stream
            $shouldProcessForThisStream = ($sourceAppStream === $streamName || $targetAppStream === $streamName);
            
            if (!$shouldProcessForThisStream) {
                continue;
            }
            
            // First, try to update existing edges that match this integration
            $foundExistingEdge = false;
            foreach ($edgesLayout as &$edge) {
                // Check if this edge represents the integration by integration_id
                $edgeIntegrationId = null;
                
                // Handle both old and new data formats
                if (isset($edge['data']) && isset($edge['data']['integration_id'])) {
                    $edgeIntegrationId = $edge['data']['integration_id'];
                }
                
                // For edges without integration_id, try to match by source and target apps
                $matchesByApps = false;
                if ($edgeIntegrationId === null) {
                    // Check both directions: edge could be source->target or target->source
                    $matchesByApps = isset($edge['source']) && isset($edge['target']) && (
                        // Direction 1: edge source matches integration source, edge target matches integration target
                        ($edge['source'] == $integration->getAttribute('source_app_id') && 
                         $edge['target'] == $integration->getAttribute('target_app_id')) ||
                        // Direction 2: edge source matches integration target, edge target matches integration source  
                        ($edge['source'] == $integration->getAttribute('target_app_id') && 
                         $edge['target'] == $integration->getAttribute('source_app_id'))
                    );
                }
                
                if ($edgeIntegrationId == $integration->getKey() || $matchesByApps) {
                    // Update the existing edge
                    $this->updateExistingEdge($edge, $integration);
                    $updated = true;
                    $foundExistingEdge = true;
                    break; // Exit loop since we found the edge
                }
            }
            
            // If no existing edge was found, add a new edge for this integration
            if (!$foundExistingEdge) {
                $newEdgeDto = $this->createNewEdgeFromIntegration($integration);
                $edgesLayout[] = $newEdgeDto->toArray();
                $updated = true;
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamName,
                    $layoutDto->nodesLayout,
                    $edgesLayout,
                    $layoutDto->streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }

    /**
     * Synchronize stream layout edges with current AppIntegration data
     */
    public function synchronizeStreamLayoutEdges(string $streamName): int
    {
        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if (!$layoutDto) {
            return 0;
        }

        // Get current stream and its apps
        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return 0;
        }

        $homeStreamApps = App::where('stream_id', $stream->getAttribute('stream_id'))->get();
        $homeAppIds = $homeStreamApps->pluck('app_id')->toArray();

        // Get connected external apps
        $connectedAppIds = collect();
        
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);
        
        // Get apps that have integrations FROM home stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);
        
        // All valid app IDs for this stream (include home stream apps + connected external apps)
        $allValidAppIds = $connectedAppIds->merge($homeAppIds)->unique()->values()->toArray();

        // Get current integrations involving these apps
        $integrations = AppIntegration::whereIn('source_app_id', $allValidAppIds)
            ->whereIn('target_app_id', $allValidAppIds)
            ->with(['connectionType', 'sourceApp', 'targetApp'])
            ->get();

        // Get existing edges layout to preserve handle positions
        $existingEdgesLayout = $layoutDto->edgesLayout;
        $existingEdgesMap = [];
        foreach ($existingEdgesLayout as $edge) {
            $edgeId = $edge['id'] ?? '';
            $existingEdgesMap[$edgeId] = $edge;
        }

        // Build new edges layout based on current integrations
        $newEdgesLayout = [];
        foreach ($integrations as $integration) {
            // Only include edges where at least one end is a home stream app
            $sourceIsHome = in_array($integration->getAttribute('source_app_id'), $homeAppIds);
            $targetIsHome = in_array($integration->getAttribute('target_app_id'), $homeAppIds);
            
            if ($sourceIsHome || $targetIsHome) {
                $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
                $existingEdge = $existingEdgesMap[$edgeId] ?? null;

                // Create new edge data with current integration data
                $newEdgeDto = $this->createNewEdgeFromIntegration($integration);
                $newEdge = $newEdgeDto->toArray();
                
                // Preserve handle positions if they exist
                if ($existingEdge) {
                    $newEdge['sourceHandle'] = $existingEdge['sourceHandle'] ?? null;
                    $newEdge['targetHandle'] = $existingEdge['targetHandle'] ?? null;
                }

                $newEdgesLayout[] = $newEdge;
            }
        }

        // Update stream config
        $streamConfig = $layoutDto->streamConfig;
        $streamConfig['totalEdges'] = count($newEdgesLayout);
        $streamConfig['lastUpdated'] = now()->toISOString();

        // Count of valid nodes (excluding stream parent node)
        $nodesLayout = $layoutDto->nodesLayout;
        $validNodesCount = count(array_filter($nodesLayout, function($key) use ($streamName) {
            return $key !== $streamName && $key !== 'sp'; // Exclude stream parent nodes
        }, ARRAY_FILTER_USE_KEY));
        
        $streamConfig['totalNodes'] = $validNodesCount;

        // Update the layout
        $updatedDto = StreamLayoutDTO::forSave(
            $layoutDto->streamName,
            $nodesLayout,
            $newEdgesLayout,
            $streamConfig
        );
        $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);

        Log::info("Synchronized {$streamName} stream layout: " . count($newEdgesLayout) . " edges updated");

        return count($newEdgesLayout);
    }

    /**
     * Remove integration edges from all stream layouts
     */
    public function removeIntegrationFromLayouts(AppIntegration $integration): void
    {
        $layouts = $this->streamLayoutRepository->getAll();
        
        foreach ($layouts as $layoutDto) {
            $edgesLayout = $layoutDto->edgesLayout;
            $originalCount = count($edgesLayout);
            
            // Remove edges that involve this integration by integration_id
            $edgesLayout = array_filter($edgesLayout, function($edge) use ($integration) {
                $edgeIntegrationId = null;
                
                // Handle both old and new data formats
                if (isset($edge['data']) && isset($edge['data']['integration_id'])) {
                    $edgeIntegrationId = $edge['data']['integration_id'];
                }
                
                // Keep edges that don't match this integration
                return $edgeIntegrationId != $integration->getKey();
            });
            
            // Update stream config if edges were removed
            if (count($edgesLayout) !== $originalCount) {
                $streamConfig = $layoutDto->streamConfig;
                if (isset($streamConfig['totalEdges'])) {
                    $streamConfig['totalEdges'] = count($edgesLayout);
                }
                
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamName,
                    $layoutDto->nodesLayout,
                    array_values($edgesLayout), // Re-index array
                    $streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }

    /**
     * Update an existing edge with integration data
     */
    private function updateExistingEdge(array &$edge, AppIntegration $integration): void
    {
        // Update the edge completely with fresh integration data
        $edge['source'] = (string)$integration->getAttribute('source_app_id');
        $edge['target'] = (string)$integration->getAttribute('target_app_id');
        $edge['id'] = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
        
        // Update the data object with the new standardized format
        $edge['data'] = [
            'integration_id' => $integration->getKey(),
            'source_app_id' => $integration->getAttribute('source_app_id'),
            'target_app_id' => $integration->getAttribute('target_app_id'),
            'connection_type' => $integration->connectionType->type_name ?? 'direct',
            'connection_type_id' => $integration->getAttribute('connection_type_id'),
            'inbound' => $integration->getAttribute('inbound'),
            'outbound' => $integration->getAttribute('outbound'),
            'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
            'direction' => $integration->getAttribute('direction'),
            'source_app_name' => $integration->sourceApp->app_name ?? '',
            'target_app_name' => $integration->targetApp->app_name ?? '',
            // Keep legacy format for backward compatibility
            'label' => $integration->connectionType->type_name ?? 'direct',
            'sourceApp' => [
                'app_id' => $integration->getAttribute('source_app_id'),
                'app_name' => $integration->sourceApp->app_name ?? ''
            ],
            'targetApp' => [
                'app_id' => $integration->getAttribute('target_app_id'),
                'app_name' => $integration->targetApp->app_name ?? ''
            ]
        ];
        
        // Update the edge style based on connection type
        $connectionType = $integration->connectionType->type_name ?? 'direct';
        $edgeColor = $integration->connectionType->color ?? '#000000';
        $edge['style'] = [
            'stroke' => $edgeColor,
            'strokeWidth' => 2
        ];
        $edge['type'] = 'smoothstep';
    }
    
    /**
     * Create a new edge from integration data
     */
    private function createNewEdgeFromIntegration(AppIntegration $integration): DiagramEdgeDTO
    {
        $connectionType = $integration->connectionType->type_name ?? 'direct';
        $edgeColor = $integration->connectionType->color ?? '#000000';
        
        $edgeData = [
            'id' => $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
            'source' => (string)$integration->getAttribute('source_app_id'),
            'target' => (string)$integration->getAttribute('target_app_id'),
            'type' => 'smoothstep',
            'color' => $edgeColor,
            'style' => [
                'stroke' => $edgeColor,
                'strokeWidth' => 2
            ],
            'data' => [
                'integration_id' => $integration->getKey(),
                'source_app_id' => $integration->getAttribute('source_app_id'),
                'target_app_id' => $integration->getAttribute('target_app_id'),
                'connection_type' => $connectionType,
                'connection_type_id' => $integration->getAttribute('connection_type_id'),
                'inbound' => $integration->getAttribute('inbound'),
                'outbound' => $integration->getAttribute('outbound'),
                'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                'direction' => $integration->getAttribute('direction'),
                'source_app_name' => $integration->sourceApp->app_name ?? '',
                'target_app_name' => $integration->targetApp->app_name ?? '',
                // Keep legacy format for backward compatibility
                'label' => $connectionType,
                'sourceApp' => [
                    'app_id' => $integration->getAttribute('source_app_id'),
                    'app_name' => $integration->sourceApp->app_name ?? ''
                ],
                'targetApp' => [
                    'app_id' => $integration->getAttribute('target_app_id'),
                    'app_name' => $integration->targetApp->app_name ?? ''
                ]
            ],
            'label' => $connectionType,
            'connection_type' => $connectionType,
            'direction' => $integration->getAttribute('direction') ?? 'one_way'
        ];

        return DiagramEdgeDTO::fromArray($edgeData);
    }

    /**
     * Get edge color based on connection type (Legacy method - now uses database colors)
     * @deprecated Use database color from connection_types table instead
     */
    private function getEdgeColorByConnectionType(string $connectionType): string
    {
        // Fallback colors if database color is not available
        switch (strtolower($connectionType)) {
            case 'soa':
                return '#02a330';
            case 'sftp':
                return '#002ac0';
            case 'soa-sftp':
                return '#6b7280';
            case 'direct':
            default:
                return '#000000';
        }
    }

    /**
     * Synchronize connection type colors in stream layout
     * Updates edge colors in saved layouts when connection type colors change
     */
    public function synchronizeConnectionTypeColors(string $streamName): int
    {
        $layout = StreamLayout::where('stream_name', $streamName)->first();
        
        if (!$layout || !$layout->edges_layout) {
            return 0;
        }
        
        $edgesLayout = $layout->edges_layout;
        $colorsSynced = 0;
        
        // Get all connection types with their current colors
        $connectionTypes = \App\Models\ConnectionType::all()->keyBy('type_name');
        
        foreach ($edgesLayout as $index => $edge) {
            if (!isset($edge['data']['connection_type'])) {
                continue;
            }
            
            $connectionTypeName = $edge['data']['connection_type'];
            $connectionType = $connectionTypes->get($connectionTypeName);
            
            if (!$connectionType) {
                continue;
            }
            
            $currentColor = $connectionType->color ?? '#000000';
            $savedColor = $edge['data']['color'] ?? null;
            $styleColor = $edge['style']['stroke'] ?? null;
            
            // Update if colors don't match
            if ($savedColor !== $currentColor || $styleColor !== $currentColor) {
                $edgesLayout[$index]['data']['color'] = $currentColor;
                $edgesLayout[$index]['style']['stroke'] = $currentColor;
                
                // Update marker colors if they exist
                if (isset($edgesLayout[$index]['markerEnd']['color'])) {
                    $edgesLayout[$index]['markerEnd']['color'] = $currentColor;
                }
                if (isset($edgesLayout[$index]['markerStart']['color'])) {
                    $edgesLayout[$index]['markerStart']['color'] = $currentColor;
                }
                
                $colorsSynced++;
            }
        }
        
        if ($colorsSynced > 0) {
            $layout->edges_layout = $edgesLayout;
            $layout->save();
        }
        
        return $colorsSynced;
    }
}
