<?php

namespace App\Repositories\Interfaces;

use App\Models\ContractPeriod;
use Illuminate\Support\Collection;

interface ContractPeriodRepositoryInterface
{

    /**
     * Get contract periods by contract ID
     */
    public function getByContractId(int $contractId): Collection;

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
}
