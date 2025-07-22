<?php

namespace App\Services;

use App\Models\Stream;
use App\Http\Resources\StreamResource;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Support\Collection;

class StreamService
{
    protected StreamRepositoryInterface $streamRepository;

    public function __construct(StreamRepositoryInterface $streamRepository)
    {
        $this->streamRepository = $streamRepository;
    }

    /**
     * Get all streams
     */
    public function getAllStreams(): Collection
    {
        return StreamResource::collection($this->streamRepository->getAll())->collection;
    }

    /**
     * Find stream by ID
     */
    public function findStreamById(int $id): ?StreamResource
    {
        $stream = $this->streamRepository->findById($id);
        return $stream ? new StreamResource($stream) : null;
    }

    /**
     * Find stream by name
     */
    public function findStreamByName(string $name): ?StreamResource
    {
        $stream = $this->streamRepository->findByName($name);
        return $stream ? new StreamResource($stream) : null;
    }

    /**
     * Create new stream
     */
    public function createStream(array $data): StreamResource
    {
        $stream = $this->streamRepository->create($data);
        return new StreamResource($stream);
    }

    /**
     * Update stream
     */
    public function updateStream(Stream $stream, array $data): bool
    {
        return $this->streamRepository->update($stream, $data);
    }

    /**
     * Delete stream
     */
    public function deleteStream(Stream $stream): bool
    {
        return $this->streamRepository->delete($stream);
    }
} 