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
            'apps' => App::select('app_id', 'app_name')->get(),
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
        
        // Update stream layouts after creating integration
        $this->updateStreamLayouts($integration);
        
        return $integration;
    }

    /**
     * Update stream layouts when integration data changes
     */
    private function updateStreamLayouts(AppIntegration $integration): void
    {
        // Load relationships
        $integration->load(['sourceApp', 'targetApp', 'connectionType']);
        
        // Get all stream layouts
        $layouts = StreamLayout::all();
        
        \Log::info('Updating stream layouts for integration ID: ' . $integration->getAttribute('integration_id'));
        
        foreach ($layouts as $layout) {
            $updated = false;
            $edgesLayout = $layout->edges_layout ?? [];
            
            \Log::info('Checking layout for stream: ' . $layout->getAttribute('stream_name') . ' with ' . count($edgesLayout) . ' edges');
            
            // Update edges that involve this integration
            foreach ($edgesLayout as &$edge) {
                // Check if this edge represents the integration by integration_id
                $edgeIntegrationId = null;
                
                // Handle both old and new data formats
                if (isset($edge['data'])) {
                    if (isset($edge['data']['integration_id'])) {
                        $edgeIntegrationId = $edge['data']['integration_id'];
                    }
                }
                
                if ($edgeIntegrationId == $integration->getAttribute('integration_id')) {
                    \Log::info('Found matching edge for integration ID: ' . $integration->getAttribute('integration_id'));
                    
                    // Update the edge completely with fresh integration data
                    $edge['source'] = (string)$integration->getAttribute('source_app_id');
                    $edge['target'] = (string)$integration->getAttribute('target_app_id');
                    $edge['id'] = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
                    
                    // Update the data object with the new standardized format
                    $edge['data'] = [
                        'integration_id' => $integration->getAttribute('integration_id'),
                        'source_app_id' => $integration->getAttribute('source_app_id'),
                        'target_app_id' => $integration->getAttribute('target_app_id'),
                        'connection_type' => $integration->connectionType->type_name ?? 'direct',
                        'connection_type_id' => $integration->getAttribute('connection_type_id'),
                        'description' => $integration->getAttribute('description'),
                        'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                        'direction' => $integration->getAttribute('direction'),
                        'starting_point' => $integration->getAttribute('starting_point'),
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
                    
                    $updated = true;
                }
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                \Log::info('Updating layout for stream: ' . $layout->getAttribute('stream_name'));
                $layout->update([
                    'edges_layout' => $edgesLayout,
                ]);
            }
        }
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
                return $edgeIntegrationId != $integration->getAttribute('integration_id');
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
