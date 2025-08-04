<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPeriod;
use App\Services\ContractPeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ContractPeriodController extends Controller
{
    protected ContractPeriodService $contractPeriodService;

    public function __construct(ContractPeriodService $contractPeriodService)
    {
        $this->contractPeriodService = $contractPeriodService;
    }

    /**
     * Display a listing of contract periods
     */
    public function index(Request $request)
    {
        try {
            $contractPeriods = $this->contractPeriodService->getAllContractPeriods();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contractPeriods->map(fn($period) => $period->toArray())
                ]);
            }

            return view('admin.contract-periods.index', [
                'contractPeriods' => $contractPeriods
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contract periods: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contract-periods.index', [
                'contractPeriods' => collect(),
                'error' => 'Failed to retrieve contract periods: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display contract periods for a specific contract
     */
    public function byContract(Request $request, int $contractId)
    {
        try {
            $contractPeriods = $this->contractPeriodService->getContractPeriodsByContractId($contractId);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contractPeriods->map(fn($period) => $period->toArray()),
                    'contract_id' => $contractId
                ]);
            }

            return view('admin.contract-periods.by-contract', [
                'contractPeriods' => $contractPeriods,
                'contractId' => $contractId
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contract periods: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contract-periods.by-contract', [
                'contractPeriods' => collect(),
                'contractId' => $contractId,
                'error' => 'Failed to retrieve contract periods: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new contract period
     */
    public function create(Request $request)
    {
        try {
            $formData = [
                'budget_types' => [
                    ['value' => 'AO', 'label' => 'Anggaran Operasional'],
                    ['value' => 'RI', 'label' => 'Realisasi Investasi']
                ],
                'payment_statuses' => array_map(fn($value, $label) => [
                    'value' => $value, 
                    'label' => $label
                ], array_keys($this->contractPeriodService->getPaymentStatusOptions()), $this->contractPeriodService->getPaymentStatusOptions()),
                'contracts' => $this->contractPeriodService->getContractOptionsForForms()
            ];

            // If contract_id is provided, pre-select it
            if ($request->has('contract_id')) {
                $formData['selected_contract_id'] = $request->get('contract_id');
            }

            return view('admin.contract-periods.create', $formData);
        } catch (\Exception $e) {
            return view('admin.contract-periods.create', [
                'budget_types' => [
                    ['value' => 'AO', 'label' => 'Anggaran Operasional'],
                    ['value' => 'RI', 'label' => 'Realisasi Investasi']
                ],
                'payment_statuses' => [],
                'contracts' => [],
                'error' => 'Failed to load create form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created contract period
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'contract_id' => 'required|integer',
                'period_name' => 'required|string|max:255',
                'budget_type' => 'required|in:AO,RI',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'payment_value_rp' => 'nullable|numeric|min:0',
                'payment_value_non_rp' => 'nullable|numeric|min:0',
                'payment_status' => 'required|string',
            ]);

            $contractPeriod = $this->contractPeriodService->createContractPeriod($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract period created successfully',
                    'data' => $contractPeriod->toArray()
                ], 201);
            }

            return redirect()->route('admin.contract-periods.show', $contractPeriod->id)
                ->with('success', 'Contract period created successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create contract period: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to create contract period: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contract period
     */
    public function show(Request $request, ContractPeriod $contractPeriod)
    {
        try {
            $contractPeriodDTO = $this->contractPeriodService->findContractPeriodById($contractPeriod->id);

            if (!$contractPeriodDTO) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contract period not found'
                    ], 404);
                }

                return view('admin.contract-periods.index', [
                    'contractPeriods' => collect(),
                    'error' => 'Contract period not found'
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $contractPeriodDTO->toArray()
                ]);
            }

            return view('admin.contract-periods.show', [
                'contractPeriod' => $contractPeriodDTO
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve contract period: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.contract-periods.show', [
                'contractPeriod' => null,
                'error' => 'Failed to retrieve contract period: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for editing the specified contract period
     */
    public function edit(ContractPeriod $contractPeriod)
    {
        try {
            $formData = $this->contractPeriodService->getContractPeriodFormData($contractPeriod->id);

            return view('admin.contract-periods.edit', $formData);
        } catch (\Exception $e) {
            return view('admin.contract-periods.edit', [
                'contract_period' => null,
                'app_name' => '',
                'contract_name' => '',
                'contract_id' => null,
                'budget_types' => [
                    ['value' => 'AO', 'label' => 'Anggaran Operasional'],
                    ['value' => 'RI', 'label' => 'Realisasi Investasi']
                ],
                'payment_statuses' => [],
                'contracts' => [],
                'error' => 'Failed to load edit form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get form data for editing (API endpoint)
     */
    public function getFormData(ContractPeriod $contractPeriod): JsonResponse
    {
        try {
            $formData = $this->contractPeriodService->getContractPeriodFormData($contractPeriod->id);

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
     * Update the specified contract period
     */
    public function update(Request $request, ContractPeriod $contractPeriod): JsonResponse|RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'contract_id' => 'required|integer',
                'period_name' => 'required|string|max:255',
                'budget_type' => 'required|in:AO,RI',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'payment_value_rp' => 'nullable|numeric|min:0',
                'payment_value_non_rp' => 'nullable|numeric|min:0',
                'payment_status' => 'required|string',
            ]);

            $updatedContractPeriod = $this->contractPeriodService->updateContractPeriod($contractPeriod, $validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract period updated successfully',
                    'data' => $updatedContractPeriod->toArray()
                ]);
            }

            return redirect()->route('admin.contract-periods.show', $contractPeriod->id)
                ->with('success', 'Contract period updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contract period: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to update contract period: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified contract period
     */
    public function destroy(Request $request, ContractPeriod $contractPeriod): JsonResponse|RedirectResponse
    {
        try {
            $this->contractPeriodService->deleteContractPeriod($contractPeriod);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contract period deleted successfully'
                ]);
            }

            return redirect()->route('admin.contract-periods.index')
                ->with('success', 'Contract period deleted successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete contract period: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete contract period: ' . $e->getMessage());
        }
    }

    /**
     * Search contract periods
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

            $contractPeriods = $this->contractPeriodService->searchContractPeriods($query);

            return response()->json([
                'success' => true,
                'data' => $contractPeriods->map(fn($period) => $period->toArray()),
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
     * Get contract periods by payment status
     */
    public function byPaymentStatus(Request $request, string $paymentStatus): JsonResponse
    {
        try {
            $validStatuses = array_keys($this->contractPeriodService->getPaymentStatusOptions());
            
            if (!in_array($paymentStatus, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment status'
                ], 422);
            }

            $contractPeriods = $this->contractPeriodService->getContractPeriodsByPaymentStatus($paymentStatus);

            return response()->json([
                'success' => true,
                'data' => $contractPeriods->map(fn($period) => $period->toArray()),
                'payment_status' => $paymentStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contract periods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contract periods by budget type
     */
    public function byBudgetType(Request $request, string $budgetType): JsonResponse
    {
        try {
            if (!in_array($budgetType, ['AO', 'RI'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid budget type'
                ], 422);
            }

            $contractPeriods = $this->contractPeriodService->getContractPeriodsByBudgetType($budgetType);

            return response()->json([
                'success' => true,
                'data' => $contractPeriods->map(fn($period) => $period->toArray()),
                'budget_type' => $budgetType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contract periods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active contract periods
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $contractPeriods = $this->contractPeriodService->getActiveContractPeriods();

            return response()->json([
                'success' => true,
                'data' => $contractPeriods->map(fn($period) => $period->toArray())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active contract periods: ' . $e->getMessage()
            ], 500);
        }
    }
}
