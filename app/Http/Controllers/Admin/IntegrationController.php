<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppIntegration;
use App\Services\IntegrationService;
use App\Http\Requests\Admin\StoreIntegrationRequest;
use App\Http\Requests\Admin\UpdateIntegrationRequest;
use App\Http\Requests\Admin\BulkCreateIntegrationsRequest;
use App\Http\Requests\Admin\CheckIntegrationExistsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    protected IntegrationService $integrationService;

    public function __construct(IntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Display paginated list of integrations
     */
    public function index(Request $request): Response
    {
        $paginationData = $this->integrationService->getPaginatedIntegrations(
            search: $request->get('search'),
            perPage: $request->get('per_page', 10),
            sortBy: $request->get('sort_by', 'source_app_name'),
            sortDesc: $request->boolean('sort_desc', false)
        );

    return Inertia::render('Admin/Integrations', $paginationData);
    }

    /**
     * Show form for creating new integration
     */
    public function create(): Response
    {
        $formData = $this->integrationService->getFormData();
        
        return Inertia::render('Admin/IntegrationForm', $formData);
    }

    /**
     * Store newly created integration
     */
    public function store(StoreIntegrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $integrationDTO = $this->integrationService->createIntegration($validated);
            
            return redirect()
                ->route('admin.integrations.index')
                ->with('success', 'Integration created successfully');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to create integration: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show form for editing integration
     */
    public function edit(int $id): Response
    {
        $formData = $this->integrationService->getFormData($id);
        
        if (!$formData['integration']) {
            abort(404, 'Integration not found');
        }
        
        return Inertia::render('Admin/IntegrationForm', $formData);
    }

    /**
     * Update integration
     */
    public function update(UpdateIntegrationRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $integration = AppIntegration::findOrFail($id);
        
        try {
            $integrationDTO = $this->integrationService->updateIntegration($integration, $validated);
            
            return redirect()
                ->route('admin.integrations.index')
                ->with('success', 'Integration updated successfully');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to update integration: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove integration
     */
    public function destroy(int $id): RedirectResponse
    {
        $integration = AppIntegration::findOrFail($id);
        
        try {
            if (!$this->integrationService->deleteIntegration($integration)) {
                throw new \Exception('Failed to delete integration');
            }
            
            return back()->with('success', 'Integration deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete integration: ' . $e->getMessage()]);
        }
    }

    /**
     * Display integration statistics
     */
    public function statistics(): Response
    {
        $statistics = $this->integrationService->getIntegrationStatistics();
        $connectionTypes = $this->integrationService->getConnectionTypes();
        
        return Inertia::render('Admin/IntegrationStatistics', [
            'statistics' => $statistics,
            'connection_types' => $connectionTypes,
        ]);
    }

    /**
     * Remove duplicate integrations
     */
    public function removeDuplicates(): RedirectResponse
    {
        try {
            $removedCount = $this->integrationService->removeDuplicateIntegrations();
            
            if ($removedCount === 0) {
                return back()->with('info', 'No duplicate integrations found');
            }
            
            return back()->with('success', "Removed {$removedCount} duplicate integration(s)");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to remove duplicates: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk create integrations
     */
    public function bulkCreate(BulkCreateIntegrationsRequest $request): RedirectResponse
    {
        try {
            $result = $this->integrationService->bulkCreateIntegrations($request->validated('integrations'));
            
            $successMessage = "Created {$result['success_count']} integration(s)";
            if ($result['error_count'] > 0) {
                $successMessage .= ", {$result['error_count']} failed";
            }
            
            return redirect()
                ->route('admin.integrations.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create integrations: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if integration exists between apps (AJAX endpoint)
     */
    public function checkExists(CheckIntegrationExistsRequest $request)
    {
        $validated = $request->validated();

        $exists = $this->integrationService->integrationExistsBetweenApps(
            $validated['source_app_id'],
            $validated['target_app_id']
        );
        
        return response()->json([
            'exists' => $exists,
        ]);
    }
}
