<?php

namespace App\Services;

use App\DTOs\ContractDTO;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use App\Repositories\Interfaces\AppRepositoryInterface;
use Illuminate\Support\Collection;

class UserContractService
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
     * Get contract data for user view
     */
    public function getContractForUser(int $appId, int $contractId): ?array
    {
        // Find the specific contract
        $contract = $this->contractRepository->findByIdWithRelations($contractId);
        
        if (!$contract || $contract->app_id !== $appId) {
            return null;
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
     * Get all contracts for an app
     */
    public function getContractsForApp(int $appId): Collection
    {
        $contracts = $this->contractRepository->getByAppIdWithRelations($appId);
        
        return $contracts->map(fn($contract) => ContractDTO::fromModel($contract));
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
}
