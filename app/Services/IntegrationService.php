<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\ConnectionType;
use App\Models\StreamLayout;

class IntegrationService
{
    protected StreamLayoutService $streamLayoutService;

    public function __construct(StreamLayoutService $streamLayoutService)
    {
        $this->streamLayoutService = $streamLayoutService;
    }
    public function getPaginatedIntegrations(?string $search, int $perPage = 10, string $sortBy = 'source_app_name', bool $sortDesc = false): array
    {
        $query = AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
            ->when($search, function ($query, $search) {
                $query->whereHas('sourceApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                })->orWhereHas('targetApp', function ($q) use ($search) {
                    $q->where('app_name', 'like', "%{$search}%");
                });
            });

        // Handle sorting
        $sortDirection = $sortDesc ? 'desc' : 'asc';
        
        switch ($sortBy) {
            case 'source_app_name':
                $query->leftJoin('apps as source_apps', 'appintegrations.source_app_id', '=', 'source_apps.app_id')
                      ->orderBy('source_apps.app_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            case 'target_app_name':
                $query->leftJoin('apps as target_apps', 'appintegrations.target_app_id', '=', 'target_apps.app_id')
                      ->orderBy('target_apps.app_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            case 'connection_type_name':
                $query->leftJoin('connectiontypes', 'appintegrations.connection_type_id', '=', 'connectiontypes.connection_type_id')
                      ->orderBy('connectiontypes.type_name', $sortDirection)
                      ->select('appintegrations.*');
                break;
            default:
                $query->orderBy('appintegrations.integration_id', $sortDirection);
                break;
        }

        $paginator = $query->paginate($perPage);

        $data = $paginator->items();
        $transformedData = array_map(function ($integration) {
            return [
                'integration_id' => $integration->integration_id,
                'source_app' => [
                    'app_id' => $integration->sourceApp->app_id,
                    'app_name' => $integration->sourceApp->app_name,
                ],
                'target_app' => [
                    'app_id' => $integration->targetApp->app_id,
                    'app_name' => $integration->targetApp->app_name,
                ],
                'connection_type' => [
                    'connection_type_id' => $integration->connectionType->connection_type_id,
                    'type_name' => $integration->connectionType->type_name,
                ],
            ];
        }, $data);

        return [
            'integrations' => [
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'links' => $paginator->onEachSide(1)->linkCollection()->toArray(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]
            ]
        ];
    }

    public function getFormData(?int $integrationId = null): array
    {
        $data = [
            'apps' => App::select('app_id', 'app_name')->orderBy('app_name', 'asc')->get(),
            'connectionTypes' => ConnectionType::all(),
        ];

        if ($integrationId) {
            $data['integration'] = AppIntegration::with(['connectionType', 'sourceApp', 'targetApp'])
                ->findOrFail($integrationId);
        }

        return $data;
    }

    public function createIntegration(array $data): AppIntegration
    {
        // Check for existing connection in either direction
        $exists = AppIntegration::where(function ($query) use ($data) {
            $query->where('source_app_id', $data['source_app_id'])
                  ->where('target_app_id', $data['target_app_id']);
        })->orWhere(function ($query) use ($data) {
            $query->where('source_app_id', $data['target_app_id'])
                  ->where('target_app_id', $data['source_app_id']);
        })->exists();

        if ($exists) {
            throw new \Exception('Integration between these apps already exists');
        }

        $integration = AppIntegration::create($data);
        
        // Refresh the model to ensure we have the integration_id
        $integration->refresh();
        
        // Update stream layouts after creating integration
        $this->streamLayoutService->updateStreamLayoutsForIntegration($integration);
        
        return $integration;
    }

    public function updateIntegration(AppIntegration $integration, array $data): bool
    {
        // Check for existing connection in either direction, excluding the current integration
        if (isset($data['source_app_id']) && isset($data['target_app_id'])) {
            $exists = AppIntegration::where($integration->getKeyName(), '!=', $integration->getKey())
                ->where(function ($query) use ($data) {
                    $query->where('source_app_id', $data['source_app_id'])
                          ->where('target_app_id', $data['target_app_id']);
                })->orWhere(function ($query) use ($data, $integration) {
                    $query->where($integration->getKeyName(), '!=', $integration->getKey())
                          ->where('source_app_id', $data['target_app_id'])
                          ->where('target_app_id', $data['source_app_id']);
                })->exists();

            if ($exists) {
                throw new \Exception('Integration between these apps already exists');
            }
        }

        $result = $integration->update($data);
        
        // Update stream layouts after updating integration
        if ($result) {
            // Refresh the model to get the updated data with relationships
            $integration->refresh();
            $this->streamLayoutService->updateStreamLayoutsForIntegration($integration);
        }
        
        return $result;
    }

    public function deleteIntegration(AppIntegration $integration): ?bool
    {
        // Remove integration from all stream layouts before deleting
        $this->streamLayoutService->removeIntegrationFromLayouts($integration);
        
        return $integration->delete();
    }

    public function getConnectionTypes(): array
    {
        return [
            'connectionTypes' => ConnectionType::withCount('appIntegrations')->get()
        ];
    }
}
