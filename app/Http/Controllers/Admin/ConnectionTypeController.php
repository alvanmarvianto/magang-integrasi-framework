<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConnectionTypeService;
use App\Http\Requests\Admin\StoreConnectionTypeRequest;
use App\Http\Requests\Admin\UpdateConnectionTypeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class ConnectionTypeController extends Controller
{
    public function __construct(
        private ConnectionTypeService $connectionTypeService
    ) {}

    public function index(): Response
    {
        $connectionTypes = $this->connectionTypeService->getAllConnectionTypes();
        
        $transformedConnectionTypes = $connectionTypes->map(function ($connectionType) {
            return [
                'id' => $connectionType->connection_type_id,
                'name' => $connectionType->type_name,
                'color' => $connectionType->color ?? '#000000',
            ];
        });
        
        return Inertia::render('Admin/ConnectionTypes', [
            'connectionTypes' => $transformedConnectionTypes
        ]);
    }

    public function store(StoreConnectionTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->connectionTypeService->createConnectionType([
                'type_name' => $validated['name'],
                'color' => $validated['color'],
            ]);
            
            return back()->with('success', 'Connection type created successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create connection type: ' . $e->getMessage());
        }
    }

    public function update(UpdateConnectionTypeRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->connectionTypeService->updateConnectionType($id, [
                'type_name' => $validated['name'],
                'color' => $validated['color'],
            ]);
            
            return back()->with('success', 'Connection type updated successfully and diagram layouts refreshed');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update connection type: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->connectionTypeService->deleteConnectionType($id);
            return back()->with('success', 'Connection type deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete connection type: ' . $e->getMessage());
        }
    }

    /**
     * Check if connection type is being used by integrations
     */
    public function checkUsage(int $id)
    {
        try {
            $usage = $this->connectionTypeService->checkConnectionTypeUsage($id);
            return response()->json($usage);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to check usage'], 500);
        }
    }
}
