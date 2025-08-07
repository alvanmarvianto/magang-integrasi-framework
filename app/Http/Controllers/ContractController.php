<?php

namespace App\Http\Controllers;

use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    protected ContractService $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    /**
     * Display a listing of all contracts
     */
    public function index(): Response
    {
        try {
            $allContracts = $this->contractService->getAllContracts();

            return Inertia::render('Contract/Index', [
                'contracts' => $allContracts->map(fn($c) => $c->toArray())->toArray(),
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Contract/Index', [
                'contracts' => [],
                'error' => 'Failed to retrieve contracts: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show contract for a specific app and contract ID
     */
    public function show(int $appId, int $contractId): Response
    {
        try {
            $contractData = $this->contractService->getContractForUser($appId, $contractId);

            if (!$contractData) {
                // Contract not found, but let's check if the app exists
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    abort(404, 'Application not found');
                }
                
                // Get all contracts for this app to show in sidebar
                $allContracts = $this->contractService->getContractsByAppId($appId);
                
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => $allContracts->map(fn($c) => $c->toArray())->toArray(),
                ]);
            }

            return Inertia::render('Contract/App', [
                'contract' => $contractData['contract']->toArray(),
                'app' => $contractData['app']->toArray(),
                'allContracts' => $contractData['allContracts']->map(fn($c) => $c->toArray())->toArray(),
            ]);
        } catch (\Exception $e) {
            // If there's an exception, try to gracefully handle it
            try {
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    // App doesn't exist, render contract page with app not found error
                    return Inertia::render('Contract/App', [
                        'contract' => null,
                        'app' => null,
                        'allContracts' => [],
                        'error' => 'Application not found',
                    ]);
                }
                
                // Get all contracts for this app if possible
                $allContracts = $this->contractService->getContractsByAppId($appId);
                
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => $allContracts->map(fn($c) => $c->toArray())->toArray(),
                    'error' => 'Failed to retrieve contract: ' . $e->getMessage(),
                ]);
            } catch (\Exception $fallbackException) {
                // Complete fallback - render with app not found error
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => null,
                    'allContracts' => [],
                    'error' => 'Application not found',
                ]);
            }
        }
    }

    /**
     * Redirect to the first available contract for an app
     */
    public function redirectToFirstContract(int $appId): RedirectResponse|Response
    {
        try {
            // Check if app has contracts
            $hasContracts = $this->contractService->appHasContracts($appId);
            
            if (!$hasContracts) {
                // If no contracts, render the contract page with empty data
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    abort(404, 'Application not found');
                }
                
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => [],
                ]);
            }

            $firstContract = $this->contractService->getFirstContractForApp($appId);

            if (!$firstContract) {
                abort(404, 'No contracts found for this app');
            }

            return redirect()->route('contract.show', [
                'app_id' => $appId,
                'contract_id' => $firstContract->id
            ]);
        } catch (\Exception $e) {
            // If there's an exception, try to gracefully handle it
            try {
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    // App doesn't exist, render contract page with app not found error
                    return Inertia::render('Contract/App', [
                        'contract' => null,
                        'app' => null,
                        'allContracts' => [],
                        'error' => 'Application not found',
                    ]);
                }
                
                // App exists but there was another error
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => [],
                    'error' => 'Failed to retrieve contracts: ' . $e->getMessage(),
                ]);
            } catch (\Exception $fallbackException) {
                // Complete fallback - render with app not found error
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => null,
                    'allContracts' => [],
                    'error' => 'Application not found',
                ]);
            }
        }
    }
}
