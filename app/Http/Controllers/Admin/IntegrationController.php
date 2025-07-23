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
        ]);

        try {
            $this->integrationService->createIntegration($validated);
            return redirect()->route('admin.integrations')->with('success', 'Integration created successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/IntegrationForm', $this->integrationService->getFormData($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'connection_type_id' => 'required|exists:connectiontypes,connection_type_id',
        ]);

        $integration = AppIntegration::findOrFail($id);
        try {
            $this->integrationService->updateIntegration($integration, $validated);
            return redirect()->route('admin.integrations')->with('success', 'Integration updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $integration = AppIntegration::findOrFail($id);
        try {
            $this->integrationService->deleteIntegration($integration);
            return back()->with('success', 'Integration deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
