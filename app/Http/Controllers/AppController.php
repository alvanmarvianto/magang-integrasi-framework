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
        
        return Inertia::render('Index', [
            'appData' => $hierarchyData->toArray(),
        ]);
    }

    public function appIntegration(int $appId): Response
    {
        try {
            $integrationData = $this->integrationService->getAppIntegrationData($appId);
            
            return Inertia::render('Integration/App', [
                'integrationData' => $this->cleanTree($integrationData->toArray()),
                'parentAppId' => $integrationData->appId,
                'appName' => $integrationData->appName,
                'streamName' => $integrationData->streamName,
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'not allowed')) {
                abort(403, $e->getMessage());
            }
            abort(404, 'Application not found');
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
