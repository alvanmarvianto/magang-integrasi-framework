<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\ConnectionType;
use App\Models\StreamLayout;

class IntegrationService
{
    public function getPaginatedIntegrations(?string $search, int $perPage = 10, string $sortBy = 'source_app_name', bool $sortDesc = false): array
    {
        $query = AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
            ->when($search, function ($query, $search) {
                $query->whereHas('sourceApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                })->orWhereHas('targetApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                });
            });

        // Handle sorting
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
                $query->leftJoin('connectiontypes', 'appintegrations.connection_type_id', '=', 'connectiontypes.connection_type_id')
                      ->orderBy('connectiontypes.type_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            default:
                $query->orderBy('appintegrations.integration_id', $sortDirection);
                break;
        }

        $paginator = $query->paginate($perPage);

        $data = $paginator->items();
        $transformedData = array_map(function ($integration) {
            return [
                'integration_id' => $integration->integration_id,
                'source_app' => [
                    'app_id' => $integration->sourceApp->app_id,
                    'app_name' => $integration->sourceApp->app_name,
                ],
                'target_app' => [
                    'app_id' => $integration->targetApp->app_id,
                    'app_name' => $integration->targetApp->app_name,
                ],
                'connection_type' => [
                    'connection_type_id' => $integration->connectionType->connection_type_id,
                    'type_name' => $integration->connectionType->type_name,
                ],
            ];
        }, $data);

        return [
            'integrations' => [
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'links' => $paginator->onEachSide(1)->linkCollection()->toArray(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]
            ]
        ];
    }

    public function getFormData(?int $integrationId = null): array
    {
        $data = [
            'apps' => App::select('app_id', 'app_name')->orderBy('app_name', 'asc')->get(),
            'connectionTypes' => ConnectionType::all(),
        ];

        if ($integrationId) {
            $data['integration'] = AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
                ->findOrFail($integrationId);
        }

        return $data;
    }

    public function createIntegration(array $data): AppIntegration
    {
        // Check for existing connection in either direction
        $exists = AppIntegration::where(function ($query) use ($data) {
            $query->where('source_app_id', $data['source_app_id'])
                  ->where('target_app_id', $data['target_app_id']);
        })->orWhere(function ($query) use ($data) {
            $query->where('source_app_id', $data['target_app_id'])
                  ->where('target_app_id', $data['source_app_id']);
        })->exists();

        if ($exists) {
            throw new \Exception('Integration between these apps already exists');
        }

        $integration = AppIntegration::create($data);
        
        // Refresh the model to ensure we have the integration_id
        $integration->refresh();
        
        // Update stream layouts after creating integration
        $this->updateStreamLayouts($integration);
        
        return $integration;
    }

    /**
     * Update stream layouts when integration data changes
     */
    private function updateStreamLayouts(AppIntegration $integration): void
    {
        // Load relationships including the stream for each app
        $integration->load(['sourceApp.stream', 'targetApp.stream', 'connectionType']);
        
        // Get all stream layouts
        $layouts = StreamLayout::all();
        
        foreach ($layouts as $layout) {
            $updated = false;
            $edgesLayout = $layout->edges_layout ?? [];
            $streamName = $layout->getAttribute('stream_name');
            
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
                $newEdge = $this->createNewEdgeFromIntegration($integration);
                $edgesLayout[] = $newEdge;
                $updated = true;
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $layout->update([
                    'edges_layout' => $edgesLayout,
                ]);
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
        $edgeColor = $this->getEdgeColorByConnectionType($connectionType);
        $edge['style'] = [
            'stroke' => $edgeColor,
            'strokeWidth' => 2
        ];
        $edge['type'] = 'smoothstep';
    }
    
    /**
     * Create a new edge from integration data
     */
    private function createNewEdgeFromIntegration(AppIntegration $integration): array
    {
        $connectionType = $integration->connectionType->type_name ?? 'direct';
        $edgeColor = $this->getEdgeColorByConnectionType($connectionType);
        
        return [
            'id' => $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
            'source' => (string)$integration->getAttribute('source_app_id'),
            'target' => (string)$integration->getAttribute('target_app_id'),
            'type' => 'smoothstep',
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
            ]
        ];
    }

    /**
     * Get edge color based on connection type
     */
    private function getEdgeColorByConnectionType(string $connectionType): string
    {
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
     * Remove integration edges from all stream layouts
     */
    private function removeIntegrationFromLayouts(AppIntegration $integration): void
    {
        $layouts = StreamLayout::all();
        
        foreach ($layouts as $layout) {
            $edgesLayout = $layout->edges_layout ?? [];
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
                $streamConfig = $layout->stream_config ?? [];
                if (isset($streamConfig['totalEdges'])) {
                    $streamConfig['totalEdges'] = count($edgesLayout);
                }
                
                $layout->update([
                    'edges_layout' => array_values($edgesLayout), // Re-index array
                    'stream_config' => $streamConfig,
                ]);
            }
        }
    }

    public function updateIntegration(AppIntegration $integration, array $data): bool
    {
        // Check for existing connection in either direction, excluding the current integration
        if (isset($data['source_app_id']) && isset($data['target_app_id'])) {
            $exists = AppIntegration::where($integration->getKeyName(), '!=', $integration->getKey())
                ->where(function ($query) use ($data) {
                    $query->where('source_app_id', $data['source_app_id'])
                          ->where('target_app_id', $data['target_app_id']);
                })->orWhere(function ($query) use ($data, $integration) {
                    $query->where($integration->getKeyName(), '!=', $integration->getKey())
                          ->where('source_app_id', $data['target_app_id'])
                          ->where('target_app_id', $data['source_app_id']);
                })->exists();

            if ($exists) {
                throw new \Exception('Integration between these apps already exists');
            }
        }

        $result = $integration->update($data);
        
        // Update stream layouts after updating integration
        if ($result) {
            // Refresh the model to get the updated data with relationships
            $integration->refresh();
            $this->updateStreamLayouts($integration);
        }
        
        return $result;
    }

    public function deleteIntegration(AppIntegration $integration): ?bool
    {
        // Remove integration from all stream layouts before deleting
        $this->removeIntegrationFromLayouts($integration);
        
        return $integration->delete();
    }

    public function getConnectionTypes(): array
    {
        return [
            'connectionTypes' => ConnectionType::withCount('appIntegrations')->get()
        ];
    }
}
