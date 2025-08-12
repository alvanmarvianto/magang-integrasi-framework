<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\StreamService;
use App\Services\IntegrationService;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    public function __construct(
        private StreamService $streamService,
        private IntegrationService $integrationService
    ) {}

    public function index(): Response
    {
        $hierarchyData = $this->streamService->getAppHierarchyForIndex();
        $allowedStreams = $this->streamService->getAllowedDiagramStreamsWithDetails();
        $allStreams = $this->streamService->getAllStreamsWithDetails();
        
        return Inertia::render('Index', [
            'appData' => $hierarchyData->toArray(),
            'allowedStreams' => $allowedStreams,
            'allStreams' => $allStreams,
        ]);
    }

    public function appIntegration(int $appId): Response
    {
        try {
            $integrationData = $this->integrationService->getAppIntegrationData($appId);
            $allowedStreams = $this->streamService->getAllowedDiagramStreamsWithDetails();
            $allStreams = $this->streamService->getAllStreamsWithDetails();
            
            return Inertia::render('Integration/App', [
                'integrationData' => $this->cleanTree($integrationData->toArray()),
                'parentAppId' => $integrationData->appId,
                'appName' => $integrationData->appName,
                'streamName' => $integrationData->streamName,
                'allowedStreams' => $allowedStreams,
                'allStreams' => $allStreams,
            ]);
        } catch (\Exception $e) {
            $allStreams = $this->streamService->getAllStreamsWithDetails();
            
            if (str_contains($e->getMessage(), 'not allowed')) {
                return Inertia::render('Integration/App', [
                    'integrationData' => [],
                    'parentAppId' => $appId,
                    'appName' => 'Akses ditolak',
                    'streamName' => '',
                    'error' => $e->getMessage(),
                    'allowedStreams' => [],
                    'allStreams' => $allStreams,
                ]);
            }
            
            return Inertia::render('Integration/App', [
                'integrationData' => [],
                'parentAppId' => $appId,
                'appName' => 'Aplikasi tidak ditemukan',
                'streamName' => '',
                'error' => 'Application not found',
                'allowedStreams' => [],
                'allStreams' => $allStreams,
            ]);
        }
    }

    private function cleanTree(array $node): array
    {
        if (!isset($node['children'])) {
            return $node;
        }

        $node['children'] = array_map(fn($child) => $this->cleanTree($child), $node['children']);
        return $node;
    }
}
