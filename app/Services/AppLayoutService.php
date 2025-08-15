<?php

namespace App\Services;

use App\Models\App;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AppLayoutService
{
    public function __construct(
        private readonly AppLayoutRepositoryInterface $appLayoutRepository
    ) {}

    /**
     * Synchronize app layout colors with current stream colors
     */
    public function syncAppLayoutColors(int $appId): int
    {
        try {
            $savedLayout = $this->appLayoutRepository->findByAppId($appId);
            
            if (!$savedLayout || !$savedLayout->nodesLayout) {
                return 0;
            }
            
            $app = App::with('stream')->find($appId);
            if (!$app || !$app->stream) {
                return 0;
            }
            
            $nodesLayout = $savedLayout->nodesLayout;
            $colorsSynced = 0;
            $streamColor = $app->stream->color ?? '#3b82f6';
            
            // Get all streams for external app color resolution
            $allStreams = \App\Models\Stream::all()->keyBy('stream_name');
            
            foreach ($nodesLayout as $nodeId => $node) {
                $colorNeedsUpdate = false;
                $targetColor = $streamColor; // Default to app's stream color
                
                // Determine target color based on node type and stream
                if (isset($node['data'])) {
                    $nodeData = $node['data'];
                    
                    // For external app nodes, use their specific stream color
                    if (isset($nodeData['is_home_stream']) && !$nodeData['is_home_stream']) {
                        if (isset($nodeData['stream_name']) && isset($allStreams[$nodeData['stream_name']])) {
                            $targetColor = $allStreams[$nodeData['stream_name']]->color ?? $streamColor;
                        }
                    }
                    // For function nodes or home app nodes, use the main app's stream color
                    // (targetColor is already set to $streamColor above)
                }
                
                // Ensure style array exists
                if (!isset($nodesLayout[$nodeId]['style']) || !is_array($nodesLayout[$nodeId]['style'])) {
                    $nodesLayout[$nodeId]['style'] = [];
                }
                
                // Update border color
                $desiredBorderStr = "2px solid {$targetColor}";
                if (($nodesLayout[$nodeId]['style']['border'] ?? null) !== $desiredBorderStr) {
                    $nodesLayout[$nodeId]['style']['border'] = $desiredBorderStr;
                    $colorNeedsUpdate = true;
                }
                
                // Update borderColor property
                if (($nodesLayout[$nodeId]['style']['borderColor'] ?? null) !== $targetColor) {
                    $nodesLayout[$nodeId]['style']['borderColor'] = $targetColor;
                    $colorNeedsUpdate = true;
                }
                
                // Update data color for consistency
                if (isset($nodesLayout[$nodeId]['data']) && is_array($nodesLayout[$nodeId]['data'])) {
                    if (($nodesLayout[$nodeId]['data']['color'] ?? null) !== $targetColor) {
                        $nodesLayout[$nodeId]['data']['color'] = $targetColor;
                        $colorNeedsUpdate = true;
                    }
                }
                
                if ($colorNeedsUpdate) {
                    $colorsSynced++;
                }
            }
            
            // Save updated layout if colors were changed
            if ($colorsSynced > 0) {
                $this->appLayoutRepository->saveLayoutByAppId(
                    $appId,
                    $nodesLayout,
                    $savedLayout->edgesLayout ?? [],
                    $savedLayout->appConfig ?? []
                );
            }
            
            return $colorsSynced;
        } catch (\Exception $e) {
            Log::error('Error syncing app layout colors: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Synchronize colors for multiple apps
     */
    public function syncMultipleAppLayoutColors(array $appIds): int
    {
        $totalColorsSynced = 0;
        
        foreach ($appIds as $appId) {
            try {
                // Only sync if the app has a layout (to avoid unnecessary processing)
                $layout = $this->appLayoutRepository->findByAppId($appId);
                
                if ($layout) {
                    $colorsSynced = $this->syncAppLayoutColors($appId);
                    $totalColorsSynced += $colorsSynced;
                }
            } catch (\Exception $syncEx) {
                // Log but don't fail the entire operation for sync issues
                Log::warning("Failed to sync colors for app {$appId}: " . $syncEx->getMessage());
            }
        }
        
        return $totalColorsSynced;
    }

    /**
     * Clear cache for app layout
     */
    public function clearAppLayoutCache(int $appId): void
    {
        try {
            if (method_exists($this->appLayoutRepository, 'clearCaches')) {
                $this->appLayoutRepository->clearCaches($appId);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to clear cache for app {$appId}: " . $e->getMessage());
        }
    }

    /**
     * Auto-sync colors after app operations that might affect colors
     * This should be called after:
     * - Creating app integration functions
     * - Updating app stream assignment
     * - Bulk updating apps
     */
    public function autoSyncColorsAfterAppOperation(int $appId): void
    {
        try {
            $colorsSynced = $this->syncAppLayoutColors($appId);
            if ($colorsSynced > 0) {
                Log::info("Auto-synced {$colorsSynced} app layout node colors for app {$appId}");
            }
        } catch (\Exception $e) {
            Log::warning("Failed to auto-sync colors for app {$appId}: " . $e->getMessage());
        }
    }

    /**
     * Auto-sync colors for multiple apps after bulk operations
     */
    public function autoSyncColorsAfterBulkOperation(array $appIds): int
    {
        $totalColorsSynced = $this->syncMultipleAppLayoutColors($appIds);
        
        if ($totalColorsSynced > 0) {
            Log::info("Auto-synced {$totalColorsSynced} app layout node colors for " . count($appIds) . " apps after bulk operation");
        }
        
        return $totalColorsSynced;
    }
}
