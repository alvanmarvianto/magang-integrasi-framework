<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\StreamLayout;
use App\Models\Stream;
use App\Models\ConnectionType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminVueFlowController extends Controller
{
    private const ALLOWED_STREAMS = ['sp', 'mi', 'ssk', 'moneter', 'market'];

    /**
     * Show admin page for specific stream
     */
    public function show(Request $request, string $streamName)
    {
        if (!in_array($streamName, self::ALLOWED_STREAMS)) {
            abort(404, 'Stream not found');
        }

        // Clean up invalid nodes and edges first
        $this->cleanupInvalidData($streamName);

        // Get stream data
        $streamData = $this->getStreamData($streamName);
        
        // Get saved layout if exists
        $savedLayout = StreamLayout::getLayout($streamName);

        return Inertia::render('AdminVueFlowStream', [
            'streamName' => $streamName,
            'nodes' => $streamData['nodes'],
            'edges' => $streamData['edges'],
            'savedLayout' => $savedLayout,
            'allowedStreams' => self::ALLOWED_STREAMS,
        ]);
    }

    /**
     * Save layout configuration
     */
    public function saveLayout(Request $request, string $streamName)
    {
        if (!in_array($streamName, self::ALLOWED_STREAMS)) {
            return response('Invalid stream', 400);
        }

        $request->validate([
            'nodes_layout' => 'required|array',
            'edges_layout' => 'array',
            'stream_config' => 'required|array',
        ]);

        StreamLayout::saveLayout(
            $streamName,
            $request->nodes_layout,
            $request->stream_config,
            $request->edges_layout ?? []
        );

        // Return an empty response for Inertia
        return response('', 200);
    }

    /**
     * Clean up invalid nodes and edges
     */
    private function cleanupInvalidData(string $streamName): void
    {
        // Get current stream
        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) return;

        // Get all apps that should exist
        $validAppIds = App::whereHas('stream', function ($query) use ($streamName) {
            $query->where('stream_name', $streamName);
        })->pluck('app_id')->toArray();

        // Get all apps from other streams that might be connected
        $externalAppIds = App::whereHas('integrations', function ($query) use ($validAppIds) {
            $query->whereIn('target_app_id', $validAppIds);
        })->orWhereHas('integratedBy', function ($query) use ($validAppIds) {
            $query->whereIn('source_app_id', $validAppIds);
        })->pluck('app_id')->toArray();

        $allValidAppIds = array_unique(array_merge($validAppIds, $externalAppIds));

        // DO NOT DELETE ANY AppIntegration RECORDS - keep all connections intact
        // Only clean up layout data for nodes that no longer exist

        // Remove layout data for deleted apps only
        $layout = StreamLayout::where('stream_name', $streamName)->first();
        if ($layout && $layout->nodes_layout) {
            $validNodeIds = collect($allValidAppIds)->map(fn($id) => (string)$id)->toArray();
            $validNodeIds[] = $streamName; // Include stream parent node

            $cleanedLayout = array_filter(
                $layout->nodes_layout,
                fn($key) => in_array($key, $validNodeIds),
                ARRAY_FILTER_USE_KEY
            );

            if (count($cleanedLayout) !== count($layout->nodes_layout)) {
                \Log::info("Cleaning layout for stream {$streamName}: " . (count($layout->nodes_layout) - count($cleanedLayout)) . " layout nodes removed from stream_layouts, AppIntegration records preserved");
                $layout->update(['nodes_layout' => $cleanedLayout]);
            }
        }
    }

    /**
     * Get stream data (same as AppController but with cleanup)
     */
    private function getStreamData(string $streamName): array
    {
        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return ['nodes' => [], 'edges' => []];
        }

        // Get home stream apps
        $homeStreamApps = App::where('stream_id', $stream->stream_id)->get();

        // Create stream parent node
        $nodes = [[
            'id' => $streamName,
            'data' => [
                'label' => strtoupper($streamName) . ' Stream',
                'app_id' => -1,
                'stream_name' => $streamName,
                'lingkup' => $streamName,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ]
        ]];

        // Add home stream app nodes
        foreach ($homeStreamApps as $app) {
            $nodes[] = [
                'id' => (string)$app->app_id,
                'data' => [
                    'label' => $app->app_name . "\nID: " . $app->app_id . "\nStream: " . strtoupper($streamName),
                    'app_id' => $app->app_id,
                    'stream_name' => $streamName,
                    'lingkup' => $app->stream->stream_name ?? 'unknown',
                    'is_home_stream' => true,
                ]
            ];
        }

        // Get connected external apps
        $homeAppIds = $homeStreamApps->pluck('app_id')->toArray();
        
        // Get apps that are connected to home stream apps but are not in the home stream
        $connectedAppIds = collect();
        
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);
        
        // Get apps that have integrations FROM home stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);
        
        // Remove home stream app IDs to get only external apps
        $externalAppIds = $connectedAppIds->diff($homeAppIds)->unique()->values();
        
        $externalApps = App::whereIn('app_id', $externalAppIds)->with('stream')->get();

        // Add external app nodes
        foreach ($externalApps as $app) {
            $nodes[] = [
                'id' => (string)$app->app_id,
                'data' => [
                    'label' => $app->app_name . "\nID: " . $app->app_id . "\nStream: " . strtoupper($app->stream->stream_name ?? 'external'),
                    'app_id' => $app->app_id,
                    'stream_name' => $app->stream->stream_name ?? 'external',
                    'lingkup' => $app->stream->stream_name ?? 'external',
                    'is_home_stream' => false,
                ]
            ];
        }

        // Remove any duplicate nodes by app_id (safety check)
        $uniqueNodes = [];
        $seenIds = [];
        
        foreach ($nodes as $node) {
            $nodeId = $node['id'];
            if (!in_array($nodeId, $seenIds)) {
                $uniqueNodes[] = $node;
                $seenIds[] = $nodeId;
            }
        }
        $nodes = $uniqueNodes;
        
        // Get all edges
        $allAppIds = collect($nodes)->pluck('data.app_id')->filter(fn($id) => $id > 0)->toArray();
        
        $integrations = AppIntegration::whereIn('source_app_id', $allAppIds)
            ->whereIn('target_app_id', $allAppIds)
            ->with('connectionType')
            ->get();

        $edges = [];
        foreach ($integrations as $integration) {
            $edges[] = [
                'id' => $integration->source_app_id . '-' . $integration->target_app_id,
                'source' => (string)$integration->source_app_id,
                'target' => (string)$integration->target_app_id,
                'data' => [
                    'label' => $integration->connectionType->type_name ?? 'Unknown',
                    'connection_type' => strtolower($integration->connectionType->type_name ?? 'direct'),
                ]
            ];
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
