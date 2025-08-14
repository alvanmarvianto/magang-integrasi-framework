<?php

namespace App\Services;

use App\DTOs\DiagramNodeDTO;
use App\Models\App;
use App\Models\AppIntegration;
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
        // Clean up the stream name for consistent ID: remove "Stream " prefix and convert to lowercase
        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7); // Remove "stream " prefix
        }
        $effectiveBorderColor = $streamColor ?: '#3b82f6';
        
        $nodeData = [
            'id' => $cleanStreamName, // Keep normalized ID for stability
            'data' => [
                // Display label should use DB casing exactly without extra prefixes/suffixes
                'label' => $streamName,
                'app_id' => -1,
                'app_name' => $streamName,
                // Keep data fields using DB value for downstream exact matches
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
            // Use provided stream color or get from app's stream
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
            // Provide explicit color hint for UI
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
                // Ensure parentNode matches the stream parent node id, which is the cleaned stream name
                $cleanParentId = strtolower(trim($streamName));
                if (str_starts_with($cleanParentId, 'stream ')) {
                    $cleanParentId = substr($cleanParentId, 7);
                }
                $baseData['data']['label'] .= "\nID: " . $app->getAttribute('app_id') . "\nStream: " . strtoupper($streamName);
                $baseData['type'] = 'appNode';
                $baseData['position'] = ['x' => 100, 'y' => 100];
                $baseData['parentNode'] = $cleanParentId; // match createStreamNode id
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
            // Use stream color from app's stream if available
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
            // Provide explicit color hint for UI
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

    // When creating app nodes (home and external), ensure style contains width/height and DB border color
    private function withAppStyle(array $style, ?string $borderColor = null): array
    {
        $borderColor = $borderColor ?: '#999999';
        return array_merge($style, [
            'width' => self::APP_WIDTH,
            'height' => self::APP_HEIGHT,
            'border' => '2px solid ' . $borderColor,
            'borderRadius' => '8px',
        ]);
    }
}
