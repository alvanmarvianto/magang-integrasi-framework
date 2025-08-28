<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
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
     * Get the stream that owns the layout
     */
    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'stream_id');
    }

    /**
     * Get layout for a specific stream
     */
    public static function getLayout(int $streamId): ?array
    {
        $layout = self::where('stream_id', $streamId)->first();
        return $layout ? [
            'nodes_layout' => $layout->nodes_layout,
            'edges_layout' => $layout->edges_layout,
            'stream_config' => $layout->stream_config,
        ] : null;
    }

    /**
     * Save layout for a specific stream
     */
    public static function saveLayout(int $streamId, array $nodesLayout, array $streamConfig, array $edgesLayout = []): void
    {
        self::updateOrCreate(
            ['stream_id' => $streamId],
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
            
            if (isset($nodesLayout[(string)$appId])) {
                unset($nodesLayout[(string)$appId]);
                $updated = true;
            }
            
            $edgesLayout = array_filter($edgesLayout, function($edge) use ($appId) {
                return $edge['source'] !== (string)$appId && $edge['target'] !== (string)$appId;
            });
            
            if (isset($streamConfig['totalNodes'])) {
                $streamConfig['totalNodes'] = count($nodesLayout);
                $updated = true;
            }
            
            if (isset($streamConfig['totalEdges'])) {
                $streamConfig['totalEdges'] = count($edgesLayout);
                $updated = true;
            }
            
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
