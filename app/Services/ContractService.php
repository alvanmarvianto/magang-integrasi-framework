<?php

namespace App\Services;

use App\DTOs\ContractDTO;
use App\DTOs\AppDTO;
use App\Models\Contract;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use App\Repositories\Interfaces\ContractPeriodRepositoryInterface;
use App\Repositories\Interfaces\AppRepositoryInterface;
use Illuminate\Support\Collection;

class ContractService
{
    protected ContractRepositoryInterface $contractRepository;
    protected ContractPeriodRepositoryInterface $contractPeriodRepository;
    protected AppRepositoryInterface $appRepository;

    public function __construct(
        ContractRepositoryInterface $contractRepository,
        ContractPeriodRepositoryInterface $contractPeriodRepository,
        AppRepositoryInterface $appRepository
    ) {
        $this->contractRepository = $contractRepository;
        $this->contractPeriodRepository = $contractPeriodRepository;
        $this->appRepository = $appRepository;
    }

    /**
     * Get paginated contracts with optional search and sorting
     */
    public function getPaginatedContracts(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'title',
        bool $sortDesc = false
    ): array {
        $paginatedContracts = $this->contractRepository->getPaginatedContracts($search, $perPage, $sortBy, $sortDesc);
        
        $contractDTOs = $paginatedContracts->map(function ($contract) {
            return ContractDTO::fromModel($contract);
        });
        
        $paginationData = [
            'data' => $contractDTOs->map(fn($dto) => $dto->toArray())->all(),
            'meta' => [
                'current_page' => $paginatedContracts->currentPage(),
                'last_page' => $paginatedContracts->lastPage(),
                'per_page' => $paginatedContracts->perPage(),
                'total' => $paginatedContracts->total(),
                'from' => $paginatedContracts->firstItem(),
                'to' => $paginatedContracts->lastItem(),
                'links' => $this->buildPaginationLinks($paginatedContracts),
            ],
        ];
        
        return [
            'contracts' => $paginationData,
        ];
    }

    /**
     * Build pagination links for the frontend
     */
    private function buildPaginationLinks($paginator): array
    {
        $links = [];
        
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false
        ];
        
        foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url) {
            $links[] = [
                'url' => $url,
                'label' => (string) $page,
                'active' => $page === $paginator->currentPage()
            ];
        }
        
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false
        ];
        
        return $links;
    }

    /**
     * Get all contracts as DTOs
     */
    public function getAllContracts(): Collection
    {
        $contracts = $this->contractRepository->getAllWithRelations();
        return $contracts->map(fn($contract) => ContractDTO::fromModel($contract));
    }

    /**
     * Get contracts by app ID as DTOs
     */
    public function getContractsByAppId(int $appId): Collection
    {
        $contracts = $this->contractRepository->getByAppIdWithRelations($appId);
        return $contracts->map(fn($contract) => ContractDTO::fromModel($contract));
    }

    /**
     * Find contract by ID and return as DTO
     */
    public function findContractById(int $id): ?ContractDTO
    {
        $contract = $this->contractRepository->findByIdWithRelations($id);
        return $contract ? ContractDTO::fromModel($contract) : null;
    }

    /**
     * Create new contract
     */
    public function createContract(array $data): ContractDTO
    {
        $this->validateContractData($data);
        
        $contractPeriodsData = $data['contract_periods'] ?? [];
        $appIds = $data['app_ids'] ?? [];
        
        unset($data['contract_periods'], $data['app_ids']);
        
        $contract = $this->contractRepository->create($data);
        
        if (!empty($appIds)) {
            $contract->apps()->attach($appIds);
        }
        
        if (!empty($contractPeriodsData)) {
            foreach ($contractPeriodsData as $periodData) {
                $periodData['contract_id'] = $contract->id;
                $this->contractPeriodRepository->create($periodData);
            }
        }
        
        $contractWithRelations = $this->contractRepository->findByIdWithRelations($contract->id);
        
        return ContractDTO::fromModel($contractWithRelations);
    }

    /**
     * Update existing contract
     */
    public function updateContract(Contract $contract, array $data): ContractDTO
    {
        $this->validateContractData($data, $contract->id);
        
        $contractPeriodsData = $data['contract_periods'] ?? [];
        $appIds = $data['app_ids'] ?? [];
        
        unset($data['contract_periods'], $data['app_ids']);
        
        $this->contractRepository->update($contract, $data);
        
        if (isset($appIds)) {
            $contract->apps()->sync($appIds);
        }
        
        if (isset($contractPeriodsData)) {
            $existingPeriods = $this->contractPeriodRepository->getByContractId($contract->id);
            $existingPeriodsById = $existingPeriods->keyBy('id');
            
            $updatedPeriodIds = [];
            
            foreach ($contractPeriodsData as $periodData) {
                $periodData['contract_id'] = $contract->id;
                
                if (isset($periodData['id']) && $existingPeriodsById->has($periodData['id'])) {
                    $existingPeriod = $existingPeriodsById->get($periodData['id']);
                    $this->contractPeriodRepository->update($existingPeriod, $periodData);
                    $updatedPeriodIds[] = $periodData['id'];
                } else {
                    $newPeriod = $this->contractPeriodRepository->create($periodData);
                    $updatedPeriodIds[] = $newPeriod->id;
                }
            }
            
            foreach ($existingPeriods as $period) {
                if (!in_array($period->id, $updatedPeriodIds)) {
                    $this->contractPeriodRepository->delete($period);
                }
            }
        }
        
        $updatedContract = $this->contractRepository->findByIdWithRelations($contract->id);
        
        return ContractDTO::fromModel($updatedContract);
    }

    /**
     * Delete contract
     */
    public function deleteContract(Contract $contract): bool
    {
        $existingPeriods = $this->contractPeriodRepository->getByContractId($contract->id);
        foreach ($existingPeriods as $period) {
            $this->contractPeriodRepository->delete($period);
        }
        
        $contract->apps()->detach();
        
        return $this->contractRepository->delete($contract);
    }

    /**
     * Get contract statistics
     */
    public function getContractStatistics(): array
    {
        return $this->contractRepository->getContractStatistics();
    }

    /**
     * Get form data for contract editing
     */
    public function getContractFormData(int $contractId): array
    {
        $contract = $this->contractRepository->findByIdWithRelations($contractId);
        
        if (!$contract) {
            throw new \InvalidArgumentException('Contract not found');
        }

        return [
            'contract' => ContractDTO::fromModel($contract)->toArray(),
            'apps' => $this->getAppOptionsForForms()
        ];
    }

    /**
     * Get app options for forms
     */
    public function getAppOptionsForForms(): array
    {
        $apps = $this->appRepository->getAppsWithIntegrationCounts();
        return $apps->map(fn($app) => [
            'app_id' => $app->app_id,
            'app_name' => $app->app_name,
        ])->toArray();
    }

    /**
     * Get contract data for user view
     */
    public function getContractForUser(int $appId, int $contractId): ?array
    {
        $contract = $this->contractRepository->findByIdWithRelations($contractId);

        if (!$contract) {
            return null;
        }

        $isAssociatedWithApp = $contract->apps->contains('app_id', $appId);
        
        if (!$isAssociatedWithApp) {
            return null;
        }

        if ($contract->relationLoaded('contractPeriods') && $contract->contractPeriods->isNotEmpty()) {
            $sortedPeriods = $contract->contractPeriods->sortBy('id');
            $contract->setRelation('contractPeriods', $sortedPeriods);
        }

        $app = $this->appRepository->findWithRelations($appId);
        
        if (!$app) {
            return null;
        }

        $allContracts = $this->contractRepository->getByAppIdWithRelations($appId);

        return [
            'contract' => ContractDTO::fromModel($contract),
            'app' => AppDTO::fromModel($app),
            'allContracts' => $allContracts->map(fn($c) => ContractDTO::fromModel($c)),
        ];
    }

    /**
     * Get the first available contract for an app
     */
    public function getFirstContractForApp(int $appId): ?ContractDTO
    {
        $app = $this->appRepository->findWithRelations($appId);
        
        if (!$app) {
            return null;
        }

        $contracts = $this->contractRepository->getByAppId($appId);
        
        if ($contracts->isEmpty()) {
            return null;
        }

        $firstContract = $contracts->sortBy('id')->first();
        
        return ContractDTO::fromModel($firstContract);
    }

    /**
     * Check if app exists and has contracts
     */
    public function appHasContracts(int $appId): bool
    {
        $app = $this->appRepository->findWithRelations($appId);
        if (!$app) {
            return false;
        }

        $contracts = $this->contractRepository->getByAppId($appId);
        
        return $contracts->isNotEmpty();
    }

    /**
     * Get app basic info
     */
    public function getAppInfo(int $appId): ?AppDTO
    {
        $app = $this->appRepository->findWithRelations($appId);
        
        if (!$app) {
            return null;
        }

        return AppDTO::fromModel($app);
    }

    /**
     * Validate contract data
     */
    private function validateContractData(array $data, ?int $excludeId = null): void
    {
        $requiredFields = ['title', 'contract_number', 'currency_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        if (isset($data['app_ids']) && !empty($data['app_ids'])) {
            foreach ($data['app_ids'] as $appId) {
                $app = $this->appRepository->findWithRelations($appId);
                if (!$app) {
                    throw new \InvalidArgumentException("App with ID {$appId} does not exist");
                }
            }
        }

        if (!in_array($data['currency_type'], ['rp', 'non_rp'])) {
            throw new \InvalidArgumentException('Invalid currency type');
        }

        if (strlen($data['contract_number']) > 255) {
            throw new \InvalidArgumentException('Contract number must not exceed 255 characters');
        }

        $decimalFields = ['contract_value_rp', 'contract_value_non_rp', 'lumpsum_value_rp', 'unit_value_rp'];
        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!is_numeric($data[$field]) || $data[$field] < 0) {
                    throw new \InvalidArgumentException("Field {$field} must be a positive number");
                }
            }
        }

        if ($data['currency_type'] === 'rp') {
            $hasRpValue = !empty($data['contract_value_rp']) || 
                         !empty($data['lumpsum_value_rp']) || 
                         !empty($data['unit_value_rp']);
            
            if (!$hasRpValue) {
                throw new \InvalidArgumentException('For Rupiah contracts, at least one Rupiah value must be provided');
            }
        }

        if ($data['currency_type'] === 'non_rp' && empty($data['contract_value_non_rp'])) {
            throw new \InvalidArgumentException('For Non-Rupiah contracts, contract value must be provided');
        }
    }
}
