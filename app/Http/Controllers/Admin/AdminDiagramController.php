<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DiagramService;
use App\Services\DiagramCleanupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class AdminDiagramController extends Controller
{
    protected DiagramService $diagramService;
    protected DiagramCleanupService $cleanupService;

    public function __construct(DiagramService $diagramService, DiagramCleanupService $cleanupService)
    {
        $this->diagramService = $diagramService;
        $this->cleanupService = $cleanupService;
    }

    /**
     * Show the admin diagram page
     */
    public function show(string $streamName)
    {
        try {
            // Use the old working method from DiagramController temporarily
            $data = $this->getOldVueFlowAdminData($streamName);
            
            return inertia('Admin/Diagram', [
                'streamName' => $streamName,
                'nodes' => $data['nodes'] ?? [],
                'edges' => $data['edges'] ?? [],
                'savedLayout' => $data['savedLayout'] ?? null,
                'allowedStreams' => $this->diagramService->getAllowedStreams()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading admin diagram page: ' . $e->getMessage());
            
            return inertia('Admin/Diagram', [
                'streamName' => $streamName,
                'nodes' => [],
                'edges' => [],
                'savedLayout' => null,
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => 'Failed to load diagram data'
            ]);
        }
    }

    /**
     * Temporary method using old working logic
     */
    private function getOldVueFlowAdminData(string $streamName): array
    {
        if (!$this->diagramService->validateStreamName($streamName)) {
            return ['nodes' => [], 'edges' => [], 'savedLayout' => null];
        }

        // Clean up invalid nodes and edges first
        $this->cleanupService->removeDuplicateIntegrations();
        $this->cleanupService->removeInvalidIntegrations();

        // Get stream data using old method
        $streamData = $this->getOldStreamData($streamName);
        
        // Get saved layout if exists
        $savedLayout = \App\Models\StreamLayout::where('stream_name', $streamName)->first();

        return [
            'nodes' => $streamData['nodes'],
            'edges' => $streamData['edges'],
            'savedLayout' => $savedLayout,
        ];
    }

    /**
     * Old stream data method that was working
     */
    private function getOldStreamData(string $streamName): array
    {
        $stream = \App\Models\Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return ['nodes' => [], 'edges' => []];
        }

        // Get home stream apps
        $homeStreamApps = \App\Models\App::where('stream_id', $stream->getAttribute('stream_id'))->get();

        // Create stream parent node
        $nodes = [[
            'id' => $streamName,
            'type' => 'group',
            'position' => ['x' => 0, 'y' => 0],
            'data' => [
                'label' => strtoupper($streamName) . ' Stream',
                'app_id' => -1,
                'stream_name' => $streamName,
                'lingkup' => $streamName,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ],
            'style' => [
                'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
                'width' => 600,
                'height' => 400,
                'border' => '2px dashed #999',
            ],
        ]];

        // Add home stream app nodes
        foreach ($homeStreamApps as $app) {
            $nodes[] = [
                'id' => (string)$app->getAttribute('app_id'),
                'type' => 'appNode',
                'position' => ['x' => 100, 'y' => 100], // Will be updated by layout
                'data' => [
                    'label' => $app->getAttribute('app_name') . "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($streamName),
                    'app_id' => $app->getAttribute('app_id'),
                    'app_name' => $app->getAttribute('app_name'),
                    'stream_name' => $streamName,
                    'lingkup' => $app->stream->getAttribute('stream_name') ?? 'unknown',
                    'is_home_stream' => true,
                    'is_external' => false,
                ],
                'parentNode' => $streamName,
                'extent' => 'parent',
            ];
        }

        // Get connected external apps
        $homeAppIds = $homeStreamApps->pluck('app_id')->toArray();
        
        // Get apps that are connected to home stream apps but are not in the home stream
        $connectedAppIds = collect();
        
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = \App\Models\AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);
        
        // Get apps that have integrations FROM home stream apps
        $targetAppIds = \App\Models\AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);
        
        // Remove home stream app IDs to get only external apps
        $externalAppIds = $connectedAppIds->diff($homeAppIds)->unique()->values();
        
        $externalApps = \App\Models\App::whereIn('app_id', $externalAppIds)->with('stream')->get();

        // Add external app nodes
        foreach ($externalApps as $app) {
            $nodes[] = [
                'id' => (string)$app->getAttribute('app_id'),
                'type' => 'appNode',
                'position' => ['x' => 700, 'y' => 100], // Will be updated by layout
                'data' => [
                    'label' => $app->getAttribute('app_name') . "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($app->stream->getAttribute('stream_name') ?? 'external'),
                    'app_id' => $app->getAttribute('app_id'),
                    'app_name' => $app->getAttribute('app_name'),
                    'stream_name' => $app->stream->getAttribute('stream_name') ?? 'external',
                    'lingkup' => $app->stream->getAttribute('stream_name') ?? 'external',
                    'is_home_stream' => false,
                    'is_external' => true,
                ],
                'parentNode' => null,
                'extent' => null,
            ];
        }

        // Remove any duplicate nodes by app_id (safety check)
        $uniqueNodes = [];
        $seenIds = [];
        
        foreach ($nodes as $node) {
            $nodeId = $node['id'];
            if (!in_array($nodeId, $seenIds)) {
                $uniqueNodes[] = $node;
                $seenIds[] = $nodeId;
            }
        }
        $nodes = $uniqueNodes;
        
        // Get all edges - only show connections involving at least one home stream app
        $allAppIds = collect($nodes)->pluck('data.app_id')->filter(fn($id) => $id > 0)->toArray();
        
        $integrations = \App\Models\AppIntegration::whereIn('source_app_id', $allAppIds)
            ->whereIn('target_app_id', $allAppIds)
            ->with(['connectionType', 'sourceApp', 'targetApp'])
            ->get();

        $edges = [];
        foreach ($integrations as $integration) {
            // Only include edges where at least one end is a home stream app
            $sourceIsHome = in_array($integration->getAttribute('source_app_id'), $homeAppIds);
            $targetIsHome = in_array($integration->getAttribute('target_app_id'), $homeAppIds);
            
            if ($sourceIsHome || $targetIsHome) {
                $edges[] = [
                    'id' => 'edge-' . $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
                    'source' => (string)$integration->getAttribute('source_app_id'),
                    'target' => (string)$integration->getAttribute('target_app_id'),
                    'type' => 'smoothstep',
                    'data' => [
                        'label' => $integration->connectionType->getAttribute('type_name') ?? 'Unknown',
                        'connection_type' => strtolower($integration->connectionType->getAttribute('type_name') ?? 'direct'),
                        'integration_id' => $integration->getAttribute('integration_id'),
                        'sourceApp' => [
                            'app_id' => $integration->sourceApp->getAttribute('app_id'),
                            'app_name' => $integration->sourceApp->getAttribute('app_name'),
                        ],
                        'targetApp' => [
                            'app_id' => $integration->targetApp->getAttribute('app_id'),
                            'app_name' => $integration->targetApp->getAttribute('app_name'),
                        ],
                        'direction' => $integration->getAttribute('direction'),
                        'inbound' => $integration->getAttribute('inbound'),
                        'outbound' => $integration->getAttribute('outbound'),
                        'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                    ]
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Get Vue Flow data for admin view
     */
    public function getVueFlowData(string $streamName): JsonResponse
    {
        try {
            $data = $this->diagramService->getVueFlowData($streamName, false);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching admin diagram data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load diagram data'], 500);
        }
    }

    /**
     * Save layout configuration for admin
     */
    public function saveLayout(Request $request, string $streamName): RedirectResponse
    {
        try {
            $this->diagramService->saveLayout(
                $streamName,
                $request->input('nodes_layout', []),
                $request->input('stream_config', []),
                $request->input('edges_layout', [])
            );

            // Return success response for Inertia
            return back()->with('success', 'Layout saved successfully!');
        } catch (\Exception $e) {
            Log::error('Error saving admin layout: ' . $e->getMessage());
            return back()->with('error', 'Failed to save layout');
        }
    }

    /**
     * Refresh layout - clean up invalid data and synchronize with AppIntegration data
     */
    public function refreshLayout(string $streamName): RedirectResponse
    {
        try {
            if (!$this->diagramService->validateStreamName($streamName)) {
                return redirect()->route('admin.diagrams.show', ['streamName' => $streamName])
                    ->with('error', 'Invalid stream name');
            }

            // Clean up duplicates and invalid integrations
            $duplicatesRemoved = $this->cleanupService->removeDuplicateIntegrations();
            $invalidRemoved = $this->cleanupService->removeInvalidIntegrations();
            
            // Clean up stream layout nodes
            $this->cleanupService->cleanupStreamLayout($streamName);

            // Synchronize edges layout with current AppIntegration data
            $edgesSynced = $this->synchronizeStreamLayoutEdges($streamName);

            $totalRemoved = $duplicatesRemoved + $invalidRemoved;
            
            if ($totalRemoved > 0 || $edgesSynced > 0) {
                $message = "Layout refreshed successfully. Removed {$duplicatesRemoved} duplicates, {$invalidRemoved} invalid connections, and synchronized {$edgesSynced} edges.";
            } else {
                $message = "Layout refreshed successfully. No invalid data found.";
            }

            return redirect()->route('admin.diagrams.show', ['streamName' => $streamName])
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error refreshing admin layout: ' . $e->getMessage());
            return redirect()->route('admin.diagrams.show', ['streamName' => $streamName])
                ->with('error', 'Failed to refresh layout');
        }
    }

    /**
     * Synchronize stream layout edges with current AppIntegration data
     */
    private function synchronizeStreamLayoutEdges(string $streamName): int
    {
        $layout = \App\Models\StreamLayout::where('stream_name', $streamName)->first();
        if (!$layout) {
            return 0;
        }

        // Get current stream and its apps
        $stream = \App\Models\Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return 0;
        }

        $homeStreamApps = \App\Models\App::where('stream_id', $stream->getAttribute('stream_id'))->get();
        $homeAppIds = $homeStreamApps->pluck('app_id')->toArray();

        // Get connected external apps
        $connectedAppIds = collect();
        
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = \App\Models\AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);
        
        // Get apps that have integrations FROM home stream apps
        $targetAppIds = \App\Models\AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);
        
        // All valid app IDs for this stream
        $allValidAppIds = $connectedAppIds->unique()->values()->toArray();

        // Get current integrations involving these apps
        $integrations = \App\Models\AppIntegration::whereIn('source_app_id', $allValidAppIds)
            ->whereIn('target_app_id', $allValidAppIds)
            ->with(['connectionType', 'sourceApp', 'targetApp'])
            ->get();

        // Get existing edges layout to preserve handle positions
        $existingEdgesLayout = $layout->edges_layout ?? [];
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
                $sourceId = (string)$integration->getAttribute('source_app_id');
                $targetId = (string)$integration->getAttribute('target_app_id');
                $connectionType = strtolower($integration->connectionType->getAttribute('type_name') ?? 'direct');
                $edgeId = $sourceId . '-' . $targetId;
                
                // Determine edge color based on connection type
                $edgeColor = '#000000'; // default for direct
                switch ($connectionType) {
                    case 'soa':
                        $edgeColor = '#02a330';
                        break;
                    case 'sftp':
                        $edgeColor = '#002ac0';
                        break;
                    case 'direct':
                    default:
                        $edgeColor = '#000000';
                        break;
                }

                // Check if this edge exists in the current layout to preserve handles
                $existingEdge = $existingEdgesMap[$edgeId] ?? null;
                
                $newEdge = [
                    'id' => $edgeId,
                    'source' => $sourceId,
                    'target' => $targetId,
                    'type' => 'smoothstep',
                    'style' => [
                        'stroke' => $edgeColor,
                        'strokeWidth' => 2
                    ],
                    'data' => [
                        'label' => $connectionType,
                        'connection_type' => $connectionType,
                        'integration_id' => $integration->getAttribute('integration_id'),
                        'source_app_id' => $integration->getAttribute('source_app_id'),
                        'target_app_id' => $integration->getAttribute('target_app_id'),
                        'inbound' => $integration->getAttribute('inbound'),
                        'outbound' => $integration->getAttribute('outbound'),
                        'direction' => $integration->getAttribute('direction'),
                        'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                        'source_app_name' => $integration->sourceApp->getAttribute('app_name'),
                        'target_app_name' => $integration->targetApp->getAttribute('app_name'),
                        'sourceApp' => [
                            'app_id' => $integration->getAttribute('source_app_id'),
                            'app_name' => $integration->sourceApp->getAttribute('app_name')
                        ],
                        'targetApp' => [
                            'app_id' => $integration->getAttribute('target_app_id'),
                            'app_name' => $integration->targetApp->getAttribute('app_name')
                        ]
                    ]
                ];

                // Preserve existing handle positions if they exist
                if ($existingEdge) {
                    if (isset($existingEdge['sourceHandle'])) {
                        $newEdge['sourceHandle'] = $existingEdge['sourceHandle'];
                    }
                    if (isset($existingEdge['targetHandle'])) {
                        $newEdge['targetHandle'] = $existingEdge['targetHandle'];
                    }
                }

                $newEdgesLayout[] = $newEdge;
            }
        }

        // Update stream config
        $streamConfig = $layout->stream_config ?? [];
        $streamConfig['totalEdges'] = count($newEdgesLayout);
        $streamConfig['lastUpdated'] = now()->toISOString();

        // Count of valid nodes (excluding stream parent node)
        $nodesLayout = $layout->nodes_layout ?? [];
        $validNodesCount = count(array_filter($nodesLayout, function($key) use ($streamName) {
            return $key !== $streamName && $key !== 'sp'; // Exclude stream parent nodes
        }, ARRAY_FILTER_USE_KEY));
        
        $streamConfig['totalNodes'] = $validNodesCount;

        // Update the layout
        $layout->update([
            'edges_layout' => $newEdgesLayout,
            'stream_config' => $streamConfig
        ]);

        Log::info("Synchronized {$streamName} stream layout: " . count($newEdgesLayout) . " edges updated");

        return count($newEdgesLayout);
    }

    /**
     * Clean up diagram data
     */
    public function cleanupDiagramData(array $data): array
    {
        return $this->cleanupService->cleanupDiagramData($data);
    }

    /**
     * Get allowed streams for admin
     */
    public function getAllowedStreams(): JsonResponse
    {
        return response()->json($this->diagramService->getAllowedStreams());
    }
}
