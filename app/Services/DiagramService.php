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
    ) {
    }

    /**
     * Validate stream name
     */
    public function validateStreamName(string $streamName): bool
    {
        if ($this->streamConfigService->isStreamAllowed($streamName)) {
            return true;
        }

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
        $app = App::with(['stream', 'integrationFunctions.integration'])->find($appId);
        if (!$app) {
            return DiagramDataDTO::withError('App not found');
        }

        $allFunctions = $app->integrationFunctions;

        if ($allFunctions->isEmpty()) {
            return DiagramDataDTO::withError('No functions found for this app');
        }

        $functions = $allFunctions->unique('function_name');

        $functionIntegrationIds = $allFunctions->pluck('integration_id')->unique()->values();

        $integrations = AppIntegration::whereIn('integration_id', $functionIntegrationIds)
            ->with(['sourceApp', 'targetApp', 'functions', 'connections.connectionType'])
            ->get();

        $externalAppIds = $integrations->map(function ($integration) use ($appId) {
            return $integration->source_app_id == $appId
                ? $integration->target_app_id
                : $integration->source_app_id;
        })->unique()->filter(function ($id) use ($appId) {
            return $id != $appId;
        })->values();

        $externalApps = App::whereIn('app_id', $externalAppIds)->with('stream')->get();

        $appNest = [
            'id' => (string) $appId,
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

        $functionNodes = $functions->map(function ($func, $index) use ($appId, $app) {
            $row = intval($index / 2);
            $col = $index % 2;
            $x = 30 + ($col * 130);
            $y = 50 + ($row * 80);

            $streamColor = $app->stream->color ?? '#3b82f6';

            return [
                'id' => 'f-' . $func->function_name,
                'data' => [
                    'label' => $func->function_name,
                    'function_name' => $func->function_name,
                    'app_id' => $appId,
                    'app_name' => $func->function_name,
                    'integration_id' => $func->integration_id,
                    'stream_name' => $app->app_name ?? '',
                    'lingkup' => $app->stream->stream_name ?? '',
                    'color' => $streamColor,
                    'is_home_stream' => true,
                    'is_parent_node' => false,
                ],
                'type' => 'function',
                'parentNode' => (string) $appId,
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

        $externalAppNodes = $externalApps->map(function ($extApp, $index) use ($externalApps) {
            $angle = ($index * 2 * M_PI) / max($externalApps->count(), 1);
            $radius = 300;
            $centerX = 250;
            $centerY = 200;

            $streamColor = $extApp->stream->color ?? '#6b7280';

            return [
                'id' => 'ext-' . $extApp->app_id,
                'data' => [
                    'label' => $extApp->app_name,
                    'app_id' => $extApp->app_id,
                    'app_name' => $extApp->app_name,
                    'stream_name' => $extApp->stream->stream_name ?? '',
                    'lingkup' => $extApp->stream->stream_name ?? '',
                    'color' => $streamColor,
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

        $edges = [];
        foreach ($integrations as $integration) {
            $isAppSource = $integration->source_app_id == $appId;
            $externalAppId = $isAppSource ? $integration->target_app_id : $integration->source_app_id;

            $types = $integration->relationLoaded('connections')
                ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)->filter()->unique()->values()->toArray()
                : [];
            $connectionType = empty($types) ? 'direct' : implode(' / ', $types);

            $integrationFunctions = $allFunctions->where('integration_id', $integration->integration_id);

            foreach ($integrationFunctions as $func) {
                $edges[] = [
                    'id' => 'edge-' . $func->getKey() . '-' . $externalAppId,
                    'source' => 'f-' . $func->function_name,
                    'target' => 'ext-' . $externalAppId,
                    'type' => 'smoothstep',
                    'style' => [
                        'stroke' => $isUserView ? '#374151' : '#000000',
                        'strokeWidth' => 2,
                    ],
                    'data' => [
                        'connection_type' => strtolower($connectionType),
                        'connection_types' => array_map(fn($n) => ['name' => $n], $types),
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

        $nodes = array_merge([$appNest], $functionNodes, $externalAppNodes);

        $savedLayoutDTO = $this->appLayoutRepository->findByAppId($appId);
        $savedLayout = null;

        $hasMeaningfulSavedLayout = false;
        if ($savedLayoutDTO) {
            $nodesLayout = $savedLayoutDTO->nodesLayout ?? [];

            $hasMeaningfulSavedLayout = !empty($nodesLayout) && is_array($nodesLayout) && count($nodesLayout) > 0;
        }

        if ($hasMeaningfulSavedLayout) {
            $savedLayout = [
                'nodes_layout' => $savedLayoutDTO->nodesLayout ?? [],
                'edges_layout' => $savedLayoutDTO->edgesLayout ?? [],
                'app_config' => $savedLayoutDTO->appConfig ?? [],
            ];

            foreach ($nodes as &$node) {
                $nodeId = $node['id'];
                if (isset($savedLayout['nodes_layout'][$nodeId])) {
                    $savedNodeData = $savedLayout['nodes_layout'][$nodeId];

                    if (isset($savedNodeData['position'])) {
                        $node['position'] = $savedNodeData['position'];
                    }

                    if (isset($savedNodeData['style'])) {
                        $node['style'] = array_merge($node['style'] ?? [], $savedNodeData['style']);
                    }
                }
            }
            unset($node);

            if (!empty($savedLayout['edges_layout'])) {
                foreach ($edges as &$edge) {
                    $savedEdge = collect($savedLayout['edges_layout'])->firstWhere('id', $edge['id']);
                    if ($savedEdge) {
                        if (isset($savedEdge['style'])) {
                            $edge['style'] = array_merge($edge['style'] ?? [], $savedEdge['style']);
                        }
                        if (isset($savedEdge['data'])) {
                            $edge['data'] = array_merge($edge['data'] ?? [], $savedEdge['data']);
                        }
                    }
                }
                unset($edge);
            }
        } else {
            $nodesLayout = [
                (string) $appId => [
                    'position' => ['x' => 50, 'y' => 50],
                    'style' => [
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'width' => 400,
                        'height' => 300,
                        'border' => '2px solid ' . ($app->stream->color ?? '#3b82f6'),
                    ],
                ],
            ];

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
        if (!$this->validateStreamName($streamName)) {
            return DiagramDataDTO::withError('Stream not allowed');
        }

        $stream = $this->getStream($streamName);
        if (!$stream) {
            return DiagramDataDTO::withError('Stream not found');
        }

        $streamApps = $this->getStreamApps($stream);
        $homeAppIds = $streamApps->pluck('app_id')->values()->all();

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

        $appParentNodes = $streamApps->map(function ($app) use ($stream, $cleanStreamId) {
            return [
                'id' => (string) $app->getAttribute('app_id'),
                'data' => [
                    'label' => (string) $app->getAttribute('app_name'),
                    'app_id' => (int) $app->getAttribute('app_id'),
                    'app_name' => (string) $app->getAttribute('app_name'),
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

        $functions = AppIntegrationFunction::whereIn('app_id', $homeAppIds)
            ->with(['app', 'integration'])
            ->get();

        $functionNodes = $functions->map(function ($func) use ($stream) {
            $app = $func->app;
            return [
                'id' => 'f-' . (string) $func->getKey(),
                'data' => [
                    'label' => (string) $func->getAttribute('function_name'),
                    'app_id' => (int) $func->getAttribute('app_id'),
                    'app_name' => (string) ($app?->getAttribute('app_name') ?? ''),
                    'stream_name' => $stream->stream_name,
                    'lingkup' => $stream->stream_name,
                    'is_home_stream' => true,
                    'is_parent_node' => false,
                ],
                'type' => 'app',
                'parentNode' => (string) $func->getAttribute('app_id'),
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

        $funcsByIntegration = $functions->groupBy('integration_id');
        $edges = [];

        $integrationIds = $funcsByIntegration->keys()->values()->all();
        $integrations = AppIntegration::whereIn('integration_id', $integrationIds)
            ->with(['sourceApp', 'targetApp'])
            ->get()
            ->keyBy('integration_id');

        foreach ($funcsByIntegration as $integrationId => $funcRows) {
            $integration = $integrations->get($integrationId);
            if (!$integration) {
                continue;
            }

            $sourceAppId = (int) $integration->getAttribute('source_app_id');
            $targetAppId = (int) $integration->getAttribute('target_app_id');

            $sourceFuncs = $funcRows->where('app_id', $sourceAppId)->values();
            $targetFuncs = $funcRows->where('app_id', $targetAppId)->values();

            if ($sourceFuncs->isEmpty() || $targetFuncs->isEmpty()) {
                continue;
            }

            $sf = $sourceFuncs->first();
            $tf = $targetFuncs->first();

            $edges[] = [
                'id' => (string) $sourceAppId . '-' . (string) $targetAppId,
                'source' => (string) $sourceAppId,
                'target' => (string) $targetAppId,
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
                    'integration_id' => (int) $integrationId,
                    'sourceApp' => [
                        'app_id' => $sourceAppId,
                        'app_name' => (string) ($integration->sourceApp?->getAttribute('app_name') ?? ''),
                    ],
                    'targetApp' => [
                        'app_id' => $targetAppId,
                        'app_name' => (string) ($integration->targetApp?->getAttribute('app_name') ?? ''),
                    ],
                    'source_app_name' => (string) ($integration->sourceApp?->getAttribute('app_name') ?? ''),
                    'target_app_name' => (string) ($integration->targetApp?->getAttribute('app_name') ?? ''),
                ],
                'sourceHandle' => 'left-source',
                'targetHandle' => 'right-target',
            ];
        }

        $nodes = array_merge([$streamParentNode], $appParentNodes, $functionNodes);

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
            $appIdStr = (string) $app->getAttribute('app_id');
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
                'totalNodes' => count($nodes) - 1,
                'totalEdges' => count($edges),
                'forceEdgeBlackNoArrow' => true,
            ],
        ];

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
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->whereNotIn('source_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();

        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->whereNotIn('target_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();

        $externalAppIds = $sourceAppIds->merge($targetAppIds)->unique();

        return App::whereIn('app_id', $externalAppIds)->with('stream')->get();
    }

    /**
     * Get integrations involving specific apps using DTOs
     */
    public function getIntegrations(array $appIds, array $homeAppIds = []): Collection
    {
        $integrations = AppIntegration::where(function ($q) use ($appIds) {
            $q->whereIn('source_app_id', $appIds)
                ->orWhereIn('target_app_id', $appIds);
        })
            ->with(['connections.connectionType', 'sourceApp', 'targetApp'])
            ->get();

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
        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            throw new \InvalidArgumentException("Stream not found: {$streamName}");
        }

        $streamApps = $this->getStreamApps($stream);
        $appIds = $streamApps->pluck('app_id')->toArray();
        $integrations = $this->getIntegrations($appIds, $appIds);
        $externalApps = $this->getConnectedExternalApps($appIds);

        $nodeTransformer = new NodeTransformer();
        $edgeTransformer = new EdgeTransformer();

        $nodes = [];
        $edges = collect();
        $savedLayout = null;

        $streamNode = $nodeTransformer->createStreamNode($streamName, !$isUserView, $stream->color ?? null);
        $nodes[] = $streamNode->toArray();

        if ($streamApps->isNotEmpty()) {
            $streamNodeApps = $nodeTransformer->transformHomeStreamApps($streamApps, $streamName, !$isUserView, $stream->color);
            $nodes = array_merge($nodes, $streamNodeApps->toArray());

            $externalNodeApps = $nodeTransformer->transformExternalApps($externalApps, !$isUserView);
            $nodes = array_merge($nodes, $externalNodeApps->toArray());

            $savedLayout = $this->streamLayoutRepository->getLayoutDataById($stream->stream_id);

            try {
                StreamLayout::with('stream')->get()->map(function ($layout) {
                    return $layout->stream ? $layout->stream->stream_name : "No stream (ID: {$layout->stream_id})";
                })->toArray();

                $cleanStreamName = strtolower(trim($streamName));
                if (str_starts_with($cleanStreamName, 'stream ')) {
                    $cleanStreamName = substr($cleanStreamName, 7);
                }
            } catch (\Exception $e) {
                \Log::error("DiagramService - Debug error: " . $e->getMessage());
            }

            if (!$isUserView && is_array($savedLayout)) {
                foreach (['edges_layout', 'edges'] as $edgeListKey) {
                    if (isset($savedLayout[$edgeListKey]) && is_array($savedLayout[$edgeListKey])) {
                        foreach ($savedLayout[$edgeListKey] as $idx => $edge) {
                            if (!is_array($savedLayout[$edgeListKey][$idx]))
                                continue;
                            if (!isset($savedLayout[$edgeListKey][$idx]['style']) || !is_array($savedLayout[$edgeListKey][$idx]['style'])) {
                                $savedLayout[$edgeListKey][$idx]['style'] = [];
                            }
                            $savedLayout[$edgeListKey][$idx]['style']['stroke'] = '#000000';
                            $savedLayout[$edgeListKey][$idx]['style']['strokeWidth'] = $savedLayout[$edgeListKey][$idx]['style']['strokeWidth'] ?? 2;
                            unset($savedLayout[$edgeListKey][$idx]['markerEnd'], $savedLayout[$edgeListKey][$idx]['markerStart']);
                            $savedLayout[$edgeListKey][$idx]['color'] = '#000000';
                            if (!isset($savedLayout[$edgeListKey][$idx]['data']) || !is_array($savedLayout[$edgeListKey][$idx]['data'])) {
                                $savedLayout[$edgeListKey][$idx]['data'] = [];
                            }
                            $savedLayout[$edgeListKey][$idx]['data']['color'] = '#000000';
                            $savedLayout[$edgeListKey][$idx]['type'] = $savedLayout[$edgeListKey][$idx]['type'] ?? 'smoothstep';
                            if (isset($savedLayout[$edgeListKey][$idx]['animated'])) {
                                $savedLayout[$edgeListKey][$idx]['animated'] = false;
                            }
                        }
                    }
                }
                if (!isset($savedLayout['stream_config']) || !is_array($savedLayout['stream_config'])) {
                    $savedLayout['stream_config'] = [];
                }
                $savedLayout['stream_config']['forceEdgeBlackNoArrow'] = true;
            }

            $edges = $isUserView
                ? $edgeTransformer->transformForUser($integrations, $savedLayout)
                : $edgeTransformer->transformForAdmin($integrations, $savedLayout);
        }

        return DiagramDataDTO::create(
            $nodes,
            $edges instanceof Collection ? $edges->toArray() : (array) $edges,
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

        $nodeTypes = [];
        $processedStreams = new \stdClass();

        $streamColors = Stream::pluck('color', 'stream_name')->toArray();
        $streamDescriptions = Stream::pluck('description', 'stream_name')->toArray();

        foreach ($allApps as $app) {
            $streamName = $app->stream?->stream_name ?? 'external';
            $streamKey = strtolower($streamName);

            if (!property_exists($processedStreams, $streamKey)) {
                $processedStreams->$streamKey = true;

                $streamColor = $streamColors[$streamName] ?? '#6b7280';
                $streamDescription = $streamDescriptions[$streamName] ?? null;

                $nodeTypes[] = [
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
