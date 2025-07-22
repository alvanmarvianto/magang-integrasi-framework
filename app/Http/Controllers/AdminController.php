<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Services\AppService;
use App\Services\TechnologyService;
use App\Http\Requests\Admin\StoreAppRequest;
use App\Http\Requests\Admin\UpdateEnumRequest;
use App\Http\Controllers\Traits\HandlesTechnologyEnums;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    use HandlesTechnologyEnums;

    protected DiagramController $diagramController;
    protected AppService $appService;
    protected TechnologyService $technologyService;

    public function __construct(
        DiagramController $diagramController,
        AppService $appService,
        TechnologyService $technologyService
    ) {
        $this->diagramController = $diagramController;
        $this->appService = $appService;
        $this->technologyService = $technologyService;
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Index');
    }

    public function apps(Request $request): Response
    {
        $data = $this->appService->getPaginatedApps(
            $request->get('search'),
            10
        );
        return Inertia::render('Admin/Apps', $data);
    }

    public function technology(): Response
    {
        return Inertia::render('Admin/Technology', [
            'enums' => $this->getTechnologyEnums(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AppForm', $this->appService->getFormData());
    }

    public function store(StoreAppRequest $request): RedirectResponse
    {
        $this->appService->createApp($request->validated());
        return redirect()->route('admin.apps')->with('success', 'Application created successfully');
    }

    public function edit(int $appId): Response
    {
        return Inertia::render('Admin/AppForm', $this->appService->getFormData($appId));
    }

    public function update(StoreAppRequest $request, App $app): RedirectResponse
    {
        $this->appService->updateApp($app, $request->validated());
        return redirect()->route('admin.apps')->with('success', 'Application updated successfully');
    }

    public function destroy(App $app): RedirectResponse
    {
        $this->appService->deleteApp($app);
        return redirect()->route('admin.apps')->with('success', 'Application deleted successfully');
    }

    public function showStream(Request $request, string $streamName): Response
    {
        if (!$this->diagramController->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        $data = $this->diagramController->getVueFlowAdminData($streamName);

        return Inertia::render('Admin/Diagram', [
            'streamName' => $streamName,
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
            'savedLayout' => $data['savedLayout'],
            'allowedStreams' => $this->diagramController->getAllowedStreams(),
        ]);
    }

    public function saveLayout(Request $request, string $streamName)
    {
        return $this->diagramController->saveLayout($request, $streamName);
    }

    public function storeEnumValue(UpdateEnumRequest $request, string $type): RedirectResponse
    {
        try {
            $this->technologyService->storeEnumValue($type, $request->name);
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
            $this->technologyService->updateEnumValue($type, $oldValue, $request->name);
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
