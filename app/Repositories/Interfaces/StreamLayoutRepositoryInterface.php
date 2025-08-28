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
     * Find stream layout by stream name
     */
    public function findByStreamName(string $streamName): ?StreamLayoutDTO;

    /**
     * Find stream layout by stream ID
     */
    public function findByStreamId(int $streamId): ?StreamLayoutDTO;
    
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
     * Remove app from all stream layouts
     */
    public function removeAppFromLayouts(int $appId): void;

    /**
     * Get layout data for a specific stream by ID
     */
    public function getLayoutDataById(int $streamId): ?array;
}