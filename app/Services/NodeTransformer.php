<?php

namespace App\Services;

use App\DTOs\DiagramNodeDTO;
use Illuminate\Support\Collection;

class NodeTransformer
{
    private const APP_WIDTH = '120px';
    private const APP_HEIGHT = '80px';

    /**
     * Create stream parent node
     */
    public function createStreamNode(string $streamName, bool $isAdmin = false, ?string $streamColor = null): DiagramNodeDTO
    {
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }
        $effectiveBorderColor = $streamColor ?: '#3b82f6';
        
        $nodeData = [
            'id' => $cleanStreamName,
            'data' => [
                'label' => $streamName,
                'app_id' => -1,
                'app_name' => $streamName,
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
                'border' => '2px solid ' . $effectiveBorderColor,
                'borderRadius' => '8px',
            ];
        } else {
            $nodeData['type'] = 'group';
            $nodeData['position'] = ['x' => 0, 'y' => 0];
            $nodeData['style'] = [
                'backgroundColor' => 'rgba(240, 240, 240, 0.25)',
        'width' => 600,
        'height' => 400,
                'border' => '2px solid ' . ($streamColor ?: '#999'),
            ];
        }

        return DiagramNodeDTO::fromArray($nodeData);
    }

    /**
     * Transform home stream apps to nodes
     */
    public function transformHomeStreamApps(Collection $apps, string $streamName, bool $isAdmin = false, ?string $streamColor = null): Collection
    {
        return $apps->map(function ($app) use ($streamName, $isAdmin, $streamColor) {
            $appStreamName = $app->stream?->stream_name ?? $streamName;
            $appStreamColor = $streamColor ?: ($app->stream?->color ?? '#999999');
            
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
            'color' => $appStreamColor,
                    'is_home_stream' => true,
                    'is_external' => false,
                    'is_parent_node' => false,
                ],
                'style' => [
            'border' => '2px solid ' . $appStreamColor,
            'borderColor' => $appStreamColor,
                    'borderRadius' => '8px',
                    'width' => self::APP_WIDTH,
                    'height' => self::APP_HEIGHT,
                ]
            ];

            if ($isAdmin) {
                $cleanParentId = strtolower(trim($streamName));
                if (str_starts_with($cleanParentId, 'stream ')) {
                    $cleanParentId = substr($cleanParentId, 7);
                }
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($streamName);
                $baseData['type'] = 'appNode';
                $baseData['position'] = ['x' => 100, 'y' => 100];
                $baseData['parentNode'] = $cleanParentId;
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
            $appStreamColor = $app->stream?->color ?? '#999999';
            
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
            'color' => $appStreamColor,
                    'is_home_stream' => false,
                    'is_external' => true,
                    'is_parent_node' => false,
                ],
                'style' => [
            'border' => '2px solid ' . $appStreamColor,
            'borderColor' => $appStreamColor,
                    'borderRadius' => '8px',
                    'width' => self::APP_WIDTH,
                    'height' => self::APP_HEIGHT,
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
