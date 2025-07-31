<?php

namespace App\Repositories;

use App\Models\Stream;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class StreamRepository implements StreamRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function getAll(): Collection
    {
        return Cache::remember(
            'streams.all',
            self::CACHE_TTL,
            fn() => Stream::orderBy('stream_name')->get()
        );
    }

    public function getAllWithApps(): Collection
    {
        return Cache::remember(
            'streams.all_with_apps',
            self::CACHE_TTL,
            fn() => Stream::with('apps')->orderBy('stream_name')->get()
        );
    }

    public function findById(int $id): ?Stream
    {
        return Cache::remember(
            "stream.{$id}",
            self::CACHE_TTL,
            fn() => Stream::find($id)
        );
    }

    public function findByName(string $name): ?Stream
    {
        return Cache::remember(
            "stream.name.{$name}",
            self::CACHE_TTL,
            fn() => Stream::where('stream_name', $name)->first()
        );
    }

    public function findByNameWithApps(string $name): ?Stream
    {
        return Cache::remember(
            "stream.name.{$name}.with_apps",
            self::CACHE_TTL,
            fn() => Stream::with('apps')->where('stream_name', $name)->first()
        );
    }

    public function create(array $data): Stream
    {
        $stream = Stream::create([
            'stream_name' => $data['stream_name'],
        ]);

        $this->clearStreamCache();

        return $stream;
    }

    public function update(Stream $stream, array $data): bool
    {
        $oldName = $stream->stream_name;
        
        $updated = $stream->update([
            'stream_name' => $data['stream_name'],
        ]);

        if ($updated) {
            $this->clearStreamCache();
            Cache::forget("stream.name.{$oldName}");
            Cache::forget("stream.name.{$oldName}.with_apps");
        }

        return $updated;
    }

    public function delete(Stream $stream): bool
    {
        $streamName = $stream->stream_name;
        $streamId = $stream->stream_id;
        
        $deleted = $stream->delete();

        if ($deleted) {
            $this->clearStreamCache();
            Cache::forget("stream.{$streamId}");
            Cache::forget("stream.name.{$streamName}");
            Cache::forget("stream.name.{$streamName}.with_apps");
        }

        return $deleted;
    }

    public function getStreamsByNames(array $names): Collection
    {
        $sortedNames = $names;
        sort($sortedNames);
        $cacheKey = 'streams.names.' . md5(implode(',', $sortedNames));
        
        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => Stream::whereIn('stream_name', $names)->get()
        );
    }

    public function existsByName(string $name): bool
    {
        return Cache::remember(
            "stream.exists.{$name}",
            self::CACHE_TTL,
            fn() => Stream::where('stream_name', $name)->exists()
        );
    }

    public function getStreamStatistics(): array
    {
        return Cache::remember(
            'streams.statistics',
            self::CACHE_TTL,
            function () {
                $streams = Stream::withCount('apps')->get();
                
                return [
                    'total_streams' => $streams->count(),
                    'streams_with_apps' => $streams->where('apps_count', '>', 0)->count(),
                    'streams_without_apps' => $streams->where('apps_count', 0)->count(),
                    'total_apps' => $streams->sum('apps_count'),
                    'average_apps_per_stream' => $streams->count() > 0 
                        ? round($streams->sum('apps_count') / $streams->count(), 2)
                        : 0,
                    'stream_details' => $streams->map(function ($stream) {
                        return [
                            'stream_id' => $stream->stream_id,
                            'stream_name' => $stream->stream_name,
                            'apps_count' => $stream->apps_count,
                        ];
                    })->toArray(),
                ];
            }
        );
    }

    private function clearStreamCache(): void
    {
        Cache::forget('streams.all');
        Cache::forget('streams.all_with_apps');
        Cache::forget('streams.statistics');
    }
} 