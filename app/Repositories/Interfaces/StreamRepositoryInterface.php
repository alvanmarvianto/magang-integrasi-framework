<?php

namespace App\Repositories\Interfaces;

use App\DTOs\StreamDTO;
use App\Models\Stream;
use Illuminate\Database\Eloquent\Collection;

interface StreamRepositoryInterface
{
    /**
     * Get all streams
     */
    public function getAll(): Collection;

    /**
     * Get all streams with their apps
     */
    public function getAllWithApps(): Collection;

    /**
     * Get limited number of streams with their apps for hierarchy
     */
    public function getAllWithAppsLimited(int $limit = 5): Collection;

    /**
     * Get all streams as DTOs
     */
    public function getAllAsDTO(): Collection;

    /**
     * Find stream by ID
     */
    public function findById(int $id): ?Stream;

    /**
     * Find stream by ID and return as DTO
     */
    public function findByIdAsDTO(int $id): ?StreamDTO;

    /**
     * Find stream by name
     */
    public function findByName(string $name): ?Stream;

    /**
     * Find stream by name and return as DTO
     */
    public function findByNameAsDTO(string $name): ?StreamDTO;

    /**
     * Find stream by name with apps
     */
    public function findByNameWithApps(string $name): ?Stream;

    /**
     * Create new stream
     */
    public function create(array $data): Stream;

    /**
     * Create new stream from DTO
     */
    public function createFromDTO(StreamDTO $streamDTO): Stream;

    /**
     * Update stream
     */
    public function update(Stream $stream, array $data): bool;

    /**
     * Update stream from DTO
     */
    public function updateFromDTO(Stream $stream, StreamDTO $streamDTO): bool;

    /**
     * Delete stream
     */
    public function delete(Stream $stream): bool;

    /**
     * Get streams by multiple names
     */
    public function getStreamsByNames(array $names): Collection;

    /**
     * Check if stream exists by name
     */
    public function existsByName(string $name): bool;

    /**
     * Get stream statistics
     */
    public function getStreamStatistics(): array;
} 