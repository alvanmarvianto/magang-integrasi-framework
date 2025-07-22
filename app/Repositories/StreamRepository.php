<?php

namespace App\Repositories;

use App\Models\Stream;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StreamRepository implements StreamRepositoryInterface
{
    public function getAll(): Collection
    {
        return Stream::all();
    }

    public function findById(int $id): ?Stream
    {
        return Stream::find($id);
    }

    public function findByName(string $name): ?Stream
    {
        return Stream::where('stream_name', $name)->first();
    }

    public function create(array $data): Stream
    {
        return Stream::create([
            'stream_name' => $data['stream_name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Stream $stream, array $data): bool
    {
        return $stream->update([
            'stream_name' => $data['stream_name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function delete(Stream $stream): bool
    {
        return $stream->delete();
    }
} 