<?php

namespace App\Repositories;

use App\Models\Contract;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Repositories\Exceptions\RepositoryException;

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
     * Get paginated contracts with optional search and sorting
     */
    public function getPaginatedContracts(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator {
        // Validate parameters
        $this->validatePaginationParams($perPage);
        
        if ($search !== null) {
            $this->validateNotEmpty($search, 'search');
        }

        try {
            $query = $this->model->with(['app']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('contract_number', 'like', "%{$search}%")
                      ->orWhereHas('app', function ($appQuery) use ($search) {
                          $appQuery->where('app_name', 'like', "%{$search}%");
                      });
                });
            }

            $this->applySorting($query, $sortBy, $sortDesc ? 'desc' : 'asc');

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to get paginated contracts', [
                'search' => $search,
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDesc' => $sortDesc,
                'exception' => $e->getMessage()
            ]);
            
            throw RepositoryException::createFailed('contract pagination', $e->getMessage());
        }
    }

    /**
     * Apply custom sorting logic for contracts
     */
    protected function applySortingLogic($query, string $sortBy, string $direction): void
    {
        switch ($sortBy) {
            case 'app_name':
                $query->leftJoin('apps', 'contracts.app_id', '=', 'apps.app_id')
                      ->orderBy('apps.app_name', $direction)
                      ->select('contracts.*');
                break;
            default:
                $query->orderBy($sortBy, $direction);
                break;
        }
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
        $this->clearAllContractCaches(null, $data['app_id'] ?? null);
        
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
            $this->clearAllContractCaches($contract->id, $contract->app_id);
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
            // Clear all contract-related caches systematically
            $this->clearAllContractCaches($contractId, $appId);
        }
        
        return $result;
    }

    /**
     * Clear all contract-related caches comprehensively
     */
    private function clearAllContractCaches(?int $contractId = null, ?int $appId = null): void
    {
        // Clear specific contract caches
        if ($contractId) {
            Cache::forget("contracts.{$contractId}");
            Cache::forget("contracts.{$contractId}.with_relations");
        }
        
        // Clear app-specific contract caches
        if ($appId) {
            Cache::forget("contracts.app.{$appId}");
            Cache::forget("contracts.app.{$appId}.with_relations");
            Cache::forget("contracts.app.{$appId}.with_apps");
        }
        
        // Clear general contract caches
        Cache::forget('contracts.all');
        Cache::forget('contracts.all.with_relations');
        Cache::forget('contracts.all_with_apps');
        Cache::forget('contracts.statistics');
        Cache::forget('contractss.statistics'); // plural form
        
        // Clear currency-specific caches
        Cache::forget('contracts.currency.rp');
        Cache::forget('contracts.currency.non_rp');
        
        // Clear app-related caches since contract counts may have changed
        if ($appId) {
            Cache::forget("app.{$appId}");
            Cache::forget("app.{$appId}.with_relations");
            Cache::forget("app.{$appId}.with_apps");
        }
        Cache::forget('app.all');
        Cache::forget('apps.all');
        Cache::forget('apps.statistics');
        Cache::forget('apps.with_integration_counts');
        
        // Clear stream-related caches
        Cache::forget('stream.apps');
        Cache::forget('stream.name_apps');
        
        // Clear technology-related caches as they might be affected
        Cache::forget('technology.components');
        Cache::forget('technology.mappings');
        
        // Clear any search caches that might exist for this app
        if ($appId) {
            for ($i = 1; $i <= 10; $i++) {
                Cache::forget("apps.search.{$i}");
                Cache::forget("app.search.{$i}");
            }
        }
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
                    ->sum('contract_value_rp') ?: 0,
                'total_value_non_rp' => $this->model->where('currency_type', 'non_rp')
                    ->sum('contract_value_non_rp') ?: 0,
                'apps_with_contracts' => $this->model->distinct('app_id')->count('app_id'),
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
        return ['id', 'title', 'contract_number', 'currency_type', 'app_name'];
    }

    /**
     * Get default sort field for the repository
     */
    protected function getDefaultSortField(): string
    {
        return 'app_name';
    }

    /**
     * Get the entity name for cache operations
     */
    protected function getEntityName(): string
    {
        return 'contracts';
    }
}
