<?php

namespace App\Services;

use App\Models\AppIntegration;
use Illuminate\Support\Collection;

class EdgeTransformer
{
    /**
     * Transform integrations to edges for admin view
     */
    public function transformForAdmin(Collection $integrations): array
    {
        return $integrations->map(function ($integration) {
            return [
                'id' => $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
                'source' => (string)$integration->getAttribute('source_app_id'),
                'target' => (string)$integration->getAttribute('target_app_id'),
                'data' => [
                    'label' => $integration->connectionType?->type_name ?? 'Unknown',
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
                    'inbound' => $integration->getAttribute('inbound'),
                    'outbound' => $integration->getAttribute('outbound'),
                    'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                ]
            ];
        })->toArray();
    }

    /**
     * Transform integrations to edges for user view
     */
    public function transformForUser(Collection $integrations): array
    {
        return $integrations->map(function ($integration) {
            return [
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
                    'inbound' => $integration->getAttribute('inbound'),
                    'outbound' => $integration->getAttribute('outbound'),
                    'connection_endpoint' => $integration->getAttribute('connection_endpoint'),
                ],
            ];
        })->toArray();
    }
}
