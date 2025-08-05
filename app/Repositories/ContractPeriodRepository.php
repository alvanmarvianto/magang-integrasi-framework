<?php

namespace App\Repositories;

use App\Models\ContractPeriod;
use App\Repositories\Interfaces\ContractPeriodRepositoryInterface;
use Illuminate\Support\Collection;

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
        
        // Clear related caches
        $this->clearEntityCache('contract_periods');
        if (isset($data['contract_id'])) {
            $this->clearEntityCache('contract_periods', "contract.{$data['contract_id']}");
        }
        
        return $contractPeriod;
    }

    /**
     * Update contract period
     */
    public function update(ContractPeriod $contractPeriod, array $data): bool
    {
        $result = $contractPeriod->update($data);
        
        if ($result) {
            // Clear related caches
            $this->clearEntityCache('contract_periods', $contractPeriod->id);
            $this->clearEntityCache('contract_periods', "contract.{$contractPeriod->contract_id}");
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
            // Clear related caches
            $this->clearEntityCache('contract_periods', $periodId);
            $this->clearEntityCache('contract_periods', "contract.{$contractId}");
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
