<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTechnologyEnums;
use App\Services\TechnologyService;
use App\Http\Requests\Admin\UpdateEnumRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class TechnologyController extends Controller
{
    use HandlesTechnologyEnums;

    protected TechnologyService $technologyService;

    public function __construct(TechnologyService $technologyService)
    {
        $this->technologyService = $technologyService;
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Technology', [
            'enums' => $this->getTechnologyEnums(),
        ]);
    }

    public function storeEnumValue(UpdateEnumRequest $request, string $type): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $this->technologyService->storeEnumValue($type, $validated['name']);
            return back()->with('success', 'Nilai enum berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function checkEnumUsage(string $type, string $value): JsonResponse
    {
        $tableName = $this->technologyService->getTableName($type);
        $apps = $this->technologyService->getAppsUsingEnum($tableName, $value);

        return response()->json([
            'is_used' => count($apps) > 0,
            'apps' => $apps
        ]);
    }

    public function updateEnumValue(UpdateEnumRequest $request, string $type, string $oldValue): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $this->technologyService->updateEnumValue($type, $oldValue, $validated['name']);
            return back()->with('success', 'Nilai enum berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteEnumValue(string $type, string $value): RedirectResponse
    {
        try {
            $this->technologyService->deleteEnumValue($type, $value);
            return back()->with('success', 'Nilai enum berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
