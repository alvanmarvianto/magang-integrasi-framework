<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\AppService;
use App\Services\AppLayoutService;
use App\Http\Requests\Admin\StoreAppRequest;
use App\Http\Requests\Admin\UpdateAppRequest;
use App\Http\Requests\Admin\BulkUpdateAppsRequest;
use App\Http\Requests\Admin\SearchAppsRequest;
use App\Http\Requests\Admin\CheckAppNameRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AppController extends Controller
{
    protected AppService $appService;
    protected AppLayoutService $appLayoutService;

    public function __construct(AppService $appService, AppLayoutService $appLayoutService)
    {
        $this->appService = $appService;
        $this->appLayoutService = $appLayoutService;
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
    public function update(UpdateAppRequest $request, App $app): RedirectResponse
    {
        try {
            $appDTO = $this->appService->updateApp($app, $request->validated());
            
            // Auto-sync app layout colors if app has a layout (stream may have changed)
            $this->appLayoutService->autoSyncColorsAfterAppOperation($app->app_id);
            
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
    public function bulkUpdate(BulkUpdateAppsRequest $request): RedirectResponse
    {
        try {
            $success = $this->appService->bulkUpdateApps($request->validated('apps'));
            
            if (!$success) {
                throw new \Exception('No applications were updated');
            }
            
            // Auto-sync app layout colors for all updated apps that have layouts
            $appsData = $request->validated('apps');
            $appIds = array_column($appsData, 'app_id');
            $colorsSyncedTotal = $this->appLayoutService->autoSyncColorsAfterBulkOperation($appIds);
            
            $message = 'Applications updated successfully';
            if ($colorsSyncedTotal > 0) {
                $message .= " and synchronized {$colorsSyncedTotal} app layout node colors";
            }
            
            return redirect()
                ->route('admin.apps.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update applications: ' . $e->getMessage()]);
        }
    }

    /**
     * Search applications by name (AJAX endpoint)
     */
    public function search(SearchAppsRequest $request)
    {
        $searchResults = $this->appService->searchAppsByName($request->validated('term'));
        
        return response()->json([
            'results' => $searchResults,
            'total' => count($searchResults),
        ]);
    }

    /**
     * Check if application name exists (AJAX endpoint)
     */
    public function checkName(CheckAppNameRequest $request)
    {
        $validated = $request->validated();

        $exists = $this->appService->appExistsByName($validated['name']);
        
        // If we're editing an existing app, exclude it from the check
        if ($exists && isset($validated['exclude_id'])) {
            // Additional logic could be added here to exclude the current app
            // This would require modifying the service method to accept an exclude parameter
        }
        
        return response()->json([
            'exists' => $exists,
        ]);
    }
}
