<?php

namespace App\Services;

use App\DTOs\ConnectionTypeDTO;
use App\Models\ConnectionType;
use App\Repositories\ConnectionTypeRepository;
use App\Services\StreamLayoutService;
use App\Services\DiagramCleanupService;
use App\Constants\StreamConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConnectionTypeService
{
    public function __construct(
        private ConnectionTypeRepository $connectionTypeRepository,
        private StreamLayoutService $streamLayoutService,
        private DiagramCleanupService $cleanupService
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
        
        // Refresh diagram layouts after creating connection type
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
        
        $updatedConnectionType = $this->connectionTypeRepository->update($connectionType, $connectionTypeDTO);
        
        // Refresh diagram layouts after updating connection type
        $this->refreshDiagramLayouts();
        
        return ConnectionTypeDTO::fromModel($updatedConnectionType);
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

        // Check if connection type is being used
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
     * Get connection type statistics
     */
    public function getStatistics(): array
    {
        return $this->connectionTypeRepository->getConnectionTypeStatistics();
    }

    /**
     * Refresh diagram layouts for all streams to synchronize connection type colors
     */
    private function refreshDiagramLayouts(): void
    {
        try {
            // Get all available streams from constants
            $streams = StreamConstants::ALLOWED_DIAGRAM_STREAMS;
            
            Log::info('Starting diagram layout refresh for streams: ' . implode(', ', $streams));
            
            // First, clean up any invalid data globally
            $duplicatesRemoved = $this->cleanupService->removeDuplicateIntegrations();
            $invalidRemoved = $this->cleanupService->removeInvalidIntegrations();
            
            Log::info("Cleanup completed: {$duplicatesRemoved} duplicates removed, {$invalidRemoved} invalid integrations removed");
            
            $totalColorsSynced = 0;
            $totalEdgesSynced = 0;
            
            foreach ($streams as $streamName) {
                // Clean up stream layout nodes for this specific stream
                $this->cleanupService->cleanupStreamLayout($streamName);
                
                // Synchronize edges layout with current AppIntegration data
                $edgesSynced = $this->streamLayoutService->synchronizeStreamLayoutEdges($streamName);
                
                // Synchronize connection type colors in the layout
                $colorsSynced = $this->streamLayoutService->synchronizeConnectionTypeColors($streamName);
                
                $totalEdgesSynced += $edgesSynced;
                $totalColorsSynced += $colorsSynced;
                
                Log::info("Stream {$streamName}: {$edgesSynced} edges synced, {$colorsSynced} colors synced");
            }
            
            Log::info("Total refresh completed: {$totalEdgesSynced} edges synced, {$totalColorsSynced} colors synced across all streams");
            
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            Log::error('Failed to refresh diagram layouts after connection type update: ' . $e->getMessage());
        }
    }
}
