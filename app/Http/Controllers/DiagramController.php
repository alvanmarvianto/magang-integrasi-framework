<?php

namespace App\Http\Controllers;

use App\Services\DiagramService;
use App\Services\DiagramCleanupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagramController extends Controller
{
    protected DiagramService $diagramService;
    protected DiagramCleanupService $cleanupService;

    public function __construct(DiagramService $diagramService, DiagramCleanupService $cleanupService)
    {
        $this->diagramService = $diagramService;
        $this->cleanupService = $cleanupService;
    }

    public function show(string $streamName): Response
    {
        if (!$this->diagramService->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        // Debug: Log the request
        \Log::info("DiagramController - Showing stream: {$streamName}");

        try {
            $diagramData = $this->diagramService->getVueFlowData($streamName, true);
            $diagramArray = $diagramData->toArray();

            // Debug: Log the layout data being passed to frontend
            \Log::info("DiagramController - Layout data: ", [
                'has_layout' => isset($diagramArray['layout']),
                'layout_is_null' => $diagramArray['layout'] === null,
                'layout_content' => $diagramArray['layout']
            ]);

            return Inertia::render('Integration/Stream', [
                'streamName' => $streamName,
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'config' => $diagramArray['config'] ?? null,
                'streams' => $this->diagramService->getAllowedStreams(),
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => $diagramArray['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error("DiagramController - Error loading diagram: " . $e->getMessage());
            return Inertia::render('Integration/Stream', [
                'streamName' => $streamName,
                'nodes' => [],
                'edges' => [],
                'savedLayout' => null,
                'config' => null,
                'streams' => $this->diagramService->getAllowedStreams(),
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => 'Failed to load diagram data',
            ]);
        }
    }
}
