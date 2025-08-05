<?php

namespace App\Http\Controllers;

use App\Services\UserContractService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    protected UserContractService $userContractService;

    public function __construct(UserContractService $userContractService)
    {
        $this->userContractService = $userContractService;
    }

    /**
     * Show contract for a specific app and contract ID
     */
    public function show(int $appId, int $contractId): Response
    {
        try {
            $contractData = $this->userContractService->getContractForUser($appId, $contractId);

            if (!$contractData) {
                abort(404, 'Contract not found or does not belong to this app');
            }

            return Inertia::render('Contract', [
                'contract' => $contractData['contract']->toArray(),
                'app' => $contractData['app']->toArray(),
                'allContracts' => $contractData['allContracts']->map(fn($c) => $c->toArray())->toArray(),
            ]);
        } catch (\Exception $e) {
            abort(500, 'Failed to retrieve contract: ' . $e->getMessage());
        }
    }

    /**
     * Redirect to the first available contract for an app
     */
    public function redirectToFirstContract(int $appId): RedirectResponse
    {
        try {
            $firstContract = $this->userContractService->getFirstContractForApp($appId);

            if (!$firstContract) {
                abort(404, 'No contracts found for this app');
            }

            return redirect()->route('contract.show', [
                'app_id' => $appId,
                'contract_id' => $firstContract->id
            ]);
        } catch (\Exception $e) {
            abort(500, 'Failed to retrieve contracts: ' . $e->getMessage());
        }
    }
}
