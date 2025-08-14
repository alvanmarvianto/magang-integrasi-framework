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

/**
 * Admin Diagram Controller - Fully DTO-based implementation
 * 
 * This controller has been refactored to use DTOs throughout for consistency
 * with the user diagram controller and the overall application architecture.
 */
class DiagramController extends Controller
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
        if (!$this->diagramService->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        try {
            // Clean up invalid nodes and edges first
            $this->cleanupService->removeDuplicateIntegrations();
            $this->cleanupService->removeInvalidIntegrations();

            // Ensure saved layout colors are synced for nodes on every view
            // - node border colors from stream colors
            try {
                $this->streamLayoutService->synchronizeStreamColors($streamName);
            } catch (\Throwable $syncEx) {
                \Log::warning('Admin DiagramController sync on view failed: ' . $syncEx->getMessage());
            }

            // Use DTO-based DiagramService
            $diagramData = $this->diagramService->getVueFlowData($streamName, false);
            $diagramArray = $diagramData->toArray();

            return inertia('Admin/Diagram', [
                'streamName' => $streamName,
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
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
     * Get Vue Flow data for admin view
     */
    public function getVueFlowData(string $streamName): JsonResponse
    {
        try {
            $diagramData = $this->diagramService->getVueFlowData($streamName, false);
            return response()->json($diagramData->toArray());
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
            // Ensure the parent stream node label/data uses DB casing
            $parentLabelSynced = $this->streamLayoutService->synchronizeStreamParentNodeLabel($streamName);
            
            // Skip connection type color sync on refresh to keep edges black
            $colorsSynced = 0;
            
            // Synchronize stream colors for app nodes in the layout
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
     * Clean up diagram data
     */
    public function cleanupDiagramData(array $data): array
    {
        // Convert array to DTO, clean it up, then convert back to array
        $diagramDataDto = \App\DTOs\DiagramDataDTO::fromArray($data);
        $cleanedDto = $this->cleanupService->cleanupDiagramData($diagramDataDto);
        return $cleanedDto->toArray();
    }

    /**
     * Get allowed streams for admin
     */
    public function getAllowedStreams(): JsonResponse
    {
        return response()->json($this->diagramService->getAllowedStreams());
    }
}
