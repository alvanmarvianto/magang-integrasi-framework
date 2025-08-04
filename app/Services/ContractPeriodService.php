<?php

namespace App\Services;

use App\DTOs\ContractPeriodDTO;
use App\Models\ContractPeriod;
use App\Repositories\Interfaces\ContractPeriodRepositoryInterface;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use Illuminate\Support\Collection;

class ContractPeriodService
{
    protected ContractPeriodRepositoryInterface $contractPeriodRepository;
    protected ContractRepositoryInterface $contractRepository;

    public function __construct(
        ContractPeriodRepositoryInterface $contractPeriodRepository,
        ContractRepositoryInterface $contractRepository
    ) {
        $this->contractPeriodRepository = $contractPeriodRepository;
        $this->contractRepository = $contractRepository;
    }

    /**
     * Get all contract periods as DTOs
     */
    public function getAllContractPeriods(): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->getAllWithRelations();
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Get contract periods by contract ID as DTOs
     */
    public function getContractPeriodsByContractId(int $contractId): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->getByContractIdWithRelations($contractId);
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Find contract period by ID and return as DTO
     */
    public function findContractPeriodById(int $id): ?ContractPeriodDTO
    {
        $contractPeriod = $this->contractPeriodRepository->findByIdWithRelations($id);
        return $contractPeriod ? ContractPeriodDTO::fromModel($contractPeriod) : null;
    }

    /**
     * Create new contract period
     */
    public function createContractPeriod(array $data): ContractPeriodDTO
    {
        $this->validateContractPeriodData($data);
        
        $contractPeriod = $this->contractPeriodRepository->create($data);
        
        // Reload with relationships
        $contractPeriodWithRelations = $this->contractPeriodRepository->findByIdWithRelations($contractPeriod->id);
        
        return ContractPeriodDTO::fromModel($contractPeriodWithRelations);
    }

    /**
     * Update existing contract period
     */
    public function updateContractPeriod(ContractPeriod $contractPeriod, array $data): ContractPeriodDTO
    {
        $this->validateContractPeriodData($data, $contractPeriod->id);
        
        $this->contractPeriodRepository->update($contractPeriod, $data);
        
        // Reload the contract period to get updated data with relationships
        $updatedContractPeriod = $this->contractPeriodRepository->findByIdWithRelations($contractPeriod->id);
        
        return ContractPeriodDTO::fromModel($updatedContractPeriod);
    }

    /**
     * Delete contract period
     */
    public function deleteContractPeriod(ContractPeriod $contractPeriod): bool
    {
        return $this->contractPeriodRepository->delete($contractPeriod);
    }

