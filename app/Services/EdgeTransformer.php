<?php

namespace App\Services;

use App\DTOs\DiagramEdgeDTO;
use App\Models\AppIntegration;
use Illuminate\Support\Collection;

class EdgeTransformer
{
    /**
     * Transform integrations to edges for admin view
     */
    public function transformForAdmin(Collection $integrations, ?array $savedLayout = null): Collection
    {
        // Support both shapes: ['edges_layout'=>[]] or legacy ['edges'=>[]]
        $edgesLayout = $savedLayout['edges_layout'] ?? ($savedLayout['edges'] ?? []);
        
        return $integrations->map(function ($integration) use ($edgesLayout) {
            $connectionType = $integration->connectionType?->type_name ?? 'direct';
            // Admin view: always render edges black, ignore connection-type colors
            $edgeColor = '#000000';

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
            // Find saved edge layout for this edge
            $savedEdge = collect($edgesLayout)->firstWhere('id', $edgeId);

            $edgeData = [
                'id' => $edgeId,
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'style' => [
                    'stroke' => $edgeColor,
                    'strokeWidth' => 2
                ],
                'data' => [
                    'connection_type' => strtolower($connectionType),
                    'color' => $edgeColor,
                    'integration_id' => $integration->getAttribute('integration_id'),
                    'sourceApp' => [
                        'app_id' => $integration->sourceApp?->app_id ?? 0,
                        'app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    ],
                    'targetApp' => [
                        'app_id' => $integration->targetApp?->app_id ?? 0,
                        'app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                    ],
                    'direction' => $integration->getAttribute('direction'),
                    'inbound' => $integration->getAttribute('inbound'),
                    'outbound' => $integration->getAttribute('outbound'),
                    'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                    'source_app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    'target_app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                ]
            ];

            // Add handle information if available in saved layout
            if ($savedEdge) {
                if (isset($savedEdge['sourceHandle'])) {
                    $edgeData['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edgeData['targetHandle'] = $savedEdge['targetHandle'];
                }
                // Merge saved style but enforce black stroke for admin
                if (isset($savedEdge['style']) && is_array($savedEdge['style'])) {
                    $mergedStyle = array_merge($edgeData['style'], $savedEdge['style']);
                    $edgeData['style'] = $mergedStyle;
                }
            }

            // Always enforce black stroke and remove arrows in admin
            $edgeData['style']['stroke'] = '#000000';
            $edgeData['style']['strokeWidth'] = $edgeData['style']['strokeWidth'] ?? 2;
            unset($edgeData['markerEnd'], $edgeData['markerStart']);

            return $edgeData;
        });
    }

    /**
     * Transform integrations to edges for user view
     */
    public function transformForUser(Collection $integrations, ?array $savedLayout = null): Collection
    {
        // Support both shapes: ['edges_layout'=>[]] or legacy ['edges'=>[]]
        $edgesLayout = $savedLayout['edges_layout'] ?? ($savedLayout['edges'] ?? []);
        
        return $integrations->map(function ($integration) use ($edgesLayout) {
            $connectionType = $integration->connectionType?->type_name ?? 'direct';
            // User view: use connection type color from DB
            $edgeColor = $integration->connectionType?->color ?? '#000000';

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
            // Find saved edge layout for this edge
            $savedEdge = collect($edgesLayout)->firstWhere('id', $edgeId);

            $edgeData = [
                'id' => $edgeId,
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'style' => [
                    'stroke' => $edgeColor,
                    'strokeWidth' => 2
                ],
                'data' => [
                    'connection_type' => strtolower($connectionType),
                    'color' => $edgeColor,
                    'integration_id' => $integration->getAttribute('integration_id'),
                    'sourceApp' => [
                        'app_id' => $integration->sourceApp?->app_id ?? 0,
                        'app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    ],
                    'targetApp' => [
                        'app_id' => $integration->targetApp?->app_id ?? 0,
                        'app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                    ],
                    'direction' => $integration->getAttribute('direction'),
                    'inbound' => $integration->getAttribute('inbound'),
                    'outbound' => $integration->getAttribute('outbound'),
                    'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                    'source_app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    'target_app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                ],
            ];

            // Add handle information if available in saved layout
            if ($savedEdge) {
                if (isset($savedEdge['sourceHandle'])) {
                    $edgeData['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edgeData['targetHandle'] = $savedEdge['targetHandle'];
                }
                // Merge saved style but do not enforce saved color
                if (isset($savedEdge['style']) && is_array($savedEdge['style'])) {
                    $mergedStyle = array_merge($edgeData['style'], $savedEdge['style']);
                    $edgeData['style'] = $mergedStyle;
                }
            }

            // Keep whatever markers/layout the saved layout has; do not force black in user view

            return $edgeData;
        });
    }
}
