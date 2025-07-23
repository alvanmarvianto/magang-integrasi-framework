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

    public function index(Request $request): Response
    {
        $data = $this->appService->getPaginatedApps(
            $request->get('search'),
            10
        );
        return Inertia::render('Admin/Apps', $data);
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
}
