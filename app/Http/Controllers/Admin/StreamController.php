<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StreamService;
use App\Services\StreamConfigurationService;
use App\Services\StreamLayoutService;
use App\Services\DiagramCleanupService;
use App\Http\Requests\Admin\StoreStreamRequest;
use App\Http\Requests\Admin\UpdateStreamRequest;
use App\Http\Requests\Admin\BulkUpdateStreamSortRequest;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
    public function store(StoreStreamRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->streamService->createStream($validated);

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
    public function update(UpdateStreamRequest $request, Stream $stream): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->streamService->updateStream($stream, $validated);

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
    public function bulkUpdateSort(BulkUpdateStreamSortRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
            $streams = $this->streamConfigService->getAllowedDiagramStreams();
            
            $this->cleanupService->removeDuplicateIntegrations();
            $this->cleanupService->removeInvalidIntegrations();
 
            $totalColorsSynced = 0;
            $totalStreamColorsSynced = 0;
            $totalEdgesSynced = 0;
            
            foreach ($streams as $streamName) {
                $this->cleanupService->cleanupStreamLayout($streamName);
                
                $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);
                $totalEdgesSynced += $edgesSynced;
                
                $colorsSynced = $this->streamLayoutService->synchronizeConnectionTypeColors($streamName);
                $totalColorsSynced += $colorsSynced;
                
                $streamColorsSynced = $this->streamLayoutService->synchronizeStreamColors($streamName);
                $totalStreamColorsSynced += $streamColorsSynced;
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh diagram layouts after stream update: ' . $e->getMessage());
        }
    }
}
