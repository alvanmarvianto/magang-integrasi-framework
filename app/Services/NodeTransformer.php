<?php

namespace App\Services;

use App\DTOs\DiagramNodeDTO;
use App\Models\App;
use App\Models\AppIntegration;
use Illuminate\Support\Collection;

class NodeTransformer
{
    /**
     * Create stream parent node
     */
    public function createStreamNode(string $streamName, bool $isAdmin = false): DiagramNodeDTO
    {
        $nodeData = [
            'id' => $streamName,
            'data' => [
                'label' => strtoupper($streamName) . ' Stream',
                'app_id' => -1,
                'app_name' => strtoupper($streamName) . ' Stream',
                'stream_name' => $streamName,
                'lingkup' => $streamName,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ]
        ];

        if (!$isAdmin) {
            $nodeData['type'] = 'stream';
            $nodeData['position'] = ['x' => 100, 'y' => 100];
            $nodeData['style'] = [
                'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                'width' => '400px',
                'height' => '300px',
                'border' => '2px solid #3b82f6',
                'borderRadius' => '8px',
            ];
        } else {
            $nodeData['type'] = 'group';
            $nodeData['position'] = ['x' => 0, 'y' => 0];
            $nodeData['style'] = [
                'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
                'width' => 600,
                'height' => 400,
                'border' => '2px dashed #999',
            ];
        }

        return DiagramNodeDTO::fromArray($nodeData);
    }

    /**
     * Transform home stream apps to nodes
     */
    public function transformHomeStreamApps(Collection $apps, string $streamName, bool $isAdmin = false): Collection
    {
        return $apps->map(function ($app) use ($streamName, $isAdmin) {
            $appStreamName = $app->stream?->stream_name ?? $streamName;
            $baseData = [
                'id' => (string)$app->getAttribute('app_id'),
                'data' => [
                    'label' => $app->getAttribute('app_name') ?? 'Unknown App',
                    'app_id' => $app->getAttribute('app_id'),
                    'app_name' => $app->getAttribute('app_name') ?? 'Unknown App',
                    'description' => $app->getAttribute('description'),
                    'app_type' => $app->getAttribute('app_type'),
                    'stream_name' => $appStreamName,
                    'lingkup' => $appStreamName,
                    'is_home_stream' => true,
                    'is_external' => false,
                    'is_parent_node' => false,
                ]
            ];

            if ($isAdmin) {
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($streamName);
                $baseData['type'] = 'appNode';
                $baseData['position'] = ['x' => 100, 'y' => 100];
                $baseData['parentNode'] = $streamName;
                $baseData['extent'] = 'parent';
            } else {
                $baseData['type'] = 'app';
                $baseData['position'] = ['x' => 0, 'y' => 0];
                $baseData['parentNode'] = null;
                $baseData['extent'] = null;
            }

            return DiagramNodeDTO::fromArray($baseData);
        });
    }

    /**
     * Transform external apps to nodes
     */
    public function transformExternalApps(Collection $apps, bool $isAdmin = false): Collection
    {
        return $apps->map(function ($app) use ($isAdmin) {
            $appStreamName = $app->stream?->stream_name ?? 'external';
            $baseData = [
                'id' => (string)$app->getAttribute('app_id'),
                'data' => [
                    'label' => $app->getAttribute('app_name') ?? 'Unknown App',
                    'app_id' => $app->getAttribute('app_id'),
                    'app_name' => $app->getAttribute('app_name') ?? 'Unknown App',
                    'description' => $app->getAttribute('description'),
                    'app_type' => $app->getAttribute('app_type'),
                    'stream_name' => $appStreamName,
                    'lingkup' => $appStreamName,
                    'is_home_stream' => false,
                    'is_external' => true,
                    'is_parent_node' => false,
                ]
            ];

            if ($isAdmin) {
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($app->stream?->stream_name ?? 'external');
                $baseData['type'] = 'appNode';
                $baseData['position'] = ['x' => 700, 'y' => 100];
                $baseData['parentNode'] = null;
                $baseData['extent'] = null;
            } else {
                $baseData['type'] = 'app';
                $baseData['position'] = ['x' => 0, 'y' => 0];
                $baseData['parentNode'] = null;
                $baseData['extent'] = null;
            }

            return DiagramNodeDTO::fromArray($baseData);
        });
    }
}
