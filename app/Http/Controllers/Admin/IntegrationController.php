<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppIntegration;
use App\Services\IntegrationService;
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

    public function index(Request $request): Response
    {
        $data = $this->integrationService->getPaginatedIntegrations(
            $request->get('search'),
            10,
            $request->get('sort_by', 'source_app_name'),
            $request->boolean('sort_desc', false)
        );

        return Inertia::render('Admin/Integrations', $data);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/IntegrationForm', $this->integrationService->getFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_app_id' => 'required|exists:apps,app_id',
            'target_app_id' => 'required|exists:apps,app_id|different:source_app_id',
            'connection_type_id' => 'required|exists:connectiontypes,connection_type_id',
            'inbound' => 'nullable|string|max:1000',
            'outbound' => 'nullable|string|max:1000',
            'connection_endpoint' => 'nullable|url|max:255',
            'direction' => 'required|in:one_way,both_ways',
        ]);

        try {
            $this->integrationService->createIntegration($validated);     
            return redirect()->route('admin.integrations.index')
                ->with('success', 'Integration berhasil dibuat');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/IntegrationForm', $this->integrationService->getFormData($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'source_app_id' => 'required|exists:apps,app_id',
            'target_app_id' => 'required|exists:apps,app_id|different:source_app_id',
            'connection_type_id' => 'required|exists:connectiontypes,connection_type_id',
            'inbound' => 'nullable|string|max:1000',
            'outbound' => 'nullable|string|max:1000',
            'connection_endpoint' => 'nullable|url|max:255',
            'direction' => 'required|in:one_way,both_ways',
        ]);

        $integration = AppIntegration::findOrFail($id);
        try {
            $this->integrationService->updateIntegration($integration, $validated);
            return redirect()->route('admin.integrations.index');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $integration = AppIntegration::findOrFail($id);
        try {
            $this->integrationService->deleteIntegration($integration);
            return back();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function switchSourceTarget(int $id): RedirectResponse
    {
        $integration = AppIntegration::findOrFail($id);
        try {
            $integration->switchSourceAndTarget();
            return back()->with('success', 'Source and target switched successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
