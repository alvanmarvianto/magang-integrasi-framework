<?php

namespace App\Services;

use App\Models\Stream;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StreamConfigurationService
{
    /**
     * Get allowed streams for diagram operations (ordered by sort_order)
     */
    public function getAllowedDiagramStreams(): array
    {
        return Cache::remember('allowed_diagram_streams', 3600, function () {
            return Stream::where('is_allowed_for_diagram', true)
                ->orderBy('sort_order')
                ->pluck('stream_name')
                ->toArray();
        });
    }

    /**
     * Get allowed streams with full details (ordered by sort_order)
     */
    public function getAllowedDiagramStreamsWithDetails(): Collection
    {
        return Cache::remember('allowed_diagram_streams_details', 3600, function () {
            return Stream::where('is_allowed_for_diagram', true)
                ->orderBy('sort_order')
                ->select('stream_id', 'stream_name', 'description', 'color', 'sort_order')
                ->get();
        });
    }

    /**
     * Get all streams with full details (ordered by sort_order)
     */
    public function getAllStreamsWithDetails(): Collection
    {
        return Cache::remember('all_streams_details', 3600, function () {
            return Stream::orderBy('sort_order')
                ->select('stream_id', 'stream_name', 'description', 'color', 'sort_order', 'is_allowed_for_diagram')
                ->get();
        });
    }

    /**
     * Check if a stream is allowed for diagram operations
     */
    public function isStreamAllowed(string $streamName): bool
    {
        return Cache::remember("stream_allowed_{$streamName}", 3600, function () use ($streamName) {
            return Stream::where('stream_name', $streamName)
                ->where('is_allowed_for_diagram', true)
                ->exists();
        });
    }

    /**
     * Get stream configuration by name
     */
    public function getStreamConfiguration(string $streamName): ?Stream
    {
        return Cache::remember("stream_config_{$streamName}", 3600, function () use ($streamName) {
            return Stream::where('stream_name', $streamName)->first();
        });
    }

    /**
     * Clear the allowed streams cache
     */
    public function clearCache(): void
    {
        Cache::forget('allowed_diagram_streams');
        Cache::forget('allowed_diagram_streams_details');
        Cache::forget('all_streams_details');
        
        // Clear individual stream caches
        $streams = Stream::pluck('stream_name');
        foreach ($streams as $streamName) {
            Cache::forget("stream_allowed_{$streamName}");
            Cache::forget("stream_config_{$streamName}");
        }
    }

    /**
     * Legacy compatibility - get hardcoded streams
     * @deprecated Use getAllowedDiagramStreams() instead
     */
    public function getLegacyAllowedStreams(): array
    {
        return [
            'sp',
            'mi', 
            'ssk',
            'moneter',
            'market',
            'middleware'
        ];
    }
}
