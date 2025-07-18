<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminVueFlowController extends Controller
{
    protected $diagramController;

    public function __construct(DiagramController $diagramController)
    {
        $this->diagramController = $diagramController;
    }

    /**
     * Show admin page for specific stream
     */
    public function show(Request $request, string $streamName)
    {
        if (!$this->diagramController->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        $data = $this->diagramController->getVueFlowAdminData($streamName);

        return Inertia::render('AdminVueFlowStream', [
            'streamName' => $streamName,
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
            'savedLayout' => $data['savedLayout'],
            'allowedStreams' => $this->diagramController->getAllowedStreams(),
        ]);
    }

    /**
     * Save layout configuration
     */
    public function saveLayout(Request $request, string $streamName)
    {
        return $this->diagramController->saveLayout($request, $streamName);
    }
}
