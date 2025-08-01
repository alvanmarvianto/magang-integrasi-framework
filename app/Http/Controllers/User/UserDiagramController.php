<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\DiagramService;
use Inertia\Inertia;
use Inertia\Response;

class UserDiagramController extends Controller
{
    protected DiagramService $diagramService;

    public function __construct(DiagramService $diagramService)
    {
        $this->diagramService = $diagramService;
    }

    /**
     * Show diagram view for user
     */
    public function show(string $streamName): Response
    {
        if (!$this->diagramService->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        try {
            $diagramData = $this->diagramService->getVueFlowData($streamName, true);
            $diagramArray = $diagramData->toArray();

            return Inertia::render('User/Diagram', [
                'streamName' => $streamName,
                'nodes' => $diagramArray['nodes'] ?? [],
                'edges' => $diagramArray['edges'] ?? [],
                'savedLayout' => $diagramArray['layout'] ?? null,
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => $diagramArray['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('User/Diagram', [
                'streamName' => $streamName,
                'nodes' => [],
                'edges' => [],
                'savedLayout' => null,
                'allowedStreams' => $this->diagramService->getAllowedStreams(),
                'error' => 'Failed to load diagram data',
            ]);
        }
    }
}
