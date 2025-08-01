<?php

namespace App\Services;

use App\Models\AppIntegration;
use Illuminate\Support\Collection;

class EdgeTransformer
{
    /**
     * Transform integrations to edges for admin view
     */
    public function transformForAdmin(Collection $integrations, ?array $savedLayout = null): array
    {
        $edgesLayout = $savedLayout['edges'] ?? [];
        
        return $integrations->map(function ($integration) use ($edgesLayout) {
            $connectionType = $integration->connectionType?->type_name ?? 'direct';
            $edgeColor = match (strtolower($connectionType)) {
                'soa' => '#02a330',
                'sftp' => '#002ac0',
                'soa-sftp' => '#6b7280',
                default => '#000000',
            };

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
            // Find saved edge layout for this edge
            $savedEdge = collect($edgesLayout)->firstWhere('id', $edgeId);

            $edge = [
                'id' => $edgeId,
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'style' => [
                    'stroke' => $edgeColor,
                    'strokeWidth' => 2
                ],
                'data' => [
                    'label' => $connectionType,
                    'connection_type' => strtolower($connectionType),
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
                    $edge['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edge['targetHandle'] = $savedEdge['targetHandle'];
                }
                // Override style if saved layout has different styling
                if (isset($savedEdge['style'])) {
                    $edge['style'] = array_merge($edge['style'], $savedEdge['style']);
                }
            }

            return $edge;
        })->toArray();
    }

    /**
     * Transform integrations to edges for user view
     */
    public function transformForUser(Collection $integrations, ?array $savedLayout = null): array
    {
        $edgesLayout = $savedLayout['edges'] ?? [];
        
        return $integrations->map(function ($integration) use ($edgesLayout) {
            $connectionType = $integration->connectionType?->type_name ?? 'direct';
            $edgeColor = match (strtolower($connectionType)) {
                'soa' => '#02a330',
                'sftp' => '#002ac0',
                'soa-sftp' => '#6b7280',
                default => '#000000',
            };

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
            // Find saved edge layout for this edge
            $savedEdge = collect($edgesLayout)->firstWhere('id', $edgeId);

            $edge = [
                'id' => $edgeId,
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'type' => 'smoothstep',
                'style' => [
                    'stroke' => $edgeColor,
                    'strokeWidth' => 2
                ],
                'data' => [
                    'label' => $connectionType,
                    'connection_type' => strtolower($connectionType),
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
                    $edge['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edge['targetHandle'] = $savedEdge['targetHandle'];
                }
                // Override style if saved layout has different styling
                if (isset($savedEdge['style'])) {
                    $edge['style'] = array_merge($edge['style'], $savedEdge['style']);
                }
            }

            return $edge;
        })->toArray();
    }
}
