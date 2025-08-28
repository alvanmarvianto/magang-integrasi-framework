<?php

namespace App\Services;

use App\DTOs\ConnectionTypeDTO;
use App\Repositories\ConnectionTypeRepository;
use App\Services\StreamLayoutService;
use App\Services\DiagramCleanupService;
use App\Services\StreamConfigurationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConnectionTypeService
{
    public function __construct(
        private ConnectionTypeRepository $connectionTypeRepository,
        private StreamLayoutService $streamLayoutService,
        private DiagramCleanupService $cleanupService,
        private StreamConfigurationService $streamConfigService
    ) {}

    /**
     * Get all connection types with usage counts
     */
    public function getAllConnectionTypes(): Collection
    {
        return $this->connectionTypeRepository->getAllWithUsageCount();
    }

    /**
     * Create a new connection type
     */
    public function createConnectionType(array $data): ConnectionTypeDTO
    {
        $connectionTypeDTO = ConnectionTypeDTO::fromArray($data);
        $connectionType = $this->connectionTypeRepository->create($connectionTypeDTO);
        
        $this->refreshDiagramLayouts();
        
        return ConnectionTypeDTO::fromModel($connectionType);
    }

    /**
     * Update connection type
     */
    public function updateConnectionType(int $id, array $data): ConnectionTypeDTO
    {
        $connectionType = $this->connectionTypeRepository->findById($id);
        
        if (!$connectionType) {
            throw new \Exception("Connection type not found");
        }

        $connectionTypeDTO = ConnectionTypeDTO::fromArray([
            ...$data,
            'connection_type_id' => $id
        ]);
        
    $this->connectionTypeRepository->update($connectionType, $connectionTypeDTO);
        
    $this->refreshDiagramLayouts();
        
    $refetched = $this->connectionTypeRepository->findById($id);
    return ConnectionTypeDTO::fromModel($refetched);
    }

    /**
     * Delete connection type
     */
    public function deleteConnectionType(int $id): bool
    {
        $connectionType = $this->connectionTypeRepository->findById($id);
        
        if (!$connectionType) {
            throw new \Exception("Connection type not found");
        }

        if ($this->connectionTypeRepository->isBeingUsed($id)) {
            throw new \Exception("Cannot delete connection type that is being used by integrations");
        }

        return $this->connectionTypeRepository->delete($connectionType);
    }

    /**
     * Check connection type usage
     */
    public function checkConnectionTypeUsage(int $id): array
    {
        $connectionType = $this->connectionTypeRepository->findById($id);
        
        if (!$connectionType) {
            throw new \Exception("Connection type not found");
        }

        $usage = $this->connectionTypeRepository->getUsageDetails($id);
        
        return [
            'is_used' => $usage['is_used'],
            'count' => $usage['usage_count'],
            'integrations' => $usage['integrations']->map(function ($integration) {
                return [
                    'id' => $integration->integration_id,
                    'source_app' => $integration->sourceApp->app_name ?? 'Unknown',
                    'target_app' => $integration->targetApp->app_name ?? 'Unknown',
                    'edit_url' => route('admin.integrations.edit', $integration->integration_id)
                ];
            })
        ];
    }

    /**
     * Refresh diagram layouts for all streams to synchronize connection type colors
     */
    private function refreshDiagramLayouts(): void
    {
        try {
            $streams = $this->streamConfigService->getAllowedDiagramStreams();
            
            $this->cleanupService->removeDuplicateIntegrations();
            $this->cleanupService->removeInvalidIntegrations();
 
            $totalColorsSynced = 0;
            $totalEdgesSynced = 0;
            
            foreach ($streams as $streamName) {
                $this->cleanupService->cleanupStreamLayout($streamName);
                
                $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);
                
                $colorsSynced = $this->streamLayoutService->synchronizeConnectionTypeColors($streamName);
                
                $totalEdgesSynced += $edgesSynced;
                $totalColorsSynced += $colorsSynced;        
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh diagram layouts after connection type update: ' . $e->getMessage());
        }
    }
}
