<?php

namespace App\Repositories\Interfaces;

use App\Models\ConnectionType;
use App\DTOs\ConnectionTypeDTO;
use Illuminate\Database\Eloquent\Collection;

interface ConnectionTypeRepositoryInterface
{
    /**
     * Get all connection types
     */
    public function getAll(): Collection;

    /**
     * Get all connection types with usage counts
     */
    public function getAllWithUsageCounts(): Collection;

    /**
     * Find connection type by ID
     */
    public function findById(int $id): ?ConnectionType;

    /**
     * Find connection type by name
     */
    public function findByName(string $name): ?ConnectionType;

    /**
     * Create new connection type
     */
    public function create(ConnectionTypeDTO $connectionTypeData): ConnectionType;

    /**
     * Update connection type
     */
    public function update(ConnectionType $connectionType, ConnectionTypeDTO $connectionTypeData): bool;

    /**
     * Delete connection type
     */
    public function delete(ConnectionType $connectionType): bool;

    /**
     * Check if connection type exists by name
     */
    public function existsByName(string $name): bool;

    /**
     * Get connection type statistics
     */
    public function getConnectionTypeStatistics(): array;

    /**
     * Get most used connection types
     */
    public function getMostUsedConnectionTypes(int $limit = 10): Collection;
}