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
     * Find stream by ID
     */
    public function findById(int $id): ?Stream;

    /**
     * Find stream by name
     */
    public function findByName(string $name): ?Stream;

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