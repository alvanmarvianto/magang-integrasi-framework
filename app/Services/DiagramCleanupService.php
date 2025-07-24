<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\StreamLayout;
use Illuminate\Support\Facades\Log;

class DiagramCleanupService
{
    /**
     * Remove duplicate integrations from the database
     */
    public function removeDuplicateIntegrations(): int
    {
        try {
            $deletedCount = 0;

            // Get all integrations grouped by connection pair
            $allIntegrations = AppIntegration::orderBy('integration_id')->get();
            $seenConnections = [];
            $toDelete = [];

            foreach ($allIntegrations as $integration) {
                $sourceId = $integration->getAttribute('source_app_id');
                $targetId = $integration->getAttribute('target_app_id');
                
                // Create both directional keys
                $directKey = $sourceId . '-' . $targetId;
                $reverseKey = $targetId . '-' . $sourceId;
                
                // Check if we've seen this connection in either direction
                if (isset($seenConnections[$directKey]) || isset($seenConnections[$reverseKey])) {
                    // This is a duplicate, mark for deletion
                    $toDelete[] = $integration->getAttribute('integration_id');
                    $deletedCount++;
                } else {
                    // Mark this connection as seen in both directions
                    $seenConnections[$directKey] = true;
                    $seenConnections[$reverseKey] = true;
                }
            }

            // Delete all duplicates at once
            if (!empty($toDelete)) {
                AppIntegration::whereIn('integration_id', $toDelete)->delete();
                Log::info("Removed {$deletedCount} duplicate integrations from database");
            }

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error removing duplicate integrations: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Remove integrations that reference non-existent apps
     */
    public function removeInvalidIntegrations(): int
    {
        try {
            // Get all valid app IDs
            $validAppIds = App::pluck('app_id')->toArray();

            // Find integrations with invalid source or target apps
            $invalidIntegrations = AppIntegration::where(function ($query) use ($validAppIds) {
                $query->whereNotIn('source_app_id', $validAppIds)
                      ->orWhereNotIn('target_app_id', $validAppIds);
            })->get();

            $deletedCount = $invalidIntegrations->count();

            if ($deletedCount > 0) {
                // Delete invalid integrations
                AppIntegration::where(function ($query) use ($validAppIds) {
                    $query->whereNotIn('source_app_id', $validAppIds)
                          ->orWhereNotIn('target_app_id', $validAppIds);
                })->delete();

                Log::info("Removed {$deletedCount} invalid integrations referencing non-existent apps");
            }

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error removing invalid integrations: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up invalid layout data for a stream
     */
    public function cleanupStreamLayout(string $streamName): void
    {
        // Get all valid app IDs for this stream and connected apps
        $validAppIds = App::whereHas('stream', function ($query) use ($streamName) {
            $query->where('stream_name', $streamName);
        })->pluck('app_id')->toArray();

        // Get connected external app IDs
        $externalAppIds = App::whereHas('integrations', function ($query) use ($validAppIds) {
            $query->whereIn('target_app_id', $validAppIds);
        })->orWhereHas('integratedBy', function ($query) use ($validAppIds) {
            $query->whereIn('source_app_id', $validAppIds);
        })->pluck('app_id')->toArray();

        $allValidAppIds = array_unique(array_merge($validAppIds, $externalAppIds));

        // Clean up layout data
        $layout = StreamLayout::where('stream_name', $streamName)->first();
        if ($layout && $layout->nodes_layout) {
            $validNodeIds = collect($allValidAppIds)->map(fn($id) => (string)$id)->toArray();
            $validNodeIds[] = $streamName; // Include stream parent node

            $cleanedLayout = array_filter(
                $layout->nodes_layout,
                fn($key) => in_array($key, $validNodeIds),
                ARRAY_FILTER_USE_KEY
            );

            if (count($cleanedLayout) !== count($layout->nodes_layout)) {
                $removedCount = count($layout->nodes_layout) - count($cleanedLayout);
                Log::info("Cleaning layout for stream {$streamName}: {$removedCount} layout nodes removed from stream_layouts");
                $layout->update(['nodes_layout' => $cleanedLayout]);
            }
        }
    }

    /**
     * Clean up diagram data by removing non-existent apps and connections
     */
    public function cleanupDiagramData(array $data): array
    {
        // First, clean up duplicates and invalid integrations in the database
        $this->removeDuplicateIntegrations();
        $this->removeInvalidIntegrations();

        $nodes = $data['nodes'] ?? [];
        $edges = $data['edges'] ?? [];

        // Get all valid app IDs from the database
        $validAppIds = App::pluck('app_id')->map(fn($id) => (string)$id)->toArray();
        
        // Get all valid integrations from the database (after cleanup)
        $validIntegrations = AppIntegration::with(['sourceApp', 'targetApp', 'connectionType'])
            ->get()
            ->keyBy(function($integration) {
                return $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            });

        // Filter nodes - keep only stream nodes and valid app nodes
        $cleanedNodes = array_filter($nodes, function($node) use ($validAppIds) {
            // Keep stream nodes (parent nodes)
            if (isset($node['data']['is_parent_node']) && $node['data']['is_parent_node']) {
                return true;
            }
            
            // Keep only nodes that correspond to existing apps
            return in_array($node['id'], $validAppIds);
        });

        // Filter edges - keep only edges between valid apps with valid integrations
        $cleanedEdges = [];
        $seenConnections = [];

        foreach ($edges as $edge) {
            $sourceId = $edge['source'] ?? null;
            $targetId = $edge['target'] ?? null;
            
            // Skip if either app doesn't exist
            if (!in_array($sourceId, $validAppIds) || !in_array($targetId, $validAppIds)) {
                continue;
            }

            // Check if integration exists in database
            $integrationKey = $sourceId . '-' . $targetId;
            $reverseKey = $targetId . '-' . $sourceId;
            
            $integration = $validIntegrations->get($integrationKey) ?? $validIntegrations->get($reverseKey);
            
            if (!$integration) {
                continue; // Skip edges without corresponding integrations
            }

            // Check for duplicates
            $connectionKey = min($sourceId, $targetId) . '-' . max($sourceId, $targetId);
            if (in_array($connectionKey, $seenConnections)) {
                continue; // Skip duplicate connections
            }
            
            $seenConnections[] = $connectionKey;

            // Use the integration data to ensure edge data is correct
            $edgeData = [
                'id' => 'edge-' . $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'data' => [
                    'label' => $integration->connectionType?->type_name ?? 'Connection',
                    'connection_type' => strtolower($integration->connectionType?->type_name ?? 'direct'),
                    'integration_id' => $integration->getAttribute('integration_id'),
                    'sourceApp' => [
                        'app_id' => $integration->sourceApp->app_id,
                        'app_name' => $integration->sourceApp->app_name,
                    ],
                    'targetApp' => [
                        'app_id' => $integration->targetApp->app_id,
                        'app_name' => $integration->targetApp->app_name,
                    ],
                    'direction' => $integration->getAttribute('direction'),
                    'starting_point' => $integration->getAttribute('starting_point'),
                    'description' => $integration->getAttribute('description'),
                    'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                ],
            ];

            $cleanedEdges[] = $edgeData;
        }

        return [
            'nodes' => array_values($cleanedNodes),
            'edges' => $cleanedEdges,
            'savedLayout' => $data['savedLayout'] ?? null,
        ];
    }
}
