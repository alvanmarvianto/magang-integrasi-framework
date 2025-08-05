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

        // Get the first contract (or you could implement custom sorting)
        $firstContract = $contracts->sortBy('created_at')->first();
        
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
