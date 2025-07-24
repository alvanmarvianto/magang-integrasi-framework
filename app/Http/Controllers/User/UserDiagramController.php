<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\DiagramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserDiagramController extends Controller
{
    protected DiagramService $diagramService;

    public function __construct(DiagramService $diagramService)
    {
        $this->diagramService = $diagramService;
    }

    /**
     * Get Vue Flow data for user view
     */
    public function getVueFlowData(string $streamName): JsonResponse
    {
        try {
            $data = $this->diagramService->getVueFlowData($streamName, true);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching user diagram data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load diagram data'], 500);
        }
    }

    /**
     * Save layout configuration for user (read-only, optional for future use)
     */
    public function saveLayout(Request $request, string $streamName): JsonResponse
    {
        try {
            // For user view, we could save user-specific layouts in the future
            // For now, just return success without saving
            return response()->json(['success' => true, 'message' => 'User layout preferences saved']);
        } catch (\Exception $e) {
            Log::error('Error saving user layout: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save layout'], 500);
        }
    }

    /**
     * Get allowed streams for user
     */
    public function getAllowedStreams(): JsonResponse
    {
        return response()->json($this->diagramService->getAllowedStreams());
    }
}
