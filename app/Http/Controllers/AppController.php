<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Services\DiagramService;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    private const ALLOWED_STREAMS = ['sp', 'mi', 'ssk', 'moneter', 'market'];
    
    protected DiagramService $diagramService;

    public function __construct(DiagramService $diagramService)
    {
        $this->diagramService = $diagramService;
    }
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

        // Check if the app belongs to an allowed stream
        if (!$app->stream || !in_array(strtolower($app->stream->stream_name), array_map('strtolower', self::ALLOWED_STREAMS))) {
            abort(403, 'Access to this app integration is not allowed');
        }

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

        return Inertia::render('Integration/App', [
            'integrationData' => $this->cleanTree($integrationData),
            'parentAppId' => $app->app_id,
            'appName' => $app->app_name,
            'streamName' => $app->stream?->stream_name,
        ]);
    }
}
