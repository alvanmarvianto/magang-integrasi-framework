<?php

namespace App\Http\Controllers;

use App\Services\ContractService;
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
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    abort(404, 'Application not found');
                }
                
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
            try {
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    return Inertia::render('Contract/App', [
                        'contract' => null,
                        'app' => null,
                        'allContracts' => [],
                        'error' => 'Application not found',
                    ]);
                }
                
                $allContracts = $this->contractService->getContractsByAppId($appId);
                
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => $allContracts->map(fn($c) => $c->toArray())->toArray(),
                    'error' => 'Failed to retrieve contract: ' . $e->getMessage(),
                ]);
            } catch (\Exception $fallbackException) {
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
            $hasContracts = $this->contractService->appHasContracts($appId);
            
            if (!$hasContracts) {
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
            try {
                $appInfo = $this->contractService->getAppInfo($appId);
                
                if (!$appInfo) {
                    return Inertia::render('Contract/App', [
                        'contract' => null,
                        'app' => null,
                        'allContracts' => [],
                        'error' => 'Application not found',
                    ]);
                }
                
                return Inertia::render('Contract/App', [
                    'contract' => null,
                    'app' => $appInfo->toArray(),
                    'allContracts' => [],
                    'error' => 'Failed to retrieve contracts: ' . $e->getMessage(),
                ]);
            } catch (\Exception $fallbackException) {
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
