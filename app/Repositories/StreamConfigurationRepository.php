<?php

namespace App\Repositories;

use App\Models\Stream;
use App\Repositories\Interfaces\StreamConfigurationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StreamConfigurationRepository implements StreamConfigurationRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function getAllowedDiagramStreams(): array
    {
        return Cache::remember('allowed_diagram_streams', self::CACHE_TTL, function () {
            return Stream::allowedForDiagram()
                ->orderedByPriority()
                ->pluck('stream_name')
                ->toArray();
        });
    }

    public function getAllowedDiagramStreamsWithDetails(): Collection
    {
        return Cache::remember('allowed_diagram_streams_details', self::CACHE_TTL, function () {
            return Stream::allowedForDiagram()
                ->orderedByPriority()
                ->select('stream_id', 'stream_name', 'description', 'color', 'sort_order')
                ->get();
        });
    }

    public function isStreamAllowed(string $streamName): bool
    {
        return Cache::remember("stream_allowed_{$streamName}", self::CACHE_TTL, function () use ($streamName) {
            return Stream::where('stream_name', $streamName)
                ->allowedForDiagram()
                ->exists();
        });
    }

    public function getStreamConfiguration(string $streamName): ?object
    {
        return Cache::remember("stream_config_{$streamName}", self::CACHE_TTL, function () use ($streamName) {
            return Stream::where('stream_name', $streamName)->first();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('allowed_diagram_streams');
        Cache::forget('allowed_diagram_streams_details');
        
        // Clear individual stream caches
        $streams = Stream::pluck('stream_name');
        foreach ($streams as $streamName) {
            Cache::forget("stream_allowed_{$streamName}");
            Cache::forget("stream_config_{$streamName}");
        }
    }
}
