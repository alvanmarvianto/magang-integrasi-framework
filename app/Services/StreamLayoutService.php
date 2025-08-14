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
    $integration->load(['sourceApp.stream', 'targetApp.stream', 'connections.connectionType']);
        
        // Get all stream layouts
        $layouts = $this->streamLayoutRepository->getAll();
        
        foreach ($layouts as $layoutDto) {
            $updated = false;
            $edgesLayout = $layoutDto->edgesLayout;
            
            // Get stream name from database using stream ID
            $stream = \App\Models\Stream::find($layoutDto->streamId);
            $streamName = $stream ? $stream->stream_name : '';
            
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
                $edge = $newEdgeDto->toArray();
                if (!isset($edge['style']) || !is_array($edge['style'])) {
                    $edge['style'] = [];
                }
                $edge['style']['stroke'] = '#000000';
                $edge['style']['strokeWidth'] = 2;
                unset($edge['markerEnd'], $edge['markerStart']);
                $edgesLayout[] = $edge;
                $updated = true;
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamId,
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
            ->with(['connections.connectionType', 'sourceApp', 'targetApp'])
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
                
                // Force black color and remove arrows for refreshed layout
                if (!isset($newEdge['style']) || !is_array($newEdge['style'])) {
                    $newEdge['style'] = [];
                }
                $newEdge['style']['stroke'] = '#000000';
                $newEdge['style']['strokeWidth'] = 2;
                unset($newEdge['markerEnd'], $newEdge['markerStart']);
                // Keep type smoothstep but no markers means no arrows
                
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
    // Set override flag so renderers/transformers honor black/no-arrow style
    $streamConfig['forceEdgeBlackNoArrow'] = true;

        // Count of valid nodes (excluding stream parent node)
        $nodesLayout = $layoutDto->nodesLayout;
        
        // Get the stream name from database using stream ID
        $stream = \App\Models\Stream::find($layoutDto->streamId);
        $streamName = $stream ? $stream->stream_name : '';
        
        $validNodesCount = count(array_filter($nodesLayout, function($key) use ($streamName) {
            // Exclude stream parent nodes - these use the clean stream name as the node ID
            $cleanStreamName = strtolower(trim($streamName));
            if (str_starts_with($cleanStreamName, 'stream ')) {
                $cleanStreamName = substr($cleanStreamName, 7);
            }
            return $key !== $streamName && $key !== $cleanStreamName;
        }, ARRAY_FILTER_USE_KEY));
        
        $streamConfig['totalNodes'] = $validNodesCount;

        // Update the layout
        $updatedDto = StreamLayoutDTO::forSave(
            $layoutDto->streamId,
            $nodesLayout,
            $newEdgesLayout,
            $streamConfig
        );
        $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);

        return count($newEdgesLayout);
    }

    /**
     * Ensure the stream parent node in the saved layout uses DB-cased stream name in its data fields
     */
    public function synchronizeStreamParentNodeLabel(string $streamName): int
    {
        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if (!$layoutDto) {
            return 0;
        }

        // Resolve the stream by exact name (DB casing)
        $stream = \App\Models\Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return 0;
        }

        $dbName = $stream->stream_name;
        $nodesLayout = $layoutDto->nodesLayout;
        $updated = 0;

        // Parent node id is normalized (lowercase without optional "Stream " prefix)
        $cleanName = strtolower(trim($dbName));
        if (str_starts_with($cleanName, 'stream ')) {
            $cleanName = substr($cleanName, 7);
        }

        foreach ($nodesLayout as $key => &$node) {
            $nodeId = (string)($node['id'] ?? $key);
            $isParent = ($nodeId === $dbName) || ($nodeId === $cleanName);
            if (!$isParent) {
                continue;
            }

            if (!isset($node['data']) || !is_array($node['data'])) {
                $node['data'] = [];
            }

            $before = json_encode($node['data']);
            $node['data']['label'] = $dbName;
            $node['data']['app_name'] = $dbName;
            $node['data']['stream_name'] = $dbName;
            $node['data']['lingkup'] = $dbName;
            $after = json_encode($node['data']);

            if ($before !== $after) {
                $updated++;
            }
        }
        unset($node);

        if ($updated > 0) {
            $updatedDto = StreamLayoutDTO::forSave(
                $layoutDto->streamId,
                $nodesLayout,
                $layoutDto->edgesLayout,
                $layoutDto->streamConfig
            );
            $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
        }

        return $updated;
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
                    $layoutDto->streamId,
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
        
        // Build connection types summary from new connections relation
        $types = $integration->relationLoaded('connections')
            ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)
                ->filter()
                ->unique()
                ->values()
                ->toArray()
            : [];
        $connectionLabel = empty($types) ? 'direct' : implode(' / ', $types);

        // Update the data object with the new standardized format
        $edge['data'] = [
            'integration_id' => $integration->getKey(),
            'source_app_id' => $integration->getAttribute('source_app_id'),
            'target_app_id' => $integration->getAttribute('target_app_id'),
            // Backward-compatible single label, plus full list
            'connection_type' => $connectionLabel,
            'connection_types' => array_map(fn($name) => ['name' => $name], $types),
            'source_app_name' => $integration->sourceApp->app_name ?? '',
            'target_app_name' => $integration->targetApp->app_name ?? '',
            // Keep legacy format for backward compatibility
            'label' => $connectionLabel,
            'sourceApp' => [
                'app_id' => $integration->getAttribute('source_app_id'),
                'app_name' => $integration->sourceApp->app_name ?? ''
            ],
            'targetApp' => [
                'app_id' => $integration->getAttribute('target_app_id'),
                'app_name' => $integration->targetApp->app_name ?? ''
            ],
            // Persist black color in data so UI doesn't reuse old colors
            'color' => '#000000'
        ];
        
        // Force black color and no arrows
        $edge['style'] = [
            'stroke' => '#000000',
            'strokeWidth' => 2
        ];
        // Legacy root-level color for some consumers
        $edge['color'] = '#000000';
        unset($edge['markerEnd'], $edge['markerStart']);
        $edge['type'] = 'smoothstep';
    }
    
    /**
     * Create a new edge from integration data
     */
    private function createNewEdgeFromIntegration(AppIntegration $integration): DiagramEdgeDTO
    {
    $types = $integration->relationLoaded('connections')
        ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)
            ->filter()
            ->unique()
            ->values()
            ->toArray()
        : [];
    $connectionType = empty($types) ? 'direct' : implode(' / ', $types);
    // Admin saved layout should not store connection-type color
    $edgeColor = '#000000';
        
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
                'connection_types' => array_map(fn($name) => ['name' => $name], $types),
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
        ],
        'color' => '#000000'
            ],
            'label' => $connectionType,
            'connection_type' => $connectionType,
            // no direction in new model
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
        $layoutDTO = $this->streamLayoutRepository->findByStreamName($streamName);
        
        if (!$layoutDTO || !$layoutDTO->edgesLayout) {
            return 0;
        }
        
        $edgesLayout = $layoutDTO->edgesLayout;
        $colorsSynced = 0;
        
        // Get all connection types with their current colors
    $connectionTypes = \App\Models\ConnectionType::all();
        // Build lookup maps: by ID and by lowercase name (to handle casing differences)
        $connectionTypesById = $connectionTypes->keyBy('connection_type_id');
        $connectionTypesByName = $connectionTypes->mapWithKeys(function ($ct) {
            return [strtolower($ct->type_name) => $ct];
        });
        
        foreach ($edgesLayout as $index => $edge) {
            // Extract connection type info from either new (data.*) or legacy (root) shapes
            $ctId = $edge['data']['connection_type_id'] ?? null;
            $ctName = $edge['data']['connection_type'] ?? ($edge['connection_type'] ?? ($edge['label'] ?? null));

            if (!$ctId && !$ctName) {
                continue;
            }

            // Prefer ID if available for exact match; fall back to case-insensitive name
            $connectionTypeModel = null;
            if ($ctId) {
                $connectionTypeModel = $connectionTypesById->get($ctId);
            }
            if (!$connectionTypeModel && $ctName) {
                $connectionTypeModel = $connectionTypesByName->get(strtolower($ctName));
            }

            if (!$connectionTypeModel) {
                // Fallback: resolve via integration (handles renamed connection types)
                $integration = null;
                $edgeSource = $edge['source'] ?? null;
                $edgeTarget = $edge['target'] ?? null;
                $edgeIntegrationId = $edge['data']['integration_id'] ?? null;

                if ($edgeIntegrationId) {
                    $integration = \App\Models\AppIntegration::with('connections.connectionType')
                        ->find($edgeIntegrationId);
                } elseif ($edgeSource && $edgeTarget) {
                    // Try both directions
                    $integration = \App\Models\AppIntegration::with('connections.connectionType')
                        ->where(function($q) use ($edgeSource, $edgeTarget) {
                            $q->where(function($q2) use ($edgeSource, $edgeTarget) {
                                $q2->where('source_app_id', $edgeSource)
                                   ->where('target_app_id', $edgeTarget);
                            })->orWhere(function($q3) use ($edgeSource, $edgeTarget) {
                                $q3->where('source_app_id', $edgeTarget)
                                   ->where('target_app_id', $edgeSource);
                            });
                        })->orderByDesc('integration_id')->first();
                }

                if ($integration && $integration->relationLoaded('connections') && $integration->connections->isNotEmpty()) {
                    $first = $integration->connections->firstWhere('connectionType', '!=', null) ?? $integration->connections->first();
                    if ($first && $first->connectionType) {
                        $connectionTypeModel = $first->connectionType;
                        // Also set ctId to be used below
                        $ctId = $first->connection_type_id;
                    }
                } else {
                    // Still nothing to sync
                    continue;
                }
            }

            $currentColor = $connectionTypeModel->color ?? '#000000';

            // Ensure arrays exist before writing
            if (!isset($edgesLayout[$index]['style']) || !is_array($edgesLayout[$index]['style'])) {
                $edgesLayout[$index]['style'] = [];
            }
            if (!isset($edgesLayout[$index]['data']) || !is_array($edgesLayout[$index]['data'])) {
                $edgesLayout[$index]['data'] = [];
            }

            $savedColor = $edgesLayout[$index]['data']['color'] ?? null;
            $styleColor = $edgesLayout[$index]['style']['stroke'] ?? null;
            $rootColor = $edgesLayout[$index]['color'] ?? null;

            // Update if colors or type names don't match
            $currentName = $connectionTypeModel->type_name;
            $savedNameData = $edgesLayout[$index]['data']['connection_type'] ?? null;
            $savedNameRoot = $edgesLayout[$index]['connection_type'] ?? null;
            $savedLabel = $edgesLayout[$index]['label'] ?? null;

            $needsNameUpdate = ($savedNameData && strtolower($savedNameData) !== strtolower($currentName))
                || ($savedNameRoot && strtolower($savedNameRoot) !== strtolower($currentName))
                || ($savedLabel && strtolower($savedLabel) !== strtolower($currentName));

            if ($savedColor !== $currentColor || $styleColor !== $currentColor || $rootColor !== $currentColor || $needsNameUpdate) {
                $edgesLayout[$index]['data']['color'] = $currentColor;
                $edgesLayout[$index]['style']['stroke'] = $currentColor;
                // Some edges also keep a root-level color field
                $edgesLayout[$index]['color'] = $currentColor;
                // Update connection type name/labels to current DB value for consistency
                $edgesLayout[$index]['data']['connection_type'] = $currentName;
                // Ensure connection_type_id is set so future syncs are exact
                if (isset($ctId) && $ctId) {
                    $edgesLayout[$index]['data']['connection_type_id'] = $ctId;
                } elseif (isset($connectionTypeModel->connection_type_id)) {
                    $edgesLayout[$index]['data']['connection_type_id'] = $connectionTypeModel->connection_type_id;
                }
                $edgesLayout[$index]['connection_type'] = $currentName;
                $edgesLayout[$index]['label'] = $currentName;

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
            // Save the updated layout using the repository
            $this->streamLayoutRepository->saveLayoutById(
                $layoutDTO->streamId, 
                $layoutDTO->nodesLayout, 
                $edgesLayout, 
                $layoutDTO->streamConfig
            );
        }
        
        return $colorsSynced;
    }

    /**
     * Synchronize stream colors for app nodes in stream layout
     * Updates node colors when stream colors change
     */
    public function synchronizeStreamColors(string $streamName): int
    {
        $layoutDTO = $this->streamLayoutRepository->findByStreamName($streamName);
        
        if (!$layoutDTO || !$layoutDTO->nodesLayout) {
            return 0;
        }
        
        $nodesLayout = $layoutDTO->nodesLayout;
        $colorsSynced = 0;
        
        // Get all streams with their current colors
        $streams = \App\Models\Stream::all();
        // Build maps: exact by name and normalized (lowercase + without 'stream ' prefix)
        $streamsByExact = $streams->keyBy('stream_name');
        $streamsByNormalized = $streams->mapWithKeys(function ($s) {
            $name = $s->stream_name;
            $norm = strtolower(trim($name));
            if (str_starts_with($norm, 'stream ')) {
                $norm = substr($norm, 7);
            }
            return [$norm => $s];
        });
        
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }

        foreach ($nodesLayout as $key => $node) {
            // Determine node id regardless of shape (keyed map or indexed array)
            $nodeIdStr = (string)($node['id'] ?? $key);

            $nodeStreamName = null;
            $isParentNode = false;

            // Detect parent node by either raw or cleaned stream name
            if ($nodeIdStr === $streamName || $nodeIdStr === $cleanStreamName) {
                $nodeStreamName = $streamName;
                $isParentNode = true;
            }

            // Prefer data.stream_name if present
            if (!$nodeStreamName && isset($node['data']['stream_name'])) {
                $nodeStreamName = (string)$node['data']['stream_name'];
            }

            // If still unknown and node id looks like numeric app id, resolve via DB
            if (!$nodeStreamName && is_numeric($nodeIdStr)) {
                $app = \App\Models\App::with('stream')->find((int)$nodeIdStr);
                if ($app && $app->stream) {
                    $nodeStreamName = $app->stream->stream_name;
                }
            }

            // Legacy fallback: try to resolve by data.app_name
            if (!$nodeStreamName && isset($node['data']['app_name']) && is_string($node['data']['app_name'])) {
                $byName = \App\Models\App::with('stream')->where('app_name', $node['data']['app_name'])->first();
                if ($byName && $byName->stream) {
                    $nodeStreamName = $byName->stream->stream_name;
                }
            }

            // Legacy fallback: try to resolve by node id as app_name
            if (!$nodeStreamName && !is_numeric($nodeIdStr)) {
                $byIdName = \App\Models\App::with('stream')->where('app_name', $nodeIdStr)->first();
                if ($byIdName && $byIdName->stream) {
                    $nodeStreamName = $byIdName->stream->stream_name;
                }
            }

            if (!$nodeStreamName) {
                continue;
            }

            // Resolve stream model and color
            $streamModel = $streamsByExact->get($nodeStreamName);
            if (!$streamModel) {
                $norm = strtolower(trim($nodeStreamName));
                if (str_starts_with($norm, 'stream ')) {
                    $norm = substr($norm, 7);
                }
                $streamModel = $streamsByNormalized->get($norm);
            }

            if (!$streamModel || !$streamModel->color) {
                continue;
            }

            $currentColor = $streamModel->color;

            // Pick a writable key; if map is keyed by id, update that; else update current index
            $writeKey = array_key_exists($nodeIdStr, $nodesLayout) ? $nodeIdStr : $key;

            // Ensure style array exists
            if (!isset($nodesLayout[$writeKey]['style']) || !is_array($nodesLayout[$writeKey]['style'])) {
                $nodesLayout[$writeKey]['style'] = [];
            }

            $borderStr = $nodesLayout[$writeKey]['style']['border'] ?? null;
            $savedBorderColor = $nodesLayout[$writeKey]['style']['borderColor'] ?? null;

            $colorNeedsUpdate = false;

            // Always enforce shorthand border color to DB value
            $desiredBorderStr = "2px solid {$currentColor}";
            if ($borderStr !== $desiredBorderStr) {
                $nodesLayout[$writeKey]['style']['border'] = $desiredBorderStr;
                $colorNeedsUpdate = true;
            }

            // Also set/align explicit borderColor for robustness
            if ($savedBorderColor !== $currentColor) {
                $nodesLayout[$writeKey]['style']['borderColor'] = $currentColor;
                $colorNeedsUpdate = true;
            }

            // Also update any data color field if it exists (non-parent nodes only)
            if (!$isParentNode && isset($nodesLayout[$writeKey]['data']) && is_array($nodesLayout[$writeKey]['data'])) {
                $dataColor = $nodesLayout[$writeKey]['data']['color'] ?? null;
                if ($dataColor !== $currentColor) {
                    $nodesLayout[$writeKey]['data']['color'] = $currentColor;
                    $colorNeedsUpdate = true;
                }
            }

            // If structure also contains a separate entry indexed by id, mirror updates
            if ($writeKey !== $nodeIdStr && isset($nodesLayout[$nodeIdStr]) && is_array($nodesLayout[$nodeIdStr])) {
                if (!isset($nodesLayout[$nodeIdStr]['style']) || !is_array($nodesLayout[$nodeIdStr]['style'])) {
                    $nodesLayout[$nodeIdStr]['style'] = [];
                }
                $nodesLayout[$nodeIdStr]['style']['border'] = $desiredBorderStr;
                $nodesLayout[$nodeIdStr]['style']['borderColor'] = $currentColor;
                if (!$isParentNode) {
                    if (!isset($nodesLayout[$nodeIdStr]['data']) || !is_array($nodesLayout[$nodeIdStr]['data'])) {
                        $nodesLayout[$nodeIdStr]['data'] = [];
                    }
                    $nodesLayout[$nodeIdStr]['data']['color'] = $currentColor;
                }
                $colorNeedsUpdate = true;
            }

            if ($colorNeedsUpdate) {
                $colorsSynced++;
            }
        }
        
        if ($colorsSynced > 0) {
            // Save the updated layout using the repository
            $this->streamLayoutRepository->saveLayoutById(
                $layoutDTO->streamId,
                $nodesLayout,
                $layoutDTO->edgesLayout,
                $layoutDTO->streamConfig
            );
        }
        
        return $colorsSynced;
    }
}
