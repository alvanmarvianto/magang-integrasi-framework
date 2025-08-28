<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DiagramService;
use App\Services\DiagramCleanupService;
use App\Services\StreamLayoutService;
use App\Services\AppLayoutService;
use App\Models\App;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
class DiagramController extends Controller
{
    protected DiagramService $diagramService;
    protected DiagramCleanupService $cleanupService;
    protected StreamLayoutService $streamLayoutService;
    protected AppLayoutRepositoryInterface $appLayoutRepository;
    protected AppLayoutService $appLayoutService;

    public function __construct(
        DiagramService $diagramService, 
        DiagramCleanupService $cleanupService,
        StreamLayoutService $streamLayoutService,
        AppLayoutRepositoryInterface $appLayoutRepository,
        AppLayoutService $appLayoutService
    ) {
        $this->diagramService = $diagramService;
        $this->cleanupService = $cleanupService;
        $this->streamLayoutService = $streamLayoutService;
        $this->appLayoutRepository = $appLayoutRepository;
        $this->appLayoutService = $appLayoutService;
    }

    /**
     * Show the admin diagram page
     */
    public function show(string $streamName)
    {
        if (!$this->diagramService->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        try {
            $this->cleanupService->removeDuplicateIntegrations();
            $this->cleanupService->removeInvalidIntegrations();

            try {
                $this->streamLayoutService->synchronizeStreamColors($streamName);
            } catch (\Throwable $syncEx) {
                \Log::warning('Admin DiagramController sync on view failed: ' . $syncEx->getMessage());
            }

            $diagramData = $this->diagramService->getVueFlowData($streamName, false);
            $diagramArray = $diagramData->toArray();

            $functionApps = App::where('is_module', true)
                ->orderBy('app_name')
                ->get(['app_id', 'app_name'])
                ->map(function ($app) {
                    return [
                        'app_id' => (int)$app->getAttribute('app_id'),
                        'app_name' => (string)$app->getAttribute('app_name'),
                    ];
                })
                ->toArray();

            return inertia('Admin/Diagram', [
                'streamName' => $streamName,
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'functionApps' => $functionApps,
                'error' => $diagramArray['error'] ?? null,
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
     * Save app layout configuration
     */
    public function saveAppLayout(Request $request, int $appId): RedirectResponse
    {
        try {
            $this->appLayoutRepository->saveLayoutByAppId(
                $appId,
                $request->input('nodes_layout', []),
                $request->input('edges_layout', []),
                $request->input('app_config', [])
            );

            return back()->with('success', 'App layout saved successfully!');
        } catch (\Exception $e) {
            Log::error('Error saving app layout: ' . $e->getMessage());
            return back()->with('error', 'Failed to save app layout');
        }
    }

    /**
     * Show the admin app layout page
     */
    public function showAppLayout(int $appId)
    {
        try {
            $app = App::with('stream')->find($appId);
            if (!$app) {
                abort(404, 'App not found');
            }

            $diagramData = $this->diagramService->getAppLayoutVueFlowData($appId, false);
            $diagramArray = $diagramData->toArray();

            $allowedStreams = $this->diagramService->getAllowedStreams();
            
            $functionApps = App::where('is_module', true)
                ->orderBy('app_name')
                ->get(['app_id', 'app_name'])
                ->map(function ($app) {
                    return [
                        'app_id' => (int)$app->getAttribute('app_id'),
                        'app_name' => (string)$app->getAttribute('app_name'),
                    ];
                })
                ->toArray();

            return inertia('Admin/AppLayoutDiagram', [
                'appId' => $appId,
                'appName' => $app->app_name,
                'streamName' => $app->stream->stream_name ?? '',
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'allowedStreams' => $allowedStreams,
                'functionApps' => $functionApps,
                'error' => $diagramArray['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading admin app layout page: ' . $e->getMessage());
            
            return inertia('Admin/AppLayoutDiagram', [
                'appId' => $appId,
                'appName' => 'Unknown App',
                'streamName' => '',
                'nodes' => [],
                'edges' => [],
                'savedLayout' => null,
                'allowedStreams' => [],
                'functionApps' => [],
                'error' => 'Failed to load app layout data'
            ]);
        }
    }

    /**
     * Get App Layout diagram data for a specific app
     */
    public function getAppLayoutVueFlowData(int $appId): JsonResponse
    {
        try {
            $diagramData = $this->diagramService->getAppLayoutVueFlowData($appId, false);
            return response()->json($diagramData->toArray());
        } catch (\Exception $e) {
            Log::error('Error fetching app layout diagram data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load app layout diagram data'], 500);
        }
    }

    /**
     * Get App-Function diagram data for admin view
     */
    public function getAppFunctionVueFlowData(string $streamName): JsonResponse
    {
        try {
            $diagramData = $this->diagramService->getAppFunctionVueFlowData($streamName, false);
            return response()->json($diagramData->toArray());
        } catch (\Exception $e) {
            Log::error('Error fetching admin app-function diagram data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load app-function diagram data'], 500);
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

            $duplicatesRemoved = $this->cleanupService->removeDuplicateIntegrations();
            $invalidRemoved = $this->cleanupService->removeInvalidIntegrations();
            
            $this->cleanupService->cleanupStreamLayout($streamName);

            $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);
            $parentLabelSynced = $this->streamLayoutService->synchronizeStreamParentNodeLabel($streamName);
            
            $colorsSynced = 0;
            
            $streamColorsSynced = $this->streamLayoutService->synchronizeStreamColors($streamName);

            $totalRemoved = $duplicatesRemoved + $invalidRemoved;
            
            if ($totalRemoved > 0 || $edgesSynced > 0 || $colorsSynced > 0 || $streamColorsSynced > 0 || $parentLabelSynced > 0) {
                $message = "Layout refreshed successfully. Removed {$duplicatesRemoved} duplicates, {$invalidRemoved} invalid connections, synchronized {$edgesSynced} edges, updated {$colorsSynced} connection type colors, updated {$streamColorsSynced} stream colors for app nodes, and synced {$parentLabelSynced} stream parent labels.";
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
     * Refresh app layout - clean up invalid data and synchronize with database
     */
    public function refreshAppLayout(int $appId): RedirectResponse
    {
        try {
            $app = App::with('stream')->find($appId);
            if (!$app) {
                abort(404, 'App not found');
            }

            $duplicatesRemoved = $this->cleanupService->removeDuplicateIntegrations();
            $invalidRemoved = $this->cleanupService->removeInvalidIntegrations();

            if (method_exists($this->appLayoutRepository, 'clearCaches')) {
                $this->appLayoutRepository->clearCaches($appId);
            }

            $edgesSynced = 0;
            $colorsSynced = 0;
            $appDataSynced = 0;

            try {
                $colorsSynced = $this->appLayoutService->syncAppLayoutColors($appId);
                
                $savedLayout = $this->appLayoutRepository->findByAppId($appId);
                
                if ($savedLayout) {
                    $freshDiagramData = $this->diagramService->getAppLayoutVueFlowData($appId, false);
                    $freshData = $freshDiagramData->toArray();
                    
                    $updatedNodesLayout = [];
                    $updatedEdgesLayout = [];
                    
                    if (isset($freshData['nodes'])) {
                        foreach ($freshData['nodes'] as $freshNode) {
                            $nodeId = $freshNode['id'];
                            $savedNodeData = $savedLayout->nodesLayout[$nodeId] ?? [];
                            
                            $updatedNodesLayout[$nodeId] = [
                                'position' => $savedNodeData['position'] ?? $freshNode['position'],
                                'style' => $freshNode['style'] ?? [],
                                'data' => $freshNode['data'] ?? [],
                                'type' => $freshNode['type'] ?? 'default'
                            ];
                        }
                    }
                    
                   if (isset($freshData['edges'])) {
                        $updatedEdgesLayout = array_map(function($edge) use ($savedLayout) {
                            $edgeId = $edge['id'];
                            $savedEdgeData = null;
                            
                            if (isset($savedLayout->edgesLayout) && is_array($savedLayout->edgesLayout)) {
                                foreach ($savedLayout->edgesLayout as $savedEdge) {
                                    if (isset($savedEdge['id']) && $savedEdge['id'] === $edgeId) {
                                        $savedEdgeData = $savedEdge;
                                        break;
                                    }
                                }
                            }
                            
                            return [
                                'id' => $edge['id'],
                                'source' => $edge['source'],
                                'target' => $edge['target'],
                                'sourceHandle' => $savedEdgeData['sourceHandle'] ?? $edge['sourceHandle'] ?? null,
                                'targetHandle' => $savedEdgeData['targetHandle'] ?? $edge['targetHandle'] ?? null,
                                'type' => $edge['type'] ?? 'default',
                                'style' => $edge['style'] ?? [],
                                'data' => $edge['data'] ?? []
                            ];
                        }, $freshData['edges']);
                        $edgesSynced = count($updatedEdgesLayout);
                    }
                    
                    $updatedAppConfig = [
                        'lastUpdated' => now()->toISOString(),
                        'totalNodes' => count($updatedNodesLayout),
                        'totalEdges' => count($updatedEdgesLayout),
                        'app_id' => $appId,
                        'app_name' => $app->app_name,
                        'stream_name' => $app->stream->stream_name ?? '',
                        'refreshedAt' => now()->toISOString()
                    ];
                    
                    $this->appLayoutRepository->saveLayoutByAppId(
                        $appId,
                        $updatedNodesLayout,
                        $updatedEdgesLayout,
                        $updatedAppConfig
                    );
                    
                    $appDataSynced = 1;
                }
            } catch (\Exception $syncEx) {
                Log::warning('App layout sync failed during refresh: ' . $syncEx->getMessage());
            }

            $totalRemoved = $duplicatesRemoved + $invalidRemoved;
            
            if ($totalRemoved > 0 || $edgesSynced > 0 || $colorsSynced > 0 || $appDataSynced > 0) {
                $message = "App layout refreshed successfully. Removed {$duplicatesRemoved} duplicates, {$invalidRemoved} invalid connections, synchronized {$edgesSynced} edges, updated {$colorsSynced} node colors, and synced {$appDataSynced} app data structure.";
            } else {
                $message = "App layout refreshed successfully. No invalid data found.";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error refreshing app layout: ' . $e->getMessage());
            return back()->with('error', 'Failed to refresh app layout');
        }
    }
}
