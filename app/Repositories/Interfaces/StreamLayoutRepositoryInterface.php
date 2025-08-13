<?php

namespace App\Repositories\Interfaces;

use App\DTOs\StreamLayoutDTO;
use Illuminate\Support\Collection;

interface StreamLayoutRepositoryInterface
{
    /**
     * Get all stream layouts
     */
    public function getAll(): Collection;

    /**
     * Find stream layout by ID
     */
    public function findById(int $id): ?StreamLayoutDTO;

    /**
     * Find stream layout by stream name
     */
    public function findByStreamName(string $streamName): ?StreamLayoutDTO;

    /**
     * Find stream layout by stream ID
     */
    public function findByStreamId(int $streamId): ?StreamLayoutDTO;

    /**
     * Create a new stream layout
     */
    public function create(StreamLayoutDTO $dto): StreamLayoutDTO;

    /**
     * Update an existing stream layout
     */
    public function update(int $id, StreamLayoutDTO $dto): ?StreamLayoutDTO;

    /**
     * Save layout for a specific stream (create or update) - backward compatibility
     */
    public function saveLayout(string $streamName, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO;

    /**
     * Save layout for a specific stream by ID
     */
    public function saveLayoutById(int $streamId, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO;

    /**
     * Save layout for a specific stream by name (backward compatibility)
     */
    public function saveLayoutByName(string $streamName, array $nodesLayout, array $edgesLayout, array $streamConfig): StreamLayoutDTO;

    /**
     * Delete stream layout by ID
     */
    public function delete(int $id): bool;

    /**
     * Remove app from all stream layouts
     */
    public function removeAppFromLayouts(int $appId): void;

    /**
     * Get layout data for a specific stream by ID
     */
    public function getLayoutDataById(int $streamId): ?array;

    /**
     * Get layout data for a specific stream by name (backward compatibility)
     */
    public function getLayoutData(string $streamName): ?array;

    /**
     * Get statistics about stream layouts
     */
    public function getStatistics(): array;

    /**
     * Get streams with most nodes
     */
    public function getStreamsWithMostNodes(int $limit = 10): Collection;

    /**
     * Get streams with most edges
     */
    public function getStreamsWithMostEdges(int $limit = 10): Collection;
}