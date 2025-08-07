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
     * Get all contract periods
     */
    public function getAll(): Collection
    {
        return $this->handleCacheOperation('contract_periods.all', function () {
            return $this->model->all();
        });
    }

    /**
     * Get all contract periods with relationships
     */
    public function getAllWithRelations(): Collection
    {
        return $this->handleCacheOperation('contract_periods.all.with_relations', function () {
            return $this->model->with(['contract', 'contract.app'])->get();
        });
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
     * Get contract periods by contract ID with relationships
     */
    public function getByContractIdWithRelations(int $contractId): Collection
    {
        return $this->handleCacheOperation("contract_periods.contract.{$contractId}.with_relations", function () use ($contractId) {
            return $this->model->with(['contract', 'contract.app'])
                ->where('contract_id', $contractId)
                ->get();
        });
    }

    /**
     * Find contract period by ID
     */
    public function findById(int $id): ?ContractPeriod
    {
        return $this->handleCacheOperation("contract_periods.{$id}", function () use ($id) {
            return $this->model->find($id);
        });
    }

    /**
     * Find contract period by ID with relationships
     */
    public function findByIdWithRelations(int $id): ?ContractPeriod
    {
        return $this->handleCacheOperation("contract_periods.{$id}.with_relations", function () use ($id) {
            return $this->model->with(['contract', 'contract.app'])->find($id);
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
     * Check if contract period exists by ID
     */
    public function existsById(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get contract periods by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection
    {
        return $this->handleCacheOperation("contract_periods.status.{$paymentStatus}", function () use ($paymentStatus) {
            return $this->model->where('payment_status', $paymentStatus)->get();
        });
    }

    /**
     * Get active contract periods (current date within period)
     */
    public function getActivePeriods(): Collection
    {
        return $this->handleCacheOperation('contract_periods.active', function () {
            $now = now()->toDateString();
            return $this->model->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->get();
        }, CacheConfig::SEARCH_TTL); // Shorter cache for time-sensitive data
    }

    /**
     * Get contract periods by budget type
     */
    public function getByBudgetType(string $budgetType): Collection
    {
        return $this->handleCacheOperation("contract_periods.budget.{$budgetType}", function () use ($budgetType) {
            return $this->model->where('budget_type', $budgetType)->get();
        });
    }

    /**
     * Search contract periods by period name
     */
    public function searchPeriods(string $query): Collection
    {
        // Don't cache search results as they can be highly variable
        return $this->model->where('period_name', 'like', "%{$query}%")
            ->with(['contract', 'contract.app'])
            ->get();
    }

    /**
     * Clear all related caches comprehensively
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
            
            // Clear contract caches since period data affects contract DTOs
            Cache::forget("contracts.{$contractId}");
            Cache::forget("contracts.{$contractId}.with_relations");
        }
        
        // Clear general period caches
        Cache::forget('contract_periods.all');
        Cache::forget('contract_periods.all.with_relations');
        Cache::forget('contract_periods.active');
        
        // Clear contract list caches that include period data
        Cache::forget('contracts.all');
        Cache::forget('contracts.all.with_relations');
        
        // Clear payment status specific caches
        $paymentStatuses = ['paid', 'ba_process', 'mka_process', 'settlement_process', 'addendum_process', 'not_due', 'has_issue', 'unpaid', 'reserved_hr', 'contract_moved'];
        foreach ($paymentStatuses as $status) {
            Cache::forget("contract_periods.status.{$status}");
        }
        
        // Clear budget type specific caches
        Cache::forget("contract_periods.budget.AO");
        Cache::forget("contract_periods.budget.RI");
        
        // Clear app-related caches that might be affected
        if ($contractId) {
            // Get contract to find related app IDs and clear their caches
            try {
                $contract = \App\Models\Contract::with('apps')->find($contractId);
                if ($contract && $contract->apps) {
                    foreach ($contract->apps as $app) {
                        Cache::forget("contracts.app.{$app->app_id}");
                        Cache::forget("contracts.app.{$app->app_id}.with_relations");
                    }
                }
            } catch (\Exception $e) {
                // If we can't get the contract, clear some common app-contract caches
                for ($i = 1; $i <= 50; $i++) {
                    Cache::forget("contracts.app.{$i}");
                    Cache::forget("contracts.app.{$i}.with_relations");
                }
            }
        }
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
