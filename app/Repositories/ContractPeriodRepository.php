<?php

namespace App\Repositories;

use App\Models\ContractPeriod;
use App\Repositories\Interfaces\ContractPeriodRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContractPeriodRepository extends BaseRepository implements ContractPeriodRepositoryInterface
{
    protected ContractPeriod $model;

    public function __construct()
    {
        $this->model = new ContractPeriod();
    }

    /**
     * Get contract periods by contract ID
     */
    public function getByContractId(int $contractId): Collection
    {
        return $this->handleCacheOperation("contract_periods.contract.{$contractId}", function () use ($contractId) {
            return $this->model->where('contract_id', $contractId)->get();
        });
    }

    /**
     * Create new contract period
     */
    public function create(array $data): ContractPeriod
    {
        $contractPeriod = $this->model->create($data);
        
        // Clear all related caches comprehensively
        $this->clearAllRelatedCaches($contractPeriod->id, $contractPeriod->contract_id);
        
        return $contractPeriod;
    }

    /**
     * Update contract period
     */
    public function update(ContractPeriod $contractPeriod, array $data): bool
    {
        $result = $contractPeriod->update($data);
        
        if ($result) {
            // Clear all related caches comprehensively
            $this->clearAllRelatedCaches($contractPeriod->id, $contractPeriod->contract_id);
        }
        
        return $result;
    }

    /**
     * Delete contract period
     */
    public function delete(ContractPeriod $contractPeriod): bool
    {
        $contractId = $contractPeriod->contract_id;
        $periodId = $contractPeriod->id;
        
        $result = $contractPeriod->delete();
        
        if ($result) {
            // Clear all related caches comprehensively
            $this->clearAllRelatedCaches($periodId, $contractId);
        }
        
        return $result;
    }

    /**
     * Clear all related caches
     */
    private function clearAllRelatedCaches(?int $periodId = null, ?int $contractId = null): void
    {
        // Clear specific contract period caches
        if ($periodId) {
            Cache::forget("contract_periods.{$periodId}");
            Cache::forget("contract_periods.{$periodId}.with_relations");
        }
        
        // Clear contract-specific period caches
        if ($contractId) {
            Cache::forget("contract_periods.contract.{$contractId}");
            Cache::forget("contract_periods.contract.{$contractId}.with_relations");
        }
        
        // Clear general period caches
        Cache::forget('contract_periods.all');
        Cache::forget('contract_periods.all.with_relations');
        Cache::forget('contract_periods.active');
    }

    /**
     * Get allowed sort fields for the repository
     */
    protected function getAllowedSortFields(): array
    {
        return ['id', 'period_name', 'budget_type', 'start_date', 'end_date', 'payment_status', 'created_at', 'updated_at'];
    }

    /**
     * Get default sort field for the repository
     */
    protected function getDefaultSortField(): string
    {
        return 'id';
    }

    /**
     * Get the entity name for cache operations
     */
    protected function getEntityName(): string
    {
        return 'contract_periods';
    }
}
