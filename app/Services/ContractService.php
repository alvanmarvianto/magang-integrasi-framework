<?php

namespace App\Services;

use App\DTOs\ContractDTO;
use App\Models\Contract;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use App\Repositories\Interfaces\AppRepositoryInterface;
use Illuminate\Support\Collection;

class ContractService
{
    protected ContractRepositoryInterface $contractRepository;
    protected AppRepositoryInterface $appRepository;

    public function __construct(
        ContractRepositoryInterface $contractRepository,
        AppRepositoryInterface $appRepository
    ) {
        $this->contractRepository = $contractRepository;
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
        
        $contract = $this->contractRepository->create($data);
        
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
        
        $this->contractRepository->update($contract, $data);
        
        // Reload the contract to get updated data with relationships
        $updatedContract = $this->contractRepository->findByIdWithRelations($contract->id);
        
        return ContractDTO::fromModel($updatedContract);
    }

    /**
     * Delete contract
     */
    public function deleteContract(Contract $contract): bool
    {
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
            'contract' => ContractDTO::fromModel($contract),
            'app_name' => $contract->app->app_name,
            'app_id' => $contract->app_id,
            'currency_types' => [
                ['value' => 'rp', 'label' => 'Rupiah'],
                ['value' => 'non_rp', 'label' => 'Non-Rupiah']
            ],
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
            'value' => $app->app_id,
            'label' => $app->app_name,
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
