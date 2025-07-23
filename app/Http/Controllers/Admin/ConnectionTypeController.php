<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectionType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class ConnectionTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ConnectionTypes', [
            'connectionTypes' => ConnectionType::withCount('appIntegrations')->get()
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type_name' => 'required|string|max:255|unique:connectiontypes,type_name',
            'description' => 'nullable|string',
        ]);

        try {
            ConnectionType::create($validated);
            return back()->with('success', 'Connection type created successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'type_name' => 'required|string|max:255|unique:connectiontypes,type_name,' . $id . ',connection_type_id',
            'description' => 'nullable|string',
        ]);

        try {
            $connectionType = ConnectionType::findOrFail($id);
            $connectionType->update($validated);
            return back()->with('success', 'Connection type updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $connectionType = ConnectionType::findOrFail($id);
            
            // Check if connection type is being used
            if ($connectionType->appIntegrations()->count() > 0) {
                return back()->with('error', 'Cannot delete connection type that is being used by integrations');
            }
            
            $connectionType->delete();
            return back()->with('success', 'Connection type deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
