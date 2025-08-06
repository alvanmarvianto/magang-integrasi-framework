<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\AppService;
use App\Http\Requests\Admin\StoreAppRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AppController extends Controller
{
    protected AppService $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
    }

    /**
     * Display paginated list of applications
     */
    public function index(Request $request): Response
    {
        $paginationData = $this->appService->getPaginatedApps(
            search: $request->get('search'),
            perPage: $request->get('per_page', 10),
            sortBy: $request->get('sort_by', 'app_name'),
            sortDesc: $request->boolean('sort_desc', false)
        );

        // Add statistics to the response
        $statistics = $this->appService->getAppStatistics();
        $paginationData['statistics'] = $statistics;

        return Inertia::render('Admin/Apps', $paginationData);
    }

    /**
     * Show form for creating new application
     */
    public function create(): Response
    {
        $formData = $this->appService->getFormData();
        
        return Inertia::render('Admin/AppForm', $formData);
    }

    /**
     * Store newly created application
     */
    public function store(StoreAppRequest $request): RedirectResponse
    {
        try {
            $appDTO = $this->appService->createApp($request->validated());
            
            return redirect()
                ->route('admin.apps.index')
                ->with('success', "Application '{$appDTO->appName}' created successfully");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create application: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form for editing application
     */
    public function edit(int $appId): Response
    {
        $formData = $this->appService->getFormData($appId);
        
        if (!$formData['app']) {
            abort(404, 'Application not found');
        }
        
        return Inertia::render('Admin/AppForm', $formData);
    }

    /**
     * Update application
     */
    public function update(StoreAppRequest $request, App $app): RedirectResponse
    {
        try {
            $appDTO = $this->appService->updateApp($app, $request->validated());
            
            return redirect()
                ->route('admin.apps.index')
                ->with('success', "Application '{$appDTO->appName}' updated successfully");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update application: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove application
     */
    public function destroy(App $app): RedirectResponse
    {
        try {
            $appName = $app->app_name;
            
            if (!$this->appService->deleteApp($app)) {
                throw new \Exception('Failed to delete application');
            }
            
            return redirect()
                ->route('admin.apps.index')
                ->with('success', "Application '{$appName}' deleted successfully");
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete application: ' . $e->getMessage()]);
        }
    }

    /**
     * Display application statistics
     */
    public function statistics(): Response
    {
        $statistics = $this->appService->getAppStatistics();
        $appsWithIntegrations = $this->appService->getAppsWithIntegrationCounts();
        
        return Inertia::render('Admin/AppStatistics', [
            'statistics' => $statistics,
            'apps_with_integrations' => $appsWithIntegrations,
        ]);
    }

    /**
     * Bulk update applications
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'apps' => 'required|array|min:1',
            'apps.*.app_id' => 'required|exists:apps,app_id',
            'apps.*.app_name' => 'required|string|max:255',
            'apps.*.description' => 'nullable|string',
            'apps.*.stream_id' => 'required|exists:streams,stream_id',
            'apps.*.app_type' => 'required|in:cots,inhouse,outsource',
            'apps.*.stratification' => 'required|in:strategis,kritikal,umum',
        ]);

        try {
            $success = $this->appService->bulkUpdateApps($request->input('apps'));
            
            if (!$success) {
                throw new \Exception('No applications were updated');
            }
            
            return redirect()
                ->route('admin.apps.index')
                ->with('success', 'Applications updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update applications: ' . $e->getMessage()]);
        }
    }

    /**
     * Search applications by name (AJAX endpoint)
     */
    public function search(Request $request)
    {
        $request->validate([
            'term' => 'required|string|min:1|max:255',
        ]);

        $searchResults = $this->appService->searchAppsByName($request->input('term'));
        
        return response()->json([
            'results' => $searchResults,
            'total' => count($searchResults),
        ]);
    }

    /**
     * Check if application name exists (AJAX endpoint)
     */
    public function checkName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'exclude_id' => 'nullable|integer|exists:apps,app_id',
        ]);

        $exists = $this->appService->appExistsByName($request->input('name'));
        
        // If we're editing an existing app, exclude it from the check
        if ($exists && $request->has('exclude_id')) {
            // Additional logic could be added here to exclude the current app
            // This would require modifying the service method to accept an exclude parameter
        }
        
        return response()->json([
            'exists' => $exists,
        ]);
    }
}
