<?php

namespace App\Repositories\Interfaces;

use App\Models\ConnectionType;
use App\DTOs\ConnectionTypeDTO;
use Illuminate\Database\Eloquent\Collection;

interface ConnectionTypeRepositoryInterface
{
    /**
     * Get all connection types with usage counts
     */
    public function getAllWithUsageCounts(): Collection;

    /**
     * Find connection type by ID
     */
    public function findById(int $id): ?ConnectionType;

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
}