<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\ConnectionType;

class IntegrationService
{
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
            'apps' => App::select('app_id', 'app_name')->get(),
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
        $exists = AppIntegration::where('source_app_id', $data['source_app_id'])
            ->where('target_app_id', $data['target_app_id'])
            ->exists();

        if ($exists) {
            throw new \Exception('Integration between these apps already exists');
        }

        return AppIntegration::create($data);
    }

    public function updateIntegration(AppIntegration $integration, array $data): bool
    {
        return $integration->update($data);
    }

    public function deleteIntegration(AppIntegration $integration): ?bool
    {
        return $integration->delete();
    }

    public function getConnectionTypes(): array
    {
        return [
            'connectionTypes' => ConnectionType::withCount('appIntegrations')->get()
        ];
    }
}
