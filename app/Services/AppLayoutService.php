<?php

namespace App\Services;

use App\Models\App;
use App\Models\Stream;
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
            
            $allStreams = Stream::all()->keyBy('stream_name');
            
            foreach ($nodesLayout as $nodeId => $node) {
                $colorNeedsUpdate = false;
                $targetColor = $streamColor;
                
                if (isset($node['data'])) {
                    $nodeData = $node['data'];
                    
                    if (isset($nodeData['is_home_stream']) && !$nodeData['is_home_stream']) {
                        if (isset($nodeData['stream_name']) && isset($allStreams[$nodeData['stream_name']])) {
                            $targetColor = $allStreams[$nodeData['stream_name']]->color ?? $streamColor;
                        }
                    }
                }
                
                if (!isset($nodesLayout[$nodeId]['style']) || !is_array($nodesLayout[$nodeId]['style'])) {
                    $nodesLayout[$nodeId]['style'] = [];
                }
                
                $desiredBorderStr = "2px solid {$targetColor}";
                if (($nodesLayout[$nodeId]['style']['border'] ?? null) !== $desiredBorderStr) {
                    $nodesLayout[$nodeId]['style']['border'] = $desiredBorderStr;
                    $colorNeedsUpdate = true;
                }
                
                if (($nodesLayout[$nodeId]['style']['borderColor'] ?? null) !== $targetColor) {
                    $nodesLayout[$nodeId]['style']['borderColor'] = $targetColor;
                    $colorNeedsUpdate = true;
                }
                
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
     * Auto-sync colors after app operations that might affect colors
     * This should be called after:
     * - Creating app integration functions
     * - Updating app stream assignment
     * - Bulk updating apps
     */
    public function autoSyncColorsAfterAppOperation(int $appId): void
    {
        try {
            $this->syncAppLayoutColors($appId);
        } catch (\Exception $e) {
            Log::warning("Failed to auto-sync colors for app {$appId}: " . $e->getMessage());
        }
    }
}
