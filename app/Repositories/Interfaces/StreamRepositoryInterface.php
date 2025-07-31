<?php

namespace App\Repositories\Interfaces;

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
     * Find stream by ID
     */
    public function findById(int $id): ?Stream;

    /**
     * Find stream by name
     */
    public function findByName(string $name): ?Stream;

    /**
     * Find stream by name with apps
     */
    public function findByNameWithApps(string $name): ?Stream;

    /**
     * Create new stream
     */
    public function create(array $data): Stream;

    /**
     * Update stream
     */
    public function update(Stream $stream, array $data): bool;

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