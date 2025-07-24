<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_name',
        'nodes_layout',
        'edges_layout',
        'stream_config',
    ];

    protected $casts = [
        'nodes_layout' => 'array',
        'edges_layout' => 'array',
        'stream_config' => 'array',
    ];

    /**
     * Get layout for a specific stream
     */
    public static function getLayout(string $streamName): ?array
    {
        $layout = self::where('stream_name', $streamName)->first();
        return $layout ? [
            'nodes_layout' => $layout->nodes_layout,
            'edges_layout' => $layout->edges_layout,
            'stream_config' => $layout->stream_config,
        ] : null;
    }

    /**
     * Save layout for a specific stream
     */
    public static function saveLayout(string $streamName, array $nodesLayout, array $streamConfig, array $edgesLayout = []): void
    {
        self::updateOrCreate(
            ['stream_name' => $streamName],
            [
                'nodes_layout' => $nodesLayout,
                'edges_layout' => $edgesLayout,
                'stream_config' => $streamConfig,
            ]
        );
    }

    /**
     * Remove app from all stream layouts
     */
    public static function removeAppFromLayouts(int $appId): void
    {
        $layouts = self::all();
        
        foreach ($layouts as $layout) {
            $updated = false;
            $nodesLayout = $layout->nodes_layout ?? [];
            $edgesLayout = $layout->edges_layout ?? [];
            $streamConfig = $layout->stream_config ?? [];
            
            // Remove app node if it exists
            if (isset($nodesLayout[(string)$appId])) {
                unset($nodesLayout[(string)$appId]);
                $updated = true;
            }
            
            // Remove edges that involve this app
            $edgesLayout = array_filter($edgesLayout, function($edge) use ($appId) {
                return $edge['source'] !== (string)$appId && $edge['target'] !== (string)$appId;
            });
            
            // Update total nodes count in stream config
            if (isset($streamConfig['totalNodes'])) {
                $streamConfig['totalNodes'] = count($nodesLayout);
                $updated = true;
            }
            
            // Update total edges count in stream config
            if (isset($streamConfig['totalEdges'])) {
                $streamConfig['totalEdges'] = count($edgesLayout);
                $updated = true;
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $layout->update([
                    'nodes_layout' => $nodesLayout,
                    'edges_layout' => array_values($edgesLayout), // Re-index array
                    'stream_config' => $streamConfig,
                ]);
            }
        }
    }
}
