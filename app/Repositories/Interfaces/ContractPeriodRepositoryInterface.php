<?php

namespace App\Repositories\Interfaces;

use App\Models\ContractPeriod;
use Illuminate\Support\Collection;

interface ContractPeriodRepositoryInterface
{
    /**
     * Get all contract periods
     */
    public function getAll(): Collection;

    /**
     * Get all contract periods with relationships
     */
    public function getAllWithRelations(): Collection;

    /**
     * Get contract periods by contract ID
     */
    public function getByContractId(int $contractId): Collection;

    /**
     * Get contract periods by contract ID with relationships
     */
    public function getByContractIdWithRelations(int $contractId): Collection;

    /**
     * Find contract period by ID
     */
    public function findById(int $id): ?ContractPeriod;

    /**
     * Find contract period by ID with relationships
     */
    public function findByIdWithRelations(int $id): ?ContractPeriod;

    /**
     * Create new contract period
     */
    public function create(array $data): ContractPeriod;

    /**
     * Update contract period
     */
    public function update(ContractPeriod $contractPeriod, array $data): bool;

    /**
     * Delete contract period
     */
    public function delete(ContractPeriod $contractPeriod): bool;

    /**
     * Check if contract period exists by ID
     */
    public function existsById(int $id): bool;

    /**
     * Get contract periods by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection;

    /**
     * Get active contract periods (current date within period)
     */
    public function getActivePeriods(): Collection;

    /**
     * Get contract periods by budget type
     */
    public function getByBudgetType(string $budgetType): Collection;

    /**
     * Search contract periods by period name
     */
    public function searchPeriods(string $query): Collection;
}
