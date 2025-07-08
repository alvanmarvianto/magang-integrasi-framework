<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    /**
     * Display the main application integration graph.
     */
    public function index(): Response
    {
        // Eager load the apps for each stream to prevent N+1 query issues
        $streams = Stream::with('apps')
                         ->orderBy('stream_id')   // optional: define your own ordering
                         ->take(5)
                         ->get();

        // Build the hierarchical data structure required by the D3 tree layout
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
                                    // This URL will be used later for showing integrations
                                    'url' => str_replace(' ', '_', strtolower($app->app_name)),
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
}