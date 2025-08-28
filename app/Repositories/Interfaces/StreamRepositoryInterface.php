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
     * Get streams with their apps filtered by allowed stream names
     */
    public function getAllWithAppsLimited(array $allowedStreamNames = []): Collection;

    /**
     * Find stream by ID
     */
    public function findById(int $id): ?Stream;

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
} 