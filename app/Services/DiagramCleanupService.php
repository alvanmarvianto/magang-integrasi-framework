<?php

namespace App\Services;

use App\DTOs\DiagramDataDTO;
use App\DTOs\DiagramNodeDTO;
use App\DTOs\DiagramEdgeDTO;
use App\DTOs\StreamLayoutDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DiagramCleanupService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {}

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
        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if ($layoutDto && $layoutDto->nodesLayout) {
            $validNodeIds = collect($allValidAppIds)->map(fn($id) => (string)$id)->toArray();
            // Include stream parent node id variants (raw and cleaned)
            $validNodeIds[] = $streamName;
            $cleanStreamName = strtolower(trim($streamName));
            if (str_starts_with($cleanStreamName, 'stream ')) {
                $cleanStreamName = substr($cleanStreamName, 7);
            }
            $validNodeIds[] = $cleanStreamName;

            $cleanedLayout = array_filter(
                $layoutDto->nodesLayout,
                fn($key) => in_array($key, $validNodeIds),
                ARRAY_FILTER_USE_KEY
            );

            if (count($cleanedLayout) !== count($layoutDto->nodesLayout)) {
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamId,
                    $cleanedLayout,
                    $layoutDto->edgesLayout,
                    $layoutDto->streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }

    /**
     * Clean up diagram data by removing non-existent apps and connections
     */
    public function cleanupDiagramData(DiagramDataDTO $diagramData): DiagramDataDTO
    {
        // First, clean up duplicates and invalid integrations in the database
        $this->removeDuplicateIntegrations();
        $this->removeInvalidIntegrations();

        $nodes = collect($diagramData->nodes);
        $edges = collect($diagramData->edges);

        // Get all valid app IDs from the database
        $validAppIds = App::pluck('app_id')->map(fn($id) => (string)$id)->toArray();
        
        // Get all valid integrations from the database (after cleanup)
    $validIntegrations = AppIntegration::with(['sourceApp', 'targetApp', 'connections.connectionType'])
            ->get()
            ->keyBy(function($integration) {
                return $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            });

        // Filter nodes - keep only stream nodes and valid app nodes
        $cleanedNodes = $nodes->filter(function($nodeDto) use ($validAppIds) {
            // Keep stream nodes (parent nodes)
            if (($nodeDto->data['is_parent_node'] ?? false)) {
                return true;
            }
            
            // Keep only nodes that correspond to existing apps
            return in_array($nodeDto->id, $validAppIds);
        });

        // Filter edges - keep only edges between valid apps with valid integrations
        $cleanedEdges = collect();
        $seenConnections = [];

        foreach ($edges as $edgeDto) {
            $sourceId = $edgeDto->source ?? null;
            $targetId = $edgeDto->target ?? null;
            
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
            // Build label from connections
            $types = $integration->relationLoaded('connections')
                ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)->filter()->unique()->values()->toArray()
                : [];
            $connectionLabel = empty($types) ? 'Connection' : implode(' / ', $types);

            $edgeData = [
                'id' => 'edge-' . $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'data' => [
                    'label' => $connectionLabel,
                    'connection_type' => strtolower($connectionLabel),
                    'integration_id' => $integration->getAttribute('integration_id'),
                    'sourceApp' => [
                        'app_id' => $integration->sourceApp->app_id,
                        'app_name' => $integration->sourceApp->app_name,
                    ],
                    'targetApp' => [
                        'app_id' => $integration->targetApp->app_id,
                        'app_name' => $integration->targetApp->app_name,
                    ],
                    // direction and per-app IO removed in new model
                ],
            ];

            $cleanedEdges->push(DiagramEdgeDTO::fromArray($edgeData));
        }

        return new DiagramDataDTO(
            $cleanedNodes->values()->toArray(),
            $cleanedEdges->values()->toArray(),
            $diagramData->layout,
            $diagramData->config,
            $diagramData->error
        );
    }
}
