<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    public function index(): Response
    {
        $streams = Stream::with('apps')
            ->orderBy('stream_id')
            ->take(5)
            ->get();

        $formattedData = [
            'name' => 'Bank Indonesia - DLDS',
            'type' => 'folder',
            'children' => $streams->map(function ($stream) {
                return [
                    'name' => 'Stream - ' . strtoupper($stream->stream_name),
                    'type' => 'folder',
                    'children' => $stream->apps->map(function ($app) use ($stream) {
                        return [
                            'name' => $app->app_name,
                            'type' => 'folder',
                            'children' => [
                                [
                                    'name' => 'Integrasi',
                                    'type' => 'url',
                                    'url' => '/integration/app/' . $app->app_id,
                                    'stream' => $stream->stream_name,
                                ],
                                [
                                    'name' => 'Teknologi',
                                    'type' => 'url',
                                    'url' => '/technology/' . $app->app_id,
                                    'stream' => $stream->stream_name,
                                ]
                            ]
                        ];
                    })->all(),
                ];
            })->all(),
        ];

        return Inertia::render('Index', [
            'appData' => $formattedData,
        ]);
    }

    function cleanTree(array $node): array
    {
        if (!isset($node['children']))
            return $node;

        $node['children'] = array_map(fn($child) => $this->cleanTree($child), $node['children']);
        return $node;
    }


    public function appIntegration($appId): Response
    {
        $app = App::with(['stream', 'integrations.stream', 'integratedBy.stream'])
            ->findOrFail($appId);

        $pivots = $app->integrations->pluck('pivot')
            ->concat($app->integratedBy->pluck('pivot'))
            ->filter();

        if ($pivots->isNotEmpty()) {
            AppIntegration::hydrate($pivots->all())->load('connectionType');
        }
        $integrations = $app->integrations->map(function ($integration) {
            return [
                'name' => $integration->app_name,
                'lingkup' => $integration->stream?->stream_name,
                'link' => $integration->pivot?->connectionType?->type_name,
                'app_id' => $integration->app_id,
            ];
        });

        $integratedBy = $app->integratedBy->map(function ($integration) {
            return [
                'name' => $integration->app_name,
                'lingkup' => $integration->stream?->stream_name,
                'link' => $integration->pivot?->connectionType?->type_name,
                'app_id' => $integration->app_id,
            ];
        });

        $allIntegrations = $integrations
            ->concat($integratedBy)
            ->filter(fn($i) => !is_null($i['link']))
            ->unique('name')
            ->values();

        $integrationData = [
            'name' => $app->app_name,
            'app_id' => $app->app_id,
            'lingkup' => $app->stream?->stream_name,
            'children' => $allIntegrations->toArray(),
        ];

        return Inertia::render('AppIntegration', [
            'integrationData' => $this->cleanTree($integrationData),
            'parentAppId' => $app->app_id,
            'appName' => $app->app_name,
            'streamName' => $app->stream?->stream_name,
        ]);
    }

    public function streamIntegrations(string $streamName): Response
    {
        $allowedNames = ['ssk', 'moneter', 'mi', 'sp', 'market'];
        if (!in_array(strtolower($streamName), $allowedNames)) {
            abort(404, 'Stream not found');
        }

        $stream = Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->firstOrFail();

        $homeApps = $stream->apps;
        $homeAppIds = $homeApps->pluck('app_id');

        $outgoingAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)->pluck('target_app_id');
        $incomingAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)->pluck('source_app_id');

        $allAppIds = $homeAppIds->concat($outgoingAppIds)->concat($incomingAppIds)->unique();

        $allAppsInGraph = App::with('stream')->whereIn('app_id', $allAppIds)->get();

        $nodes = $allAppsInGraph->map(fn($app) => [
            'id' => $app->app_id,
            'stream_id' => $app->stream?->stream_id,
            'name' => $app->app_name,
            'lingkup' => $app->stream?->stream_name ?? 'external',
        ]);

        $links = AppIntegration::with('connectionType')
            ->where(function ($query) use ($homeAppIds) {
                $query->whereIn('source_app_id', $homeAppIds)
                    ->orWhereIn('target_app_id', $homeAppIds);
            })
            ->whereIn('source_app_id', $allAppIds)
            ->whereIn('target_app_id', $allAppIds)
            ->get()
            ->map(fn($integration) => [
                'source' => $integration->source_app_id,
                'target' => $integration->target_app_id,
                'type' => $integration->connectionType?->type_name,
            ])
            ->values();

        return Inertia::render('StreamIntegration', [
            'streamName' => $stream->stream_name,
            'graphData' => [
                'nodes' => $nodes,
                'links' => $links,
            ],
        ]);
    }

    public function technology($appId): Response
    {
        $app = App::with(['stream', 'technology'])
            ->findOrFail($appId);

        return Inertia::render('Technology', [
            'app' => $app,
            'appDescription' => $app->description,
            'technology' => $app->technology,
            'appName' => $app->app_name,
            'streamName' => $app->stream?->stream_name,
        ]);
    }

    public function vueFlowStreamIntegrations(string $streamName): Response
    {
        $allowedNames = ['ssk', 'moneter', 'mi', 'sp', 'market'];
        if (!in_array(strtolower($streamName), $allowedNames)) {
            abort(404, 'Stream not found');
        }

        $stream = Stream::whereRaw('LOWER(stream_name) = ?', [strtolower($streamName)])->firstOrFail();

        // Get saved layout from database
        $savedLayout = null;
        $streamLayout = \App\Models\StreamLayout::where('stream_name', $streamName)->first();
        if ($streamLayout) {
            $savedLayout = [
                'nodes_layout' => $streamLayout->nodes_layout,
                'edges_layout' => $streamLayout->edges_layout,
                'stream_config' => $streamLayout->stream_config,
            ];
        }

        // Get all apps in the current stream
        $homeApps = $stream->apps;
        $homeAppIds = $homeApps->pluck('app_id');

        // Get connected apps (outgoing and incoming connections)
        $outgoingAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)->pluck('target_app_id');
        $incomingAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)->pluck('source_app_id');

        // Combine all app IDs
        $allAppIds = $homeAppIds->concat($outgoingAppIds)->concat($incomingAppIds)->unique();

        // Get all apps and streams for the graph
        $allAppsInGraph = App::with('stream')->whereIn('app_id', $allAppIds)->get();
        $allStreams = Stream::whereIn('stream_id', $allAppsInGraph->pluck('stream_id')->filter()->unique())->get();

        // Prepare nodes data
        $nodes = $allAppsInGraph->map(function ($app) use ($streamName) {
            $isHomeStream = strtolower($app->stream?->stream_name ?? '') === strtolower($streamName);
            $lingkup = $app->stream?->stream_name ?? 'external';
            
            return [
                'id' => (string) $app->app_id,
                'type' => 'app', // Use app type instead of default
                'data' => [
                    'label' => $app->app_name,
                    'lingkup' => $lingkup,
                    'is_home_stream' => $isHomeStream,
                    'is_parent_node' => false,
                ],
                'position' => ['x' => 0, 'y' => 0],
                'parentNode' => null,
                'extent' => null,
            ];
        });

        // Add home stream node
        $homeStreamNode = [
            'id' => $streamName,
            'type' => 'stream',
            'data' => [
                'label' => strtoupper($streamName) . ' Stream',
                'lingkup' => $streamName,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ],
            'position' => ['x' => 100, 'y' => 100],
            'style' => [
                'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                'width' => '400px',
                'height' => '300px',
                'border' => '2px solid #3b82f6',
                'borderRadius' => '8px',
            ],
        ];

        // Combine nodes (home stream group + all app nodes)
        $allNodes = collect([$homeStreamNode])->concat($nodes);

        // Prepare edges data - only show connections involving at least one home stream app
        $allEdges = AppIntegration::with('connectionType')
            ->whereIn('source_app_id', $allAppIds)
            ->whereIn('target_app_id', $allAppIds)
            ->get();

        $edges = $allEdges->filter(function ($integration) use ($homeAppIds) {
            // Only include edges where at least one end is a home stream app
            $sourceIsHome = $homeAppIds->contains($integration->source_app_id);
            $targetIsHome = $homeAppIds->contains($integration->target_app_id);
            
            return $sourceIsHome || $targetIsHome;
        })->map(function ($integration) {
            return [
                'id' => 'edge-' . $integration->source_app_id . '-' . $integration->target_app_id,
                'source' => (string) $integration->source_app_id,
                'target' => (string) $integration->target_app_id,
                'type' => 'smoothstep',
                'data' => [
                    'label' => $integration->connectionType?->type_name ?? 'Connection',
                    'connection_type' => strtolower($integration->connectionType?->type_name ?? 'direct'),
                ],
            ];
        })->values();

        return Inertia::render('VueFlowStreamIntegration', [
            'streamName' => $stream->stream_name,
            'nodes' => $allNodes,
            'edges' => $edges,
            'savedLayout' => $savedLayout,
            'streams' => $allStreams->map(fn($s) => $s->stream_name),
        ]);
    }
}