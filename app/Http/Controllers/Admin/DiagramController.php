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

    public function refreshLayout(string $streamName)
    {
        if (!$this->diagramController->validateStreamName($streamName)) {
            return redirect()->route('admin.diagrams.show', $streamName)->withErrors(['error' => 'Stream not found']);
        }

        try {
            // Get fresh data from database, which will automatically exclude non-existent apps and connections
            $data = $this->diagramController->getVueFlowAdminData($streamName);

            // Remove duplicates and clean up data
            $cleanedData = $this->diagramController->cleanupDiagramData($data);

            // Redirect back to the normal diagram view with success message
            return redirect()->route('admin.diagrams.show', $streamName)
                ->with('success', 'Layout berhasil disegarkan! Data yang tidak valid telah dihapus.');
        } catch (\Exception $e) {
            \Log::error('Refresh layout error: ' . $e->getMessage());
            return redirect()->route('admin.diagrams.show', $streamName)
                ->withErrors(['error' => 'Failed to refresh layout: ' . $e->getMessage()]);
        }
    }
}
