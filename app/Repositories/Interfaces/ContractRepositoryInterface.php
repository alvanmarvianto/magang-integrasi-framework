<?php

namespace App\Repositories\Interfaces;

use App\Models\Contract;
use Illuminate\Support\Collection;

interface ContractRepositoryInterface
{
    /**
     * Get all contracts
     */
    public function getAll(): Collection;

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
     * Find contract by ID
     */
    public function findById(int $id): ?Contract;

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
     * Check if contract exists by ID
     */
    public function existsById(int $id): bool;

    /**
     * Get contract statistics
     */
    public function getContractStatistics(): array;

    /**
     * Get contracts by currency type
     */
    public function getByCurrencyType(string $currencyType): Collection;

    /**
     * Search contracts by title or contract number
     */
    public function searchContracts(string $query): Collection;
}
