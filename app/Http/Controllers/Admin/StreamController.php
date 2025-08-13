<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StreamService;
use App\Services\StreamConfigurationService;
use App\Services\StreamLayoutService;
use App\Services\DiagramCleanupService;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StreamController extends Controller
{
    public function __construct(
        private StreamService $streamService,
        private StreamConfigurationService $streamConfigService,
        private StreamLayoutService $streamLayoutService,
        private DiagramCleanupService $cleanupService
    ) {}

    /**
     * Display a listing of streams
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'stream_name');
        $sortDesc = $request->boolean('sort_desc', false);
        $perPage = $request->input('per_page', 10);

        $streams = $this->streamService->getStreamsWithAppsCount($search, $sortBy, $sortDesc, $perPage);

        return Inertia::render('Admin/Streams', [
            'streams' => $streams,
        ]);
    }

    /**
     * Show the form for creating a new stream
     */
    public function create(): Response
    {
        return Inertia::render('Admin/StreamForm');
    }

    /**
     * Store a newly created stream
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stream_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('streams', 'stream_name')
            ],
            'description' => 'nullable|string|max:500',
            'is_allowed_for_diagram' => 'boolean',
            'sort_order' => 'nullable|integer|min:1|max:999',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF6B35).',
        ]);

        try {
            $this->streamService->createStream($validated);

            // Refresh diagram layouts for all streams to synchronize stream colors
            $this->refreshDiagramLayouts();

            return redirect()
                ->route('admin.streams.index')
                ->with('success', 'Stream created successfully and diagram layouts refreshed.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create stream: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified stream
     */
    public function edit(Stream $stream): Response
    {
        return Inertia::render('Admin/StreamForm', [
            'stream' => $stream->toArray(),
        ]);
    }

    /**
     * Update the specified stream
     */
    public function update(Request $request, Stream $stream): RedirectResponse
    {
        $validated = $request->validate([
            'stream_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('streams', 'stream_name')->ignore($stream->stream_id, 'stream_id')
            ],
            'description' => 'nullable|string|max:500',
            'is_allowed_for_diagram' => 'boolean',
            'sort_order' => 'nullable|integer|min:1|max:999',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF6B35).',
        ]);

        try {
            $this->streamService->updateStream($stream, $validated);

            // Refresh diagram layouts for all streams to synchronize stream colors
            $this->refreshDiagramLayouts();

            return redirect()
                ->route('admin.streams.index')
                ->with('success', 'Stream updated successfully and diagram layouts refreshed.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update stream: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified stream
     */
    public function destroy(Stream $stream): RedirectResponse
    {
        try {
            $this->streamService->deleteStream($stream);

            return redirect()
                ->route('admin.streams.index')
                ->with('success', 'Stream deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete stream: ' . $e->getMessage());
        }
    }

    /**
     * Toggle allowed permission for a stream
     */
    public function toggleAllowed(Stream $stream): RedirectResponse
    {
        try {
            $this->streamService->updateStream($stream, [
                'stream_name' => $stream->stream_name,
                'description' => $stream->description,
                'is_allowed_for_diagram' => !$stream->is_allowed_for_diagram,
                'sort_order' => $stream->sort_order,
                'color' => $stream->color,
            ]);

            $status = $stream->is_allowed_for_diagram ? 'disabled' : 'enabled';
            
            return redirect()
                ->back()
                ->with('success', "Stream '{$stream->stream_name}' {$status} for allowed list.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to toggle allowed permission: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update sort order for streams
     */
    public function bulkUpdateSort(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.stream_id' => 'required|integer|exists:streams,stream_id',
            'updates.*.sort_order' => 'required|integer|min:1|max:999',
        ]);

        try {
            $this->streamService->bulkUpdateSort($validated['updates']);

            return redirect()
                ->back()
                ->with('success', 'Stream sort order updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update sort order: ' . $e->getMessage());
        }
    }

    /**
     * Refresh diagram layouts for all streams to synchronize stream colors
     * Similar to the implementation in ConnectionTypeService
     */
    private function refreshDiagramLayouts(): void
    {
        try {
            // Get all available streams from stream configuration service
            $streams = $this->streamConfigService->getAllowedDiagramStreams();
            
            // First, clean up any invalid data globally
            $this->cleanupService->removeDuplicateIntegrations();
            $this->cleanupService->removeInvalidIntegrations();
 
            $totalColorsSynced = 0;
            $totalStreamColorsSynced = 0;
            $totalEdgesSynced = 0;
            
            foreach ($streams as $streamName) {
                // Clean up stream layout nodes for this specific stream
                $this->cleanupService->cleanupStreamLayout($streamName);
                
                // Synchronize edges layout with current AppIntegration data
                $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);
                $totalEdgesSynced += $edgesSynced;
                
                // Synchronize connection type colors in the layout
                $colorsSynced = $this->streamLayoutService->synchronizeConnectionTypeColors($streamName);
                $totalColorsSynced += $colorsSynced;
                
                // Synchronize stream colors for app nodes in the layout
                $streamColorsSynced = $this->streamLayoutService->synchronizeStreamColors($streamName);
                $totalStreamColorsSynced += $streamColorsSynced;
            }
            
            Log::info("Refreshed diagram layouts after stream change: {$totalEdgesSynced} edges, {$totalColorsSynced} connection colors, {$totalStreamColorsSynced} stream colors synchronized.");
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            Log::error('Failed to refresh diagram layouts after stream update: ' . $e->getMessage());
        }
    }
}
