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


    public function integration($appId): Response
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
            ->merge($integratedBy)
            ->filter(fn($i) => !is_null($i['link']))
            ->unique('name')
            ->values();

        $integrationData = [
            'name' => $app->app_name,
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
        // Only allow specific stream names
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
            'technology' => $app->technology,
            'appName' => $app->app_name,
            'streamName' => $app->stream?->stream_name,
        ]);
    }
}