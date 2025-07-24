<?php

namespace App\Services\Transformers;

use App\Models\App;
use App\Models\AppIntegration;
use Illuminate\Support\Collection;

class NodeTransformer
{
    /**
     * Create stream parent node
     */
    public function createStreamNode(string $streamName, bool $isAdmin = false): array
    {
        $baseData = [
            'id' => $streamName,
            'data' => [
                'label' => strtoupper($streamName) . ' Stream',
                'app_id' => -1,
                'stream_name' => $streamName,
                'lingkup' => $streamName,
                'is_home_stream' => true,
                'is_parent_node' => true,
            ]
        ];

        if (!$isAdmin) {
            $baseData['type'] = 'stream';
            $baseData['position'] = ['x' => 100, 'y' => 100];
            $baseData['style'] = [
                'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                'width' => '400px',
                'height' => '300px',
                'border' => '2px solid #3b82f6',
                'borderRadius' => '8px',
            ];
        }

        return $baseData;
    }

    /**
     * Transform home stream apps to nodes
     */
    public function transformHomeStreamApps(Collection $apps, string $streamName, bool $isAdmin = false): array
    {
        return $apps->map(function ($app) use ($streamName, $isAdmin) {
            $baseData = [
                'id' => (string)$app->getAttribute('app_id'),
                'data' => [
                    'label' => $app->getAttribute('app_name'),
                    'app_id' => $app->getAttribute('app_id'),
                    'stream_name' => $streamName,
                    'lingkup' => $app->stream?->stream_name ?? 'unknown',
                    'is_home_stream' => true,
                ]
            ];

            if ($isAdmin) {
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($streamName);
            } else {
                $baseData['type'] = 'app';
                $baseData['data']['is_parent_node'] = false;
                $baseData['position'] = ['x' => 0, 'y' => 0];
                $baseData['parentNode'] = null;
                $baseData['extent'] = null;
            }

            return $baseData;
        })->toArray();
    }

    /**
     * Transform external apps to nodes
     */
    public function transformExternalApps(Collection $apps, bool $isAdmin = false): array
    {
        return $apps->map(function ($app) use ($isAdmin) {
            $baseData = [
                'id' => (string)$app->getAttribute('app_id'),
                'data' => [
                    'label' => $app->getAttribute('app_name'),
                    'app_id' => $app->getAttribute('app_id'),
                    'stream_name' => $app->stream?->stream_name ?? 'external',
                    'lingkup' => $app->stream?->stream_name ?? 'external',
                    'is_home_stream' => false,
                ]
            ];

            if ($isAdmin) {
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($app->stream?->stream_name ?? 'external');
            } else {
                $baseData['type'] = 'app';
                $baseData['data']['is_parent_node'] = false;
                $baseData['position'] = ['x' => 0, 'y' => 0];
                $baseData['parentNode'] = null;
                $baseData['extent'] = null;
            }

            return $baseData;
        })->toArray();
    }
}
