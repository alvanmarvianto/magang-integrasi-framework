<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DiagramController as BaseDiagramController;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class DiagramController extends Controller
{
    protected BaseDiagramController $diagramController;

    public function __construct(BaseDiagramController $diagramController)
    {
        $this->diagramController = $diagramController;
    }

    public function show(Request $request, string $streamName): Response
    {
        if (!$this->diagramController->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        $data = $this->diagramController->getVueFlowAdminData($streamName);

        return Inertia::render('Admin/Diagram', [
            'streamName' => $streamName,
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
            'savedLayout' => $data['savedLayout'],
            'allowedStreams' => $this->diagramController->getAllowedStreams(),
        ]);
    }

    public function saveLayout(Request $request, string $streamName)
    {
        return $this->diagramController->saveLayout($request, $streamName);
    }
}
