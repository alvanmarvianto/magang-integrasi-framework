<?php

namespace App\Services;

use App\Services\StreamConfigurationService;
use App\DTOs\DiagramDataDTO;
use App\DTOs\DiagramEdgeDTO;
use App\DTOs\DiagramNodeDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Collection;

class DiagramService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository,
        private readonly StreamConfigurationService $streamConfigService
    ) {}

    /**
     * Validate stream name
     */
    public function validateStreamName(string $streamName): bool
    {
        // Try direct validation first
        if ($this->streamConfigService->isStreamAllowed($streamName)) {
            return true;
        }
        
        // If that fails, try with "Stream " prefix
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }
        
        $prefixedStreamName = 'Stream ' . ucfirst($cleanStreamName);
        return $this->streamConfigService->isStreamAllowed($prefixedStreamName);
    }

    /**
     * Get allowed streams
     */
    public function getAllowedStreams(): array
    {
        return $this->streamConfigService->getAllowedDiagramStreams();
    }

    /**
     * Get stream model by name
     */
    public function getStream(string $streamName): ?Stream
    {
        return Stream::where('stream_name', $streamName)->first();
    }

    /**
     * Get apps in a specific stream
     */
    public function getStreamApps(Stream $stream): Collection
    {
        return App::with('stream')->where('stream_id', $stream->getAttribute('stream_id'))->get();
    }

    /**
     * Get connected external apps for a stream
     */
    public function getConnectedExternalApps(array $homeAppIds): Collection
    {
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->whereNotIn('source_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();

        // Get apps that have integrations FROM home stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->whereNotIn('target_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();

        // Combine and remove home stream app IDs to get only external apps
        $externalAppIds = $sourceAppIds->merge($targetAppIds)->unique();

        return App::whereIn('app_id', $externalAppIds)->with('stream')->get();
    }

    /**
     * Get integrations involving specific apps using DTOs
     */
    public function getIntegrations(array $appIds, array $homeAppIds = []): Collection
    {
        // Fetch integrations where EITHER endpoint is in the provided app set
        // This ensures we also include edges from/to external apps that connect to home apps
        $integrations = AppIntegration::where(function ($q) use ($appIds) {
                $q->whereIn('source_app_id', $appIds)
                  ->orWhereIn('target_app_id', $appIds);
            })
            ->with(['connections.connectionType', 'sourceApp', 'targetApp'])
            ->get();

        // If homeAppIds provided, keep only integrations where at least one side is a home app
        // (This is redundant when $appIds === $homeAppIds but kept for clarity when they differ)
        if (!empty($homeAppIds)) {
            $integrations = $integrations->filter(function ($integration) use ($homeAppIds) {
                return in_array($integration->source_app_id, $homeAppIds)
                    || in_array($integration->target_app_id, $homeAppIds);
            });
        }

        return $integrations;
    }

    /**
     * Save layout configuration using DTOs
     */
    public function saveLayout(string $streamName, array $nodesLayout, array $streamConfig, array $edgesLayout = []): void
    {
        if (!$this->validateStreamName($streamName)) {
            throw new \InvalidArgumentException('Invalid stream name');
        }

        $this->streamLayoutRepository->saveLayout($streamName, $nodesLayout, $edgesLayout, $streamConfig);
    }

        /**
     * Get Vue Flow data for a specific stream using DTOs
     */
    public function getVueFlowData(string $streamName, bool $isUserView = false): DiagramDataDTO
    {
        // Get stream from database
        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            throw new \InvalidArgumentException("Stream not found: {$streamName}");
        }
        
        // Get apps and integrations for this stream
        $streamApps = $this->getStreamApps($stream);
        $appIds = $streamApps->pluck('app_id')->toArray();
        $integrations = $this->getIntegrations($appIds, $appIds);
        $externalApps = $this->getConnectedExternalApps($appIds);

        // Initialize node and edge transformers
        $nodeTransformer = new NodeTransformer();
        $edgeTransformer = new EdgeTransformer();

    $nodes = [];
    $edges = collect(); // Use Collection to simplify toArray() later
    $savedLayout = null;

    // Add the parent stream node (use DB color for border)
    $streamNode = $nodeTransformer->createStreamNode($streamName, !$isUserView, $stream->color ?? null);
        $nodes[] = $streamNode->toArray();

    if ($streamApps->isNotEmpty()) {
            // Add stream apps - pass stream color to ensure app nodes get proper border colors
            $streamNodeApps = $nodeTransformer->transformHomeStreamApps($streamApps, $streamName, !$isUserView, $stream->color);
            $nodes = array_merge($nodes, $streamNodeApps->toArray());
            
            // Add external apps
            $externalNodeApps = $nodeTransformer->transformExternalApps($externalApps, !$isUserView);
            $nodes = array_merge($nodes, $externalNodeApps->toArray());

            // Get saved layout using stream ID instead of stream name
            $savedLayout = $this->streamLayoutRepository->getLayoutDataById($stream->stream_id);
            
            // Debug: Check what layouts exist in the database
            try {
                $allLayouts = \App\Models\StreamLayout::with('stream')->get()->map(function($layout) {
                    return $layout->stream ? $layout->stream->stream_name : "No stream (ID: {$layout->stream_id})";
                })->toArray();
                \Log::info("DiagramService - All stream layouts in DB: ", $allLayouts);
                
                // Also show the cleaned stream name for debugging
                $cleanStreamName = strtolower(trim($streamName));
                if (str_starts_with($cleanStreamName, 'stream ')) {
                    $cleanStreamName = substr($cleanStreamName, 7);
                }
                \Log::info("DiagramService - Looking for layout with stream ID: {$stream->stream_id} ('{$streamName}' -> '{$cleanStreamName}')");
                \Log::info("DiagramService - Found layout: " . ($savedLayout ? 'YES' : 'NO'));
                if ($savedLayout) {
                    \Log::info("DiagramService - Layout contents: ", $savedLayout);
                }
            } catch (\Exception $e) {
                \Log::error("DiagramService - Debug error: " . $e->getMessage());
            }

            // If admin view, sanitize savedLayout edges to black/no-arrow so UI doesn't pick colored legacy data
            if (!$isUserView && is_array($savedLayout)) {
                // Handle both keys: edges_layout and legacy edges
                foreach (['edges_layout', 'edges'] as $edgeListKey) {
                    if (isset($savedLayout[$edgeListKey]) && is_array($savedLayout[$edgeListKey])) {
                        foreach ($savedLayout[$edgeListKey] as $idx => $edge) {
                            if (!is_array($savedLayout[$edgeListKey][$idx])) continue;
                            // Ensure style exists
                            if (!isset($savedLayout[$edgeListKey][$idx]['style']) || !is_array($savedLayout[$edgeListKey][$idx]['style'])) {
                                $savedLayout[$edgeListKey][$idx]['style'] = [];
                            }
                            $savedLayout[$edgeListKey][$idx]['style']['stroke'] = '#000000';
                            $savedLayout[$edgeListKey][$idx]['style']['strokeWidth'] = $savedLayout[$edgeListKey][$idx]['style']['strokeWidth'] ?? 2;
                            // Remove arrows/markers
                            unset($savedLayout[$edgeListKey][$idx]['markerEnd'], $savedLayout[$edgeListKey][$idx]['markerStart']);
                            // Normalize color fields to black
                            $savedLayout[$edgeListKey][$idx]['color'] = '#000000';
                            if (!isset($savedLayout[$edgeListKey][$idx]['data']) || !is_array($savedLayout[$edgeListKey][$idx]['data'])) {
                                $savedLayout[$edgeListKey][$idx]['data'] = [];
                            }
                            $savedLayout[$edgeListKey][$idx]['data']['color'] = '#000000';
                            // Ensure type remains smoothstep for consistency
                            $savedLayout[$edgeListKey][$idx]['type'] = $savedLayout[$edgeListKey][$idx]['type'] ?? 'smoothstep';
                            // Disable animation if present
                            if (isset($savedLayout[$edgeListKey][$idx]['animated'])) {
                                $savedLayout[$edgeListKey][$idx]['animated'] = false;
                            }
                        }
                    }
                }
                // Set a config flag to tell the frontend to disable markers and force black
                if (!isset($savedLayout['stream_config']) || !is_array($savedLayout['stream_config'])) {
                    $savedLayout['stream_config'] = [];
                }
                $savedLayout['stream_config']['forceEdgeBlackNoArrow'] = true;
            }

            // Transform edges using EdgeTransformer with saved layout
            $edges = $isUserView
                ? $edgeTransformer->transformForUser($integrations, $savedLayout)
                : $edgeTransformer->transformForAdmin($integrations, $savedLayout);
        }

        return DiagramDataDTO::create(
            $nodes,
            $edges instanceof \Illuminate\Support\Collection ? $edges->toArray() : (array)$edges,
            $savedLayout,
            $this->getStreamMetadata($streamApps, $externalApps)
        );
    }

    /**
     * Get metadata for the stream including available node types
     */
    private function getStreamMetadata($streamApps, $externalApps): array
    {
        $allApps = $streamApps->merge($externalApps);
        
        // Get unique stream names from all apps to build node type legend
        $nodeTypes = [];
        $processedStreams = new \stdClass(); // Use object to track processed streams
        
    // Get all stream colors and descriptions from database in one query
    $streamColors = Stream::pluck('color', 'stream_name')->toArray();
    $streamDescriptions = Stream::pluck('description', 'stream_name')->toArray();
        
        foreach ($allApps as $app) {
            $streamName = $app->stream?->stream_name ?? 'external';
            $streamKey = strtolower($streamName);
            
            if (!property_exists($processedStreams, $streamKey)) {
                $processedStreams->$streamKey = true;
                
                // Get color from database, default to gray if not found
                $streamColor = $streamColors[$streamName] ?? '#6b7280';
        // Get description if available
        $streamDescription = $streamDescriptions[$streamName] ?? null;
                
                // Map stream names to readable labels and CSS classes
                $nodeTypes[] = [
            // Prefer description for legend label; fallback to stream name
            'label' => $streamDescription ?: $streamName,
                    'type' => 'circle',
                    'class' => $this->getStreamCssClass($streamName),
                    'stream_name' => $streamName,
            'stream_description' => $streamDescription,
                    'color' => $streamColor
                ];
            }
        }
        
        return [
            'node_types' => $nodeTypes,
            'total_apps' => $allApps->count(),
            'home_apps' => $streamApps->count(),
            'external_apps' => $externalApps->count(),
        ];
    }

    /**
     * Get CSS class for stream
     */
    private function getStreamCssClass(string $streamName): string
    {
        return strtolower(str_replace([' ', '_'], '-', $streamName));
    }
}
