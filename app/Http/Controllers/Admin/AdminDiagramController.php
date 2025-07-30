<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DiagramService;
use App\Services\DiagramCleanupService;
use App\Services\StreamLayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class AdminDiagramController extends Controller
{
    protected DiagramService $diagramService;
    protected DiagramCleanupService $cleanupService;
    protected StreamLayoutService $streamLayoutService;

    public function __construct(
        DiagramService $diagramService, 
        DiagramCleanupService $cleanupService,
        StreamLayoutService $streamLayoutService
    ) {
        $this->diagramService = $diagramService;
        $this->cleanupService = $cleanupService;
        $this->streamLayoutService = $streamLayoutService;
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
                'label' => $streamName,
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
                    'description' => $app->getAttribute('description'),
                    'app_type' => $app->getAttribute('app_type'),
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
                    'description' => $app->getAttribute('description'),
                    'app_type' => $app->getAttribute('app_type'),
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
                $connectionType = $integration->connectionType->type_name ?? 'direct';
                $edgeColor = match (strtolower($connectionType)) {
                    'soa' => '#02a330',
                    'sftp' => '#002ac0',
                    'soa-sftp' => '#6b7280',
                    default => '#000000',
                };

                $edges[] = [
                    'id' => $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
                    'source' => (string)$integration->getAttribute('source_app_id'),
                    'target' => (string)$integration->getAttribute('target_app_id'),
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

            // Synchronize edges layout with current AppIntegration data using StreamLayoutService
            $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);

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
