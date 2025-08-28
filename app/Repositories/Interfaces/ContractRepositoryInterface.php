<?php

namespace App\Repositories\Interfaces;

use App\Models\Contract;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContractRepositoryInterface
{
    /**
     * Get all contracts with relationships
     */
    public function getAllWithRelations(): Collection;

    /**
     * Get contracts by app ID
     */
    public function getByAppId(int $appId): Collection;

    /**
     * Get contracts by app ID with relationships
     */
    public function getByAppIdWithRelations(int $appId): Collection;

    /**
     * Find contract by ID with relationships
     */
    public function findByIdWithRelations(int $id): ?Contract;

    /**
     * Create new contract
     */
    public function create(array $data): Contract;

    /**
     * Update contract
     */
    public function update(Contract $contract, array $data): bool;

    /**
     * Delete contract
     */
    public function delete(Contract $contract): bool;

    /**
     * Get contract statistics
     */
    public function getContractStatistics(): array;

    /**
     * Get paginated contracts with optional search and sorting
     */
    public function getPaginatedContracts(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator;
}
