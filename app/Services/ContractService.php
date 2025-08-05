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
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): array {
        $paginatedContracts = $this->contractRepository->getPaginatedContracts($search, $perPage, $sortBy, $sortDesc);
        
        // Convert the paginated contract models to DTOs
        $contractDTOs = $paginatedContracts->map(function ($contract) {
            return ContractDTO::fromModel($contract);
        });
        
        // Create pagination data structure that matches Laravel's default pagination
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
        
        // Previous link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false
        ];
        
        // Page number links
        foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url) {
            $links[] = [
                'url' => $url,
                'label' => (string) $page,
                'active' => $page === $paginator->currentPage()
            ];
        }
        
        // Next link
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
        
        // Extract contract periods from data
        $contractPeriodsData = $data['contract_periods'] ?? [];
        unset($data['contract_periods']);
        
        $contract = $this->contractRepository->create($data);
        
        // Create contract periods if provided
        if (!empty($contractPeriodsData)) {
            foreach ($contractPeriodsData as $periodData) {
                $periodData['contract_id'] = $contract->id;
                $this->contractPeriodRepository->create($periodData);
            }
        }
        
        // Reload with relationships
        $contractWithRelations = $this->contractRepository->findByIdWithRelations($contract->id);
        
        return ContractDTO::fromModel($contractWithRelations);
    }

    /**
     * Update existing contract
     */
    public function updateContract(Contract $contract, array $data): ContractDTO
    {
        $this->validateContractData($data, $contract->id);
        
        // Extract contract periods from data
        $contractPeriodsData = $data['contract_periods'] ?? [];
        unset($data['contract_periods']);
        
        $this->contractRepository->update($contract, $data);
        
        // Update contract periods
        if (isset($contractPeriodsData)) {
            // Delete existing periods
            $existingPeriods = $this->contractPeriodRepository->getByContractId($contract->id);
            foreach ($existingPeriods as $period) {
                $this->contractPeriodRepository->delete($period);
            }
            
            // Create new periods
            foreach ($contractPeriodsData as $periodData) {
                $periodData['contract_id'] = $contract->id;
                $this->contractPeriodRepository->create($periodData);
            }
        }
        
        // Reload the contract to get updated data with relationships
        $updatedContract = $this->contractRepository->findByIdWithRelations($contract->id);
        
        return ContractDTO::fromModel($updatedContract);
    }

    /**
     * Delete contract
     */
    public function deleteContract(Contract $contract): bool
    {
        // Delete associated contract periods first
        $existingPeriods = $this->contractPeriodRepository->getByContractId($contract->id);
        foreach ($existingPeriods as $period) {
            $this->contractPeriodRepository->delete($period);
        }
        
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
     * Search contracts
     */
    public function searchContracts(string $query): Collection
    {
        $contracts = $this->contractRepository->searchContracts($query);
        return $contracts->map(fn($contract) => ContractDTO::fromModel($contract));
    }

    /**
     * Get contracts by currency type
     */
    public function getContractsByCurrencyType(string $currencyType): Collection
    {
        $contracts = $this->contractRepository->getByCurrencyType($currencyType);
        return $contracts->map(fn($contract) => ContractDTO::fromModel($contract));
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
     * Copy an existing contract to a new app
     */
    public function copyContract(int $sourceContractId, int $targetAppId): ContractDTO
    {
        // Find the source contract with all relationships
        $sourceContract = $this->contractRepository->findByIdWithRelations($sourceContractId);
        if (!$sourceContract) {
            throw new \InvalidArgumentException('Source contract not found');
        }

        // Validate target app exists
        $targetApp = $this->appRepository->findWithRelations($targetAppId);
        if (!$targetApp) {
            throw new \InvalidArgumentException('Target app not found');
        }

        // Prepare contract data for duplication
        $contractData = [
            'app_id' => $targetAppId,
            'title' => $sourceContract->title, // Keep original title without modification
            'contract_number' => $sourceContract->contract_number, // Keep original contract number
            'currency_type' => $sourceContract->currency_type,
            'contract_value_rp' => $sourceContract->contract_value_rp,
            'contract_value_non_rp' => $sourceContract->contract_value_non_rp,
            'lumpsum_value_rp' => $sourceContract->lumpsum_value_rp,
            'unit_value_rp' => $sourceContract->unit_value_rp,
        ];

        // Create the new contract
        $newContract = $this->contractRepository->create($contractData);

        // Copy contract periods if they exist
        if ($sourceContract->relationLoaded('contractPeriods') && $sourceContract->contractPeriods->isNotEmpty()) {
            foreach ($sourceContract->contractPeriods as $sourcePeriod) {
                $periodData = [
                    'contract_id' => $newContract->id,
                    'period_name' => $sourcePeriod->period_name,
                    'budget_type' => $sourcePeriod->budget_type,
                    'start_date' => $sourcePeriod->start_date,
                    'end_date' => $sourcePeriod->end_date,
                    'payment_value_rp' => $sourcePeriod->payment_value_rp,
                    'payment_value_non_rp' => $sourcePeriod->payment_value_non_rp,
                    'payment_status' => $sourcePeriod->payment_status,
                ];
                
                $this->contractPeriodRepository->create($periodData);
            }
        }

        // Reload with relationships
        $newContractWithRelations = $this->contractRepository->findByIdWithRelations($newContract->id);
        
        return ContractDTO::fromModel($newContractWithRelations);
    }

    /**
     * Get contracts available for copying (excluding contracts from the specified app)
     */
    public function getContractsForCopying(int $excludeAppId): Collection
    {
        $allContracts = $this->contractRepository->getAllWithRelations();
        
        // Filter out contracts from the specified app
        $availableContracts = $allContracts->filter(function ($contract) use ($excludeAppId) {
            return $contract->app_id !== $excludeAppId;
        });
        
        return $availableContracts->map(fn($contract) => ContractDTO::fromModel($contract));
    }

    /**
     * Check if contract exists by ID
     */
    public function contractExistsById(int $id): bool
    {
        return $this->contractRepository->existsById($id);
    }

    /**
     * Get contract data for user view
     */
    public function getContractForUser(int $appId, int $contractId): ?array
    {
        // Find the specific contract
        $contract = $this->contractRepository->findByIdWithRelations($contractId);
        
        if (!$contract || $contract->app_id !== $appId) {
            return null;
        }

        // Sort contract periods by ID (ascending order)
        if ($contract->relationLoaded('contractPeriods') && $contract->contractPeriods->isNotEmpty()) {
            $sortedPeriods = $contract->contractPeriods->sortBy('id');
            $contract->setRelation('contractPeriods', $sortedPeriods);
        }

        // Get the app details
        $app = $this->appRepository->findWithRelations($appId);
        
        if (!$app) {
            return null;
        }

        // Get all contracts for this app
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
        // First check if the app exists
        $app = $this->appRepository->findWithRelations($appId);
        
        if (!$app) {
            return null;
        }

        // Get contracts for this app
        $contracts = $this->contractRepository->getByAppId($appId);
        
        if ($contracts->isEmpty()) {
            return null;
        }

        // Get the first contract (sorted by ID for consistency)
        $firstContract = $contracts->sortBy('id')->first();
        
        return ContractDTO::fromModel($firstContract);
    }

    /**
     * Check if app exists and has contracts
     */
    public function appHasContracts(int $appId): bool
    {
        // Check if app exists
        $app = $this->appRepository->findWithRelations($appId);
        if (!$app) {
            return false;
        }

        // Check if app has contracts
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
        // Required fields validation
        $requiredFields = ['app_id', 'title', 'contract_number', 'currency_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate app exists
        $app = $this->appRepository->findWithRelations($data['app_id']);
        if (!$app) {
            throw new \InvalidArgumentException('Selected app does not exist');
        }

        // Validate currency type
        if (!in_array($data['currency_type'], ['rp', 'non_rp'])) {
            throw new \InvalidArgumentException('Invalid currency type');
        }

        // Validate contract number format (basic validation)
        if (strlen($data['contract_number']) > 255) {
            throw new \InvalidArgumentException('Contract number must not exceed 255 characters');
        }

        // Validate title length
        if (strlen($data['title']) > 255) {
            throw new \InvalidArgumentException('Contract title must not exceed 255 characters');
        }

        // Validate decimal values if provided
        $decimalFields = ['contract_value_rp', 'contract_value_non_rp', 'lumpsum_value_rp', 'unit_value_rp'];
        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!is_numeric($data[$field]) || $data[$field] < 0) {
                    throw new \InvalidArgumentException("Field {$field} must be a positive number");
                }
            }
        }

        // Business rule: For RP contracts, at least one RP value should be provided
        if ($data['currency_type'] === 'rp') {
            $hasRpValue = !empty($data['contract_value_rp']) || 
                         !empty($data['lumpsum_value_rp']) || 
                         !empty($data['unit_value_rp']);
            
            if (!$hasRpValue) {
                throw new \InvalidArgumentException('For Rupiah contracts, at least one Rupiah value must be provided');
            }
        }

        // Business rule: For Non-RP contracts, non_rp value should be provided
        if ($data['currency_type'] === 'non_rp' && empty($data['contract_value_non_rp'])) {
            throw new \InvalidArgumentException('For Non-Rupiah contracts, contract value must be provided');
        }
    }
}
