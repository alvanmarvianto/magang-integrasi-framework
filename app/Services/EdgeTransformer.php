<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EdgeTransformer
{
    /**
     * Transform integrations to edges for admin view
     */
    public function transformForAdmin(Collection $integrations, ?array $savedLayout = null): Collection
    {
        $edgesLayout = $savedLayout['edges_layout'] ?? ($savedLayout['edges'] ?? []);
        
    return $integrations->map(function ($integration) use ($edgesLayout) {
            $types = $integration->relationLoaded('connections')
                ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)->filter()->unique()->values()->toArray()
                : [];
            $connectionType = empty($types) ? 'direct' : implode(' / ', $types);
            $edgeColor = '#000000';

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
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
                    'connection_types' => array_map(fn($n) => ['name' => $n], $types),
                    'connections' => $integration->relationLoaded('connections')
                        ? $integration->connections->map(function ($conn) use ($integration) {
                            return [
                                'connection_type_id' => $conn->connection_type_id,
                                'connection_type_name' => $conn->connectionType?->type_name,
                                'connection_color' => $conn->connectionType->color ?? null,
                                'source' => [
                                    'app_id' => $integration->sourceApp?->app_id ?? null,
                                    'app_name' => $integration->sourceApp?->app_name ?? null,
                                    'inbound' => $conn->source_inbound,
                                    'outbound' => $conn->source_outbound,
                                ],
                                'target' => [
                                    'app_id' => $integration->targetApp?->app_id ?? null,
                                    'app_name' => $integration->targetApp?->app_name ?? null,
                                    'inbound' => $conn->target_inbound,
                                    'outbound' => $conn->target_outbound,
                                ],
                            ];
                        })->toArray()
                        : [],
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
                    'source_app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    'target_app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                ]
            ];

            if ($savedEdge) {
                if (isset($savedEdge['sourceHandle'])) {
                    $edgeData['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edgeData['targetHandle'] = $savedEdge['targetHandle'];
                }
                if (isset($savedEdge['style']) && is_array($savedEdge['style'])) {
                    $mergedStyle = array_merge($edgeData['style'], $savedEdge['style']);
                    $edgeData['style'] = $mergedStyle;
                }
            }

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
        $edgesLayout = $savedLayout['edges_layout'] ?? ($savedLayout['edges'] ?? []);
        
    return $integrations->map(function ($integration) use ($edgesLayout) {
            $types = $integration->relationLoaded('connections')
                ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)->filter()->unique()->values()->toArray()
                : [];
            $connectionType = empty($types) ? 'direct' : implode(' / ', $types);
            $edgeColor = '#000000';

            $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
            
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
                    'connection_types' => array_map(fn($n) => ['name' => $n], $types),
                    'connections' => $integration->relationLoaded('connections')
                        ? $integration->connections->map(function ($conn) use ($integration) {
                            return [
                                'connection_type_id' => $conn->connection_type_id,
                                'connection_type_name' => $conn->connectionType?->type_name,
                                'connection_color' => $conn->connectionType->color ?? null,
                                'source' => [
                                    'app_id' => $integration->sourceApp?->app_id ?? null,
                                    'app_name' => $integration->sourceApp?->app_name ?? null,
                                    'inbound' => $conn->source_inbound,
                                    'outbound' => $conn->source_outbound,
                                ],
                                'target' => [
                                    'app_id' => $integration->targetApp?->app_id ?? null,
                                    'app_name' => $integration->targetApp?->app_name ?? null,
                                    'inbound' => $conn->target_inbound,
                                    'outbound' => $conn->target_outbound,
                                ],
                            ];
                        })->toArray()
                        : [],
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
                    'source_app_name' => $integration->sourceApp?->app_name ?? 'Unknown App',
                    'target_app_name' => $integration->targetApp?->app_name ?? 'Unknown App',
                ],
            ];

            if ($savedEdge) {
                if (isset($savedEdge['sourceHandle'])) {
                    $edgeData['sourceHandle'] = $savedEdge['sourceHandle'];
                }
                if (isset($savedEdge['targetHandle'])) {
                    $edgeData['targetHandle'] = $savedEdge['targetHandle'];
                }
                if (isset($savedEdge['style']) && is_array($savedEdge['style'])) {
                    $mergedStyle = array_merge($edgeData['style'], $savedEdge['style']);
                    $edgeData['style'] = $mergedStyle;
                }
            }


            return $edgeData;
        });
    }
}
