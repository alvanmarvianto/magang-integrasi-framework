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

        try {
            $diagramData = $this->diagramService->getVueFlowData($streamName, true);
            $diagramArray = $diagramData->toArray();

            return Inertia::render('Integration/Stream', [
                'streamName' => $streamName,
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'streams' => $this->diagramService->getAllowedStreams(),
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => $diagramArray['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Integration/Stream', [
                'streamName' => $streamName,
                'nodes' => [],
                'edges' => [],
                'savedLayout' => null,
                'streams' => $this->diagramService->getAllowedStreams(),
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => 'Failed to load diagram data',
            ]);
        }
    }
}
