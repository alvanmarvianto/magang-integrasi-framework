<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\AppService;
use App\Services\AppLayoutService;
use App\Http\Requests\Admin\StoreAppRequest;
use App\Http\Requests\Admin\UpdateAppRequest;
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
}
