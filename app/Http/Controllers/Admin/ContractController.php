<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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
     * Display a listing of contracts
     */
    public function index(Request $request): Response
    {
        try {
            $paginationData = $this->contractService->getPaginatedContracts(
                search: $request->get('search'),
                perPage: $request->get('per_page', 10),
                sortBy: $request->get('sort_by', 'title'),
                sortDesc: $request->boolean('sort_desc', false)
            );

            $statistics = $this->contractService->getContractStatistics();

            return Inertia::render('Admin/Contracts', [
                'contracts' => $paginationData['contracts'],
                'statistics' => $statistics
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Admin/Contracts', [
                'contracts' => ['data' => [], 'meta' => ['links' => []]],
                'statistics' => [],
                'error' => 'Failed to retrieve contracts: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new contract
     */
    public function create(): Response
    {
        try {
            $apps = $this->contractService->getAppOptionsForForms();

            return Inertia::render('Admin/ContractForm', [
                'formData' => [
                    'apps' => $apps
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Admin/ContractForm', [
                'formData' => [
                    'apps' => []
                ],
                'error' => 'Failed to load form data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $contract = $this->contractService->createContract($request->all());

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Contract created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create contract: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified contract
     */
    public function edit(Contract $contract): Response
    {
        try {
            $contractDTO = $this->contractService->findContractById($contract->id);
            $formData = $this->contractService->getContractFormData($contract->id);

            return Inertia::render('Admin/ContractForm', [
                'contract' => $contractDTO ? $contractDTO->toArray() : null,
                'formData' => $formData
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Admin/ContractForm', [
                'contract' => null,
                'formData' => [
                    'apps' => []
                ],
                'error' => 'Failed to retrieve contract: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, Contract $contract): RedirectResponse
    {
        try {
            $updatedContract = $this->contractService->updateContract($contract, $request->all());

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Contract updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update contract: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified contract
     */
    public function destroy(Contract $contract): RedirectResponse
    {
        try {
            $this->contractService->deleteContract($contract);

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Contract deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.contracts.index')
                ->with('error', 'Failed to delete contract: ' . $e->getMessage());
        }
    }
}
