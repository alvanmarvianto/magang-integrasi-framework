<?php

namespace App\Repositories;

use App\Models\Contract;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContractRepository extends BaseRepository implements ContractRepositoryInterface
{
    protected Contract $model;

    public function __construct()
    {
        $this->model = new Contract();
    }

    /**
     * Get all contracts
     */
    public function getAll(): Collection
    {
        return $this->handleCacheOperation('contracts.all', function () {
            return $this->model->all();
        });
    }

    /**
     * Get all contracts with relationships
     */
    public function getAllWithRelations(): Collection
    {
        return $this->handleCacheOperation('contracts.all.with_relations', function () {
            return $this->model->with(['app', 'contractPeriods'])->get();
        });
    }

    /**
     * Get contracts by app ID
     */
    public function getByAppId(int $appId): Collection
    {
        return $this->handleCacheOperation("contracts.app.{$appId}", function () use ($appId) {
            return $this->model->where('app_id', $appId)->get();
        });
    }

    /**
     * Get contracts by app ID with relationships
     */
    public function getByAppIdWithRelations(int $appId): Collection
    {
        return $this->handleCacheOperation("contracts.app.{$appId}.with_relations", function () use ($appId) {
            return $this->model->with(['app', 'contractPeriods'])
                ->where('app_id', $appId)
                ->get();
        });
    }

    /**
     * Find contract by ID
     */
    public function findById(int $id): ?Contract
    {
        return $this->handleCacheOperation("contracts.{$id}", function () use ($id) {
            return $this->model->find($id);
        });
    }

    /**
     * Find contract by ID with relationships
     */
    public function findByIdWithRelations(int $id): ?Contract
    {
        return $this->handleCacheOperation("contracts.{$id}.with_relations", function () use ($id) {
            return $this->model->with(['app', 'contractPeriods'])->find($id);
        });
    }

    /**
     * Create new contract
     */
    public function create(array $data): Contract
    {
        $contract = $this->model->create($data);
        
        // Clear related caches
        $this->clearEntityCache('contracts');
        if (isset($data['app_id'])) {
            $this->clearEntityCache('contracts', "app.{$data['app_id']}");
        }
        
        return $contract;
    }

    /**
     * Update contract
     */
    public function update(Contract $contract, array $data): bool
    {
        $result = $contract->update($data);
        
        if ($result) {
            // Clear related caches
            $this->clearEntityCache('contracts', $contract->id);
            $this->clearEntityCache('contracts', "app.{$contract->app_id}");
        }
        
        return $result;
    }

    /**
     * Delete contract
     */
    public function delete(Contract $contract): bool
    {
        $appId = $contract->app_id;
        $contractId = $contract->id;
        
        $result = $contract->delete();
        
        if ($result) {
            // Clear related caches
            $this->clearEntityCache('contracts', $contractId);
            $this->clearEntityCache('contracts', "app.{$appId}");
        }
        
        return $result;
    }

    /**
     * Check if contract exists by ID
     */
    public function existsById(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get contract statistics
     */
    public function getContractStatistics(): array
    {
        return $this->handleCacheOperation('contracts.statistics', function () {
            return [
                'total_contracts' => $this->model->count(),
                'rp_contracts' => $this->model->where('currency_type', 'rp')->count(),
                'non_rp_contracts' => $this->model->where('currency_type', 'non_rp')->count(),
                'total_value_rp' => $this->model->where('currency_type', 'rp')
                    ->sum('contract_value_rp'),
                'total_value_non_rp' => $this->model->where('currency_type', 'non_rp')
                    ->sum('contract_value_non_rp'),
            ];
        }, CacheConfig::STATISTICS_TTL);
    }

    /**
     * Get contracts by currency type
     */
    public function getByCurrencyType(string $currencyType): Collection
    {
        return $this->handleCacheOperation("contracts.currency.{$currencyType}", function () use ($currencyType) {
            return $this->model->where('currency_type', $currencyType)->get();
        });
    }

    /**
     * Search contracts by title or contract number
     */
    public function searchContracts(string $query): Collection
    {
        // Don't cache search results as they can be highly variable
        return $this->model->where('title', 'like', "%{$query}%")
            ->orWhere('contract_number', 'like', "%{$query}%")
            ->with(['app'])
            ->get();
    }

    /**
     * Get allowed sort fields for the repository
     */
    protected function getAllowedSortFields(): array
    {
        return ['id', 'title', 'contract_number', 'currency_type', 'created_at', 'updated_at'];
    }

    /**
     * Get default sort field for the repository
     */
    protected function getDefaultSortField(): string
    {
        return 'created_at';
    }

    /**
     * Get the entity name for cache operations
     */
    protected function getEntityName(): string
    {
        return 'contracts';
    }
}