    /**
     * Get contract periods by payment status
     */
    public function getContractPeriodsByPaymentStatus(string $paymentStatus): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->getByPaymentStatus($paymentStatus);
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Get active contract periods
     */
    public function getActiveContractPeriods(): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->getActivePeriods();
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Get contract periods by budget type
     */
    public function getContractPeriodsByBudgetType(string $budgetType): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->getByBudgetType($budgetType);
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Search contract periods
     */
    public function searchContractPeriods(string $query): Collection
    {
        $contractPeriods = $this->contractPeriodRepository->searchPeriods($query);
        return $contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period));
    }

    /**
     * Get form data for contract period editing
     */
    public function getContractPeriodFormData(int $contractPeriodId): array
    {
        $contractPeriod = $this->contractPeriodRepository->findByIdWithRelations($contractPeriodId);
        
        if (!$contractPeriod) {
            throw new \InvalidArgumentException('Contract period not found');
        }

        return [
            'contract_period' => ContractPeriodDTO::fromModel($contractPeriod),
            'app_name' => $contractPeriod->contract->app->app_name,
            'contract_name' => $contractPeriod->contract->title,
            'contract_id' => $contractPeriod->contract_id,
            'budget_types' => [
                ['value' => 'AO', 'label' => 'Anggaran Operasional'],
                ['value' => 'RI', 'label' => 'Realisasi Investasi']
            ],
            'payment_statuses' => [
                ['value' => 'paid', 'label' => 'Sudah bayar'],
                ['value' => 'ba_process', 'label' => 'Proses BA'],
                ['value' => 'mka_process', 'label' => 'Proses di MKA'],
                ['value' => 'settlement_process', 'label' => 'Proses Settlement (LD<=9 Des)'],
                ['value' => 'addendum_process', 'label' => 'Proses Addendum'],
                ['value' => 'not_due', 'label' => 'Belum Jatuh Tempo/belum ada kebutuhan'],
                ['value' => 'has_issue', 'label' => 'Terdapat Isu'],
                ['value' => 'unpaid', 'label' => 'Tidak bayar'],
                ['value' => 'reserved_hr', 'label' => 'Dicadangkan (HR)'],
                ['value' => 'contract_moved', 'label' => 'Kontrak dipindahkan']
            ],
            'contracts' => $this->getContractOptionsForForms()
        ];
    }

    /**
     * Get contract options for forms
     */
    public function getContractOptionsForForms(): array
    {
        $contracts = $this->contractRepository->getAllWithRelations();
        return $contracts->map(fn($contract) => [
            'value' => $contract->id,
            'label' => $contract->title . ' (' . $contract->contract_number . ')',
            'app_name' => $contract->app->app_name
        ])->toArray();
    }

    /**
     * Check if contract period exists by ID
     */
    public function contractPeriodExistsById(int $id): bool
    {
        return $this->contractPeriodRepository->existsById($id);
    }

    /**
     * Get payment status options
     */
    public function getPaymentStatusOptions(): array
    {
        return [
            'paid' => 'Sudah bayar',
            'ba_process' => 'Proses BA',
            'mka_process' => 'Proses di MKA',
            'settlement_process' => 'Proses Settlement (LD<=9 Des)',
            'addendum_process' => 'Proses Addendum',
            'not_due' => 'Belum Jatuh Tempo/belum ada kebutuhan',
            'has_issue' => 'Terdapat Isu',
            'unpaid' => 'Tidak bayar',
            'reserved_hr' => 'Dicadangkan (HR)',
            'contract_moved' => 'Kontrak dipindahkan'
        ];
    }

    /**
     * Get budget type options
     */
    public function getBudgetTypeOptions(): array
    {
        return [
            'AO' => 'Anggaran Operasional',
            'RI' => 'Realisasi Investasi'
        ];
    }

    /**
     * Validate contract period data
     */
    private function validateContractPeriodData(array $data, ?int $excludeId = null): void
    {
        // Required fields validation
        $requiredFields = ['contract_id', 'period_name', 'budget_type', 'start_date', 'end_date', 'payment_status'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate contract exists
        $contract = $this->contractRepository->findById($data['contract_id']);
        if (!$contract) {
            throw new \InvalidArgumentException('Selected contract does not exist');
        }

        // Validate budget type
        if (!in_array($data['budget_type'], ['AO', 'RI'])) {
            throw new \InvalidArgumentException('Invalid budget type');
        }

        // Validate payment status
        $validStatuses = array_keys($this->getPaymentStatusOptions());
        if (!in_array($data['payment_status'], $validStatuses)) {
            throw new \InvalidArgumentException('Invalid payment status');
        }

        // Validate dates
        try {
            $startDate = new \DateTime($data['start_date']);
            $endDate = new \DateTime($data['end_date']);
            
            if ($startDate >= $endDate) {
                throw new \InvalidArgumentException('End date must be after start date');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format');
        }

        // Validate period name length
        if (strlen($data['period_name']) > 255) {
            throw new \InvalidArgumentException('Period name must not exceed 255 characters');
        }

        // Validate payment values if provided
        $paymentFields = ['payment_value_rp', 'payment_value_non_rp'];
        foreach ($paymentFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (!is_numeric($data[$field]) || $data[$field] < 0) {
                    throw new \InvalidArgumentException("Field {$field} must be a positive number");
                }
            }
        }

        // Business rule: Based on contract currency type, appropriate payment value should be provided
        if ($contract->currency_type === 'rp' && !empty($data['payment_value_rp'])) {
            // RP contract with RP payment value - valid
        } elseif ($contract->currency_type === 'non_rp' && !empty($data['payment_value_non_rp'])) {
            // Non-RP contract with Non-RP payment value - valid
        } elseif (empty($data['payment_value_rp']) && empty($data['payment_value_non_rp'])) {
            // No payment values provided - this might be valid for some cases
        } else {
            // Warn about currency type mismatch but don't prevent it
            // as there might be valid business reasons for mixed currencies
        }
    }
}
