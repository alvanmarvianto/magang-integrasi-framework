<?php

namespace App\Services;

use App\Services\StreamConfigurationService;
use App\Services\EdgeTransformer;
use App\DTOs\DiagramDataDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Models\StreamLayout;
use App\Models\AppIntegrationFunction;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use Illuminate\Support\Collection;

class DiagramService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository,
        private readonly AppLayoutRepositoryInterface $appLayoutRepository,
        private readonly StreamConfigurationService $streamConfigService,
        private readonly EdgeTransformer $edgeTransformer
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
     * Build a single app layout diagram showing functions inside and connected external apps.
     * - Single app nest (the selected app)
     * - Function nodes inside the app nest
     * - External connected app nodes outside the nest (only if they connect to functions)
     * - Edges from functions to external apps based on integrations
     */
    public function getAppLayoutVueFlowData(int $appId, bool $isUserView = false): DiagramDataDTO
    {
        // Get the target app
        $app = App::with(['stream', 'integrationFunctions.integration'])->find($appId);
        if (!$app) {
            return DiagramDataDTO::withError('App not found');
        }

        // Get all functions for this app
        $allFunctions = $app->integrationFunctions;
        
        if ($allFunctions->isEmpty()) {
            return DiagramDataDTO::withError('No functions found for this app');
        }

        // Deduplicate functions by function name to avoid duplicates when functions have multiple integrations
        $functions = $allFunctions->unique('function_name');

        // Get integration IDs from functions to find connected external apps
        $functionIntegrationIds = $allFunctions->pluck('integration_id')->unique()->values();
        
        // Get all integrations that involve these functions
        $integrations = AppIntegration::whereIn('integration_id', $functionIntegrationIds)
            ->with(['sourceApp', 'targetApp', 'functions', 'connections.connectionType'])
            ->get();

        // Get external apps that are connected via functions (not the target app itself)
        $externalAppIds = $integrations->map(function ($integration) use ($appId) {
            return $integration->source_app_id == $appId 
                ? $integration->target_app_id 
                : $integration->source_app_id;
        })->unique()->filter(function ($id) use ($appId) {
            return $id != $appId; // Exclude the target app itself
        })->values();
        
        $externalApps = App::whereIn('app_id', $externalAppIds)->with('stream')->get();

        // Build the main app nest
        $appNest = [
            'id' => (string)$appId,
            'data' => [
                'label' => $app->app_name,
                'app_id' => $appId,
                'app_name' => $app->app_name,
                'stream_name' => $app->stream->stream_name ?? '',
                'lingkup' => $app->stream->stream_name ?? '',
                'is_home_stream' => true,
                'is_parent_node' => true,
            ],
            'type' => 'app',
            'position' => ['x' => 50, 'y' => 50],
            'style' => [
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'width' => 400,
                'height' => 300,
                'border' => '2px solid ' . ($app->stream->color ?? '#3b82f6'),
            ],
        ];

        // Build function nodes inside the app nest
        $functionNodes = $functions->map(function ($func, $index) use ($appId, $app) {
            $row = intval($index / 2);
            $col = $index % 2;
            $x = 30 + ($col * 130);
            $y = 50 + ($row * 80);
            
            // Get the stream color for consistent border color
            $streamColor = $app->stream->color ?? '#3b82f6';
            
            return [
                'id' => 'f-' . $func->function_name, // Use function name as ID to avoid duplicates
                'data' => [
                    'label' => $func->function_name, // Show function name, not app name
                    'function_name' => $func->function_name,
                    'app_id' => $appId,
                    'app_name' => $func->function_name,
                    'integration_id' => $func->integration_id,
                    'stream_name' => $app->app_name ?? '',
                    'lingkup' => $app->stream->stream_name ?? '',
                    'color' => $streamColor, // Provide explicit color hint for UI
                    'is_home_stream' => true,
                    'is_parent_node' => false,
                ],
                'type' => 'function', // Use function type instead of app
                'parentNode' => (string)$appId,
                'extent' => 'parent',
                'position' => ['x' => $x, 'y' => $y],
                'style' => [
                    'backgroundColor' => '#f0f9ff',
                    'border' => '2px solid ' . $streamColor,
                    'width' => '120px',
                    'height' => '60px',
                    'borderRadius' => '8px',
                    'borderColor' => $streamColor,
                ],
            ];
        })->values()->all();

        // Build external app nodes
        $externalAppNodes = $externalApps->map(function ($extApp, $index) use ($externalApps) {
            $angle = ($index * 2 * M_PI) / max($externalApps->count(), 1);
            $radius = 300;
            $centerX = 250;
            $centerY = 200;
            
            // Get the stream color for consistent border color
            $streamColor = $extApp->stream->color ?? '#6b7280';
            
            return [
                'id' => 'ext-' . $extApp->app_id,
                'data' => [
                    'label' => $extApp->app_name,
                    'app_id' => $extApp->app_id,
                    'app_name' => $extApp->app_name,
                    'stream_name' => $extApp->stream->stream_name ?? '',
                    'lingkup' => $extApp->stream->stream_name ?? '',
                    'color' => $streamColor, // Provide explicit color hint for UI
                    'is_home_stream' => false,
                    'is_parent_node' => false,
                ],
                'type' => 'app',
                'position' => [
                    'x' => $centerX + $radius * cos($angle),
                    'y' => $centerY + $radius * sin($angle),
                ],
                'style' => [
                    'backgroundColor' => '#f9fafb',
                    'border' => '2px solid ' . $streamColor,
                    'width' => '120px',
                    'height' => '60px',
                    'borderRadius' => '8px',
                    'borderColor' => $streamColor,
                ],
            ];
        })->values()->all();

        // Build edges from functions to external apps using proper integration data
        $edges = [];
        foreach ($integrations as $integration) {
            $isAppSource = $integration->source_app_id == $appId;
            $externalAppId = $isAppSource ? $integration->target_app_id : $integration->source_app_id;
            
            // Build connection type data once per integration
            $types = $integration->relationLoaded('connections')
                ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)->filter()->unique()->values()->toArray()
                : [];
            $connectionType = empty($types) ? 'direct' : implode(' / ', $types);
            
            // Find functions for this integration from the original collection (not deduplicated)
            $integrationFunctions = $allFunctions->where('integration_id', $integration->integration_id);
            
            foreach ($integrationFunctions as $func) {
                $edges[] = [
                    'id' => 'edge-' . $func->getKey() . '-' . $externalAppId,
                    'source' => 'f-' . $func->function_name, // Use function name as source to match deduplicated nodes
                    'target' => 'ext-' . $externalAppId,
                    'type' => 'smoothstep',
                    'style' => [
                        'stroke' => $isUserView ? '#374151' : '#000000', // Black for admin, gray for user
                        'strokeWidth' => 2,
                    ],
                    'data' => [
                        'connection_type' => strtolower($connectionType),
                        'connection_types' => array_map(fn($n) => ['name' => $n], $types),
                        // Detailed connections payload for sidebar rendering
                        'connections' => $integration->relationLoaded('connections')
                            ? $integration->connections->map(function ($conn) use ($integration) {
                                return [
                                    'connection_type_id' => $conn->connection_type_id,
                                    'connection_type_name' => $conn->connectionType?->type_name,
                                    'connection_color' => $conn->connectionType->color ?? null,
                                    'source' => [
                                        'app_id' => $integration->sourceApp?->app_id ?? null,
                                        'app_name' => $integration->sourceApp?->app_name ?? null,
                                        'inbound' => $conn->source_inbound,
                                        'outbound' => $conn->source_outbound,
                                    ],
                                    'target' => [
                                        'app_id' => $integration->targetApp?->app_id ?? null,
                                        'app_name' => $integration->targetApp?->app_name ?? null,
                                        'inbound' => $conn->target_inbound,
                                        'outbound' => $conn->target_outbound,
                                    ],
                                ];
                            })->toArray()
                            : [],
                        'color' => $isUserView ? '#374151' : '#000000',
                        'integration_id' => $integration->integration_id,
                        'sourceApp' => [
                            'app_id' => $integration->sourceApp?->app_id ?? 0,
                            'app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                        ],
                        'targetApp' => [
                            'app_id' => $integration->targetApp?->app_id ?? 0,
                            'app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                        ],
                        'source_app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                        'target_app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                        'function_name' => $func->function_name,
                    ],
                ];
            }
        }

        // Combine all nodes
        $nodes = array_merge([$appNest], $functionNodes, $externalAppNodes);

        // Load saved layout from database
        $savedLayoutDTO = $this->appLayoutRepository->findByAppId($appId);
        $savedLayout = null;
        
        // Check if we have meaningful saved layout data (not just empty arrays)
        $hasMeaningfulSavedLayout = false;
        if ($savedLayoutDTO) {
            $nodesLayout = $savedLayoutDTO->nodesLayout ?? []; // Use camelCase property name
            $edgesLayout = $savedLayoutDTO->edgesLayout ?? []; // Use camelCase property name
            $appConfig = $savedLayoutDTO->appConfig ?? [];     // Use camelCase property name
            
            // Only consider it a meaningful layout if nodes_layout has actual data
            $hasMeaningfulSavedLayout = !empty($nodesLayout) && is_array($nodesLayout) && count($nodesLayout) > 0;
        }
        
        if ($hasMeaningfulSavedLayout) {
            // Use saved layout data
            $savedLayout = [
                'nodes_layout' => $savedLayoutDTO->nodesLayout ?? [], // Use camelCase property name
                'edges_layout' => $savedLayoutDTO->edgesLayout ?? [], // Use camelCase property name
                'app_config' => $savedLayoutDTO->appConfig ?? [],     // Use camelCase property name
            ];
                        
            // Apply saved positions and styles to nodes if they exist
            foreach ($nodes as &$node) {
                $nodeId = $node['id'];
                if (isset($savedLayout['nodes_layout'][$nodeId])) {
                    $savedNodeData = $savedLayout['nodes_layout'][$nodeId];
                    
                    // Apply saved position
                    if (isset($savedNodeData['position'])) {
                        $node['position'] = $savedNodeData['position'];
                    }
                    
                    // Apply saved style
                    if (isset($savedNodeData['style'])) {
                        $node['style'] = array_merge($node['style'] ?? [], $savedNodeData['style']);
                    }
                }
            }
            unset($node); // Break reference
            
            // Apply saved edge data if available
            if (!empty($savedLayout['edges_layout'])) {
                foreach ($edges as &$edge) {
                    $savedEdge = collect($savedLayout['edges_layout'])->firstWhere('id', $edge['id']);
                    if ($savedEdge) {
                        // Apply saved edge style and data
                        if (isset($savedEdge['style'])) {
                            $edge['style'] = array_merge($edge['style'] ?? [], $savedEdge['style']);
                        }
                        if (isset($savedEdge['data'])) {
                            $edge['data'] = array_merge($edge['data'] ?? [], $savedEdge['data']);
                        }
                    }
                }
                unset($edge); // Break reference
            }
        } else {
            // Build default layout structure if no meaningful saved layout            
            $nodesLayout = [
                (string)$appId => [
                    'position' => ['x' => 50, 'y' => 50],
                    'style' => [
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'width' => 400,
                        'height' => 300,
                        'border' => '2px solid ' . ($app->stream->color ?? '#3b82f6'),
                    ],
                ],
            ];
            
            // Add function nodes to layout
            foreach ($functions as $index => $func) {
                $funcId = 'f-' . $func->function_name;
                $row = intval($index / 2);
                $col = $index % 2;
                $x = 30 + ($col * 130);
                $y = 50 + ($row * 80);
                
                $nodesLayout[$funcId] = [
                    'position' => ['x' => $x, 'y' => $y],
                    'style' => [
                        'backgroundColor' => '#f0f9ff',
                        'border' => '2px solid ' . ($app->stream->color ?? '#3b82f6'),
                        'width' => '120px',
                        'height' => '60px',
                        'borderRadius' => '8px',
                        'borderColor' => ($app->stream->color ?? '#3b82f6'),
                    ],
                ];
            }
            
            // Add external app nodes to layout
            foreach ($externalApps as $index => $extApp) {
                $extId = 'ext-' . $extApp->app_id;
                $angle = ($index * 2 * M_PI) / max($externalApps->count(), 1);
                $radius = 300;
                $centerX = 250;
                $centerY = 200;
                
                $nodesLayout[$extId] = [
                    'position' => [
                        'x' => $centerX + $radius * cos($angle),
                        'y' => $centerY + $radius * sin($angle),
                    ],
                    'style' => [
                        'backgroundColor' => '#f9fafb',
                        'border' => '2px solid ' . ($extApp->stream->color ?? '#6b7280'),
                        'width' => '120px',
                        'height' => '60px',
                        'borderRadius' => '8px',
                    ],
                ];
            }

            $savedLayout = [
                'nodes_layout' => $nodesLayout,
                'edges_layout' => $edges,
                'app_config' => [
                    'lastUpdated' => now()->toISOString(),
                    'totalNodes' => count($nodes),
                    'totalEdges' => count($edges),
                    'app_id' => $appId,
                    'app_name' => $app->app_name,
                ],
            ];
        }

        // Config
        $config = [
            'app_id' => $appId,
            'app_name' => $app->app_name,
            'total_functions' => $functions->count(),
            'external_apps' => $externalApps->count(),
            'node_types' => [
                [
                    'label' => $app->stream->stream_name ?? 'Home Stream',
                    'type' => 'app',
                    'class' => 'home-stream',
                    'stream_name' => $app->stream->stream_name ?? '',
                    'color' => $app->stream->color ?? '#3b82f6',
                ],
            ],
        ];

        // Add external app stream types to node_types
        foreach ($externalApps->unique('stream.stream_name') as $extApp) {
            if ($extApp->stream && $extApp->stream->stream_name !== ($app->stream->stream_name ?? '')) {
                $config['node_types'][] = [
                    'label' => $extApp->stream->stream_name,
                    'type' => 'app',
                    'class' => strtolower(str_replace([' ', '_'], '-', $extApp->stream->stream_name)),
                    'stream_name' => $extApp->stream->stream_name,
                    'color' => $extApp->stream->color ?? '#6b7280',
                ];
            }
        }

        return DiagramDataDTO::create($nodes, $edges, $savedLayout, $config);
    }

    /**
     * Build an app-nested function diagram for a given stream.
     * - Parent stream node (group)
     * - App parent nodes (groups) under the stream
     * - Function nodes under each app
     * - Edges derived from integrations (by integration_id)
     */
    public function getAppFunctionVueFlowData(string $streamName, bool $isUserView = false): DiagramDataDTO
    {
        // Validate stream
        if (!$this->validateStreamName($streamName)) {
            return DiagramDataDTO::withError('Stream not allowed');
        }

        $stream = $this->getStream($streamName);
        if (!$stream) {
            return DiagramDataDTO::withError('Stream not found');
        }

        // Apps in the stream
        $streamApps = $this->getStreamApps($stream);
        $homeAppIds = $streamApps->pluck('app_id')->values()->all();

        // Build parent stream node (group)
        $cleanStreamId = strtolower(trim(str_starts_with(strtolower($streamName), 'stream ')
            ? substr($streamName, 7)
            : $streamName));

    $streamParentNode = [
            'id' => $cleanStreamId,
            'data' => [
        'label' => $stream->stream_name,
                'app_id' => -1,
        'app_name' => $stream->stream_name,
        'stream_name' => $stream->stream_name,
        'lingkup' => $stream->stream_name,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ],
            'type' => 'stream',
            'position' => ['x' => 0, 'y' => 0],
            'style' => [
                'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
                'width' => 600,
                'height' => 400,
                'border' => '2px solid ' . ($stream->color ?? '#000000'),
            ],
        ];

        // App parent nodes
    $appParentNodes = $streamApps->map(function ($app) use ($stream, $cleanStreamId) {
            return [
        'id' => (string)$app->getAttribute('app_id'),
                'data' => [
                    'label' => (string)$app->getAttribute('app_name'),
                    'app_id' => (int)$app->getAttribute('app_id'),
                    'app_name' => (string)$app->getAttribute('app_name'),
                    'stream_name' => $stream->stream_name,
                    'lingkup' => $stream->stream_name,
                    'is_home_stream' => true,
                    'is_parent_node' => true,
                ],
                'type' => 'app',
        'parentNode' => $cleanStreamId,
        'extent' => 'parent',
        'position' => ['x' => 50, 'y' => 50],
                'style' => [
                    'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
            'width' => 320,
            'height' => 240,
                    'border' => '2px solid ' . ($app->stream->color ?? '#999999'),
                ],
            ];
        })->values()->all();

        // Function nodes under each app
        $functions = AppIntegrationFunction::whereIn('app_id', $homeAppIds)
            ->with(['app', 'integration'])
            ->get();

    $functionNodes = $functions->map(function ($func) use ($stream) {
            $app = $func->app;
            return [
                'id' => 'f-' . (string)$func->getKey(),
                'data' => [
                    'label' => (string)$func->getAttribute('function_name'),
                    'app_id' => (int)$func->getAttribute('app_id'),
                    'app_name' => (string)($app?->getAttribute('app_name') ?? ''),
                    'stream_name' => $stream->stream_name,
                    'lingkup' => $stream->stream_name,
                    'is_home_stream' => true,
                    'is_parent_node' => false,
                ],
                'type' => 'app',
                'parentNode' => (string)$func->getAttribute('app_id'),
        'extent' => 'parent',
                'position' => ['x' => 80, 'y' => 80],
                'style' => [
                    'backgroundColor' => '#fffef0',
                    'border' => '2px solid ' . ($app?->stream?->color ?? '#fbff00'),
                    'borderColor' => ($app?->stream?->color ?? '#fbff00'),
                    'width' => '120px',
                    'height' => '80px',
                    'borderRadius' => '8px',
                ],
            ];
        })->values()->all();

        // Edges between function nodes across apps, grouped by integration_id
        $funcsByIntegration = $functions->groupBy('integration_id');
        $edges = [];

        // Preload integrations to know source/target apps
        $integrationIds = $funcsByIntegration->keys()->values()->all();
        $integrations = AppIntegration::whereIn('integration_id', $integrationIds)
            ->with(['sourceApp', 'targetApp'])
            ->get()
            ->keyBy('integration_id');

        foreach ($funcsByIntegration as $integrationId => $funcRows) {
            $integration = $integrations->get($integrationId);
            if (!$integration) { continue; }

            $sourceAppId = (int)$integration->getAttribute('source_app_id');
            $targetAppId = (int)$integration->getAttribute('target_app_id');

            $sourceFuncs = $funcRows->where('app_id', $sourceAppId)->values();
            $targetFuncs = $funcRows->where('app_id', $targetAppId)->values();

            if ($sourceFuncs->isEmpty() || $targetFuncs->isEmpty()) {
                // If we don't have both sides, skip creating a function-to-function edge
                continue;
            }

            // Pair first source and first target function for a single edge per integration
            $sf = $sourceFuncs->first();
            $tf = $targetFuncs->first();

            $edges[] = [
                'id' => (string)$sourceAppId . '-' . (string)$targetAppId,
                'source' => (string)$sourceAppId,
                'target' => (string)$targetAppId,
                'type' => 'smoothstep',
                'style' => [
                    'stroke' => '#000000',
                    'strokeWidth' => 2,
                ],
                'data' => [
                    'connection_type' => 'direct',
                    'connection_types' => [],
                    'connections' => [],
                    'color' => '#000000',
                    'integration_id' => (int)$integrationId,
                    'sourceApp' => [
                        'app_id' => $sourceAppId,
                        'app_name' => (string)($integration->sourceApp?->getAttribute('app_name') ?? ''),
                    ],
                    'targetApp' => [
                        'app_id' => $targetAppId,
                        'app_name' => (string)($integration->targetApp?->getAttribute('app_name') ?? ''),
                    ],
                    'source_app_name' => (string)($integration->sourceApp?->getAttribute('app_name') ?? ''),
                    'target_app_name' => (string)($integration->targetApp?->getAttribute('app_name') ?? ''),
                ],
                'sourceHandle' => 'left-source',
                'targetHandle' => 'right-target',
            ];
        }

        // Combine nodes: stream parent + app parents + function nodes
        $nodes = array_merge([$streamParentNode], $appParentNodes, $functionNodes);

        // Layout data shape compatible with existing consumers
        // Build nodes_layout for stream and apps (default positions/styles)
        $nodesLayout = [
            $cleanStreamId => [
                'position' => ['x' => 0, 'y' => 0],
                'style' => [
                    'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
                    'width' => 600,
                    'height' => 400,
                    'border' => '2px solid ' . ($stream->color ?? '#000000'),
                ],
            ],
        ];
        foreach ($streamApps as $app) {
            $appIdStr = (string)$app->getAttribute('app_id');
            $nodesLayout[$appIdStr] = [
                'position' => ['x' => 100, 'y' => 100],
                'style' => [
                    'cursor' => 'grab',
                    'width' => '120px',
                    'height' => '80px',
                    'borderRadius' => '8px',
                    'backgroundColor' => '#fffef0',
                    'border' => '2px solid ' . ($app->stream->color ?? '#fbff00'),
                    'borderColor' => ($app->stream->color ?? '#fbff00'),
                ],
            ];
        }

        $layout = [
            'nodes_layout' => $nodesLayout,
            'edges_layout' => array_values($edges),
            'stream_config' => [
                'lastUpdated' => now()->toISOString(),
                'totalNodes' => count($nodes) - 1, // exclude parent stream node
                'totalEdges' => count($edges),
                'forceEdgeBlackNoArrow' => true,
            ],
        ];

        // Config
        $config = [
            'node_types' => [
                [
                    'label' => $stream->description ?? ('Aplikasi ' . strtoupper($cleanStreamId)),
                    'type' => 'circle',
                    'class' => 'stream-' . $cleanStreamId,
                    'stream_name' => $stream->stream_name,
                    'stream_description' => $stream->description ?? '',
                    'color' => $stream->color ?? '#000000',
                ],
            ],
            'total_apps' => count($homeAppIds),
            'home_apps' => count($homeAppIds),
            'external_apps' => 0,
        ];

        return DiagramDataDTO::create($nodes, $edges, $layout, $config);
    }

    /**
     * Validate if a stream is allowed
     */
    public function isStreamAllowed(string $streamName): bool
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
                StreamLayout::with('stream')->get()->map(function($layout) {
                    return $layout->stream ? $layout->stream->stream_name : "No stream (ID: {$layout->stream_id})";
                })->toArray();

                // Also show the cleaned stream name for debugging
                $cleanStreamName = strtolower(trim($streamName));
                if (str_starts_with($cleanStreamName, 'stream ')) {
                    $cleanStreamName = substr($cleanStreamName, 7);
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
