<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
    public function index(Request $request)
    {
        try {
            $contracts = $this->contractService->getAllContracts();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contracts->map(fn($contract) => $contract->toArray()),
                    'statistics' => $this->contractService->getContractStatistics()
                ]);
            }

            return view('admin.contracts.index', [
                'contracts' => $contracts,
                'statistics' => $this->contractService->getContractStatistics()
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contracts: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contracts.index', [
                'contracts' => collect(),
                'statistics' => [],
                'error' => 'Failed to retrieve contracts: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display contracts for a specific app
     */
    public function byApp(Request $request, int $appId)
    {
        try {
            $contracts = $this->contractService->getContractsByAppId($appId);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contracts->map(fn($contract) => $contract->toArray()),
                    'app_id' => $appId
                ]);
            }

            return view('admin.contracts.by-app', [
                'contracts' => $contracts,
                'appId' => $appId
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contracts: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contracts.by-app', [
                'contracts' => collect(),
                'appId' => $appId,
                'error' => 'Failed to retrieve contracts: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new contract
     */
    public function create()
    {
        try {
            $formData = [
                'currency_types' => [
                    ['value' => 'rp', 'label' => 'Rupiah'],
                    ['value' => 'non_rp', 'label' => 'Non-Rupiah']
                ],
                'apps' => $this->contractService->getAppOptionsForForms()
            ];

            return view('admin.contracts.create', $formData);
        } catch (\Exception $e) {
            return view('admin.contracts.create', [
                'currency_types' => [
                    ['value' => 'rp', 'label' => 'Rupiah'],
                    ['value' => 'non_rp', 'label' => 'Non-Rupiah']
                ],
                'apps' => [],
                'error' => 'Failed to load create form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'app_id' => 'required|integer',
                'title' => 'required|string|max:255',
                'contract_number' => 'required|string|max:255',
                'currency_type' => 'required|in:rp,non_rp',
                'contract_value_rp' => 'nullable|numeric|min:0',
                'contract_value_non_rp' => 'nullable|numeric|min:0',
                'lumpsum_value_rp' => 'nullable|numeric|min:0',
                'unit_value_rp' => 'nullable|numeric|min:0',
            ]);

            $contract = $this->contractService->createContract($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract created successfully',
                    'data' => $contract->toArray()
                ], 201);
            }

            return redirect()->route('admin.contracts.show', $contract->id)
                ->with('success', 'Contract created successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create contract: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to create contract: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contract
     */
    public function show(Request $request, Contract $contract)
    {
        try {
            $contractDTO = $this->contractService->findContractById($contract->id);

            if (!$contractDTO) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contract not found'
                    ], 404);
                }

                return view('admin.contracts.index', [
                    'contracts' => collect(),
                    'statistics' => [],
                    'error' => 'Contract not found'
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contractDTO->toArray()
                ]);
            }

            return view('admin.contracts.show', [
                'contract' => $contractDTO
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contract: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contracts.show', [
                'contract' => null,
                'error' => 'Failed to retrieve contract: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for editing the specified contract
     */
    public function edit(Contract $contract)
    {
        try {
            $formData = $this->contractService->getContractFormData($contract->id);

            return view('admin.contracts.edit', $formData);
        } catch (\Exception $e) {
            return view('admin.contracts.edit', [
                'contract' => null,
                'app_name' => '',
                'app_id' => null,
                'currency_types' => [
                    ['value' => 'rp', 'label' => 'Rupiah'],
                    ['value' => 'non_rp', 'label' => 'Non-Rupiah']
                ],
                'apps' => [],
                'error' => 'Failed to load edit form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get form data for editing (API endpoint)
     */
    public function getFormData(Contract $contract): JsonResponse
    {
        try {
            $formData = $this->contractService->getContractFormData($contract->id);

            return response()->json([
                'success' => true,
                'data' => $formData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, Contract $contract): JsonResponse|RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'app_id' => 'required|integer',
                'title' => 'required|string|max:255',
                'contract_number' => 'required|string|max:255',
                'currency_type' => 'required|in:rp,non_rp',
                'contract_value_rp' => 'nullable|numeric|min:0',
                'contract_value_non_rp' => 'nullable|numeric|min:0',
                'lumpsum_value_rp' => 'nullable|numeric|min:0',
                'unit_value_rp' => 'nullable|numeric|min:0',
            ]);

            $updatedContract = $this->contractService->updateContract($contract, $validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract updated successfully',
                    'data' => $updatedContract->toArray()
                ]);
            }

            return redirect()->route('admin.contracts.show', $contract->id)
                ->with('success', 'Contract updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contract: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to update contract: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified contract
     */
    public function destroy(Request $request, Contract $contract): JsonResponse|RedirectResponse
    {
        try {
            $this->contractService->deleteContract($contract);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract deleted successfully'
                ]);
            }

            return redirect()->route('admin.contracts.index')
                ->with('success', 'Contract deleted successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete contract: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete contract: ' . $e->getMessage());
        }
    }

    /**
     * Search contracts
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters'
                ], 422);
            }

            $contracts = $this->contractService->searchContracts($query);

            return response()->json([
                'success' => true,
                'data' => $contracts->map(fn($contract) => $contract->toArray()),
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contracts by currency type
     */
    public function byCurrencyType(Request $request, string $currencyType): JsonResponse
    {
        try {
            if (!in_array($currencyType, ['rp', 'non_rp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid currency type'
                ], 422);
            }

            $contracts = $this->contractService->getContractsByCurrencyType($currencyType);

            return response()->json([
                'success' => true,
                'data' => $contracts->map(fn($contract) => $contract->toArray()),
                'currency_type' => $currencyType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contract statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->contractService->getContractStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
