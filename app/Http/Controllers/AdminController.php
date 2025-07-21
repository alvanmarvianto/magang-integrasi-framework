<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Stream;
use App\Models\Vendor;
use App\Models\Database;
use App\Models\Framework;
use App\Models\Middleware;
use App\Models\Platform;
use App\Models\OperatingSystem;
use App\Models\ProgrammingLanguage;
use App\Models\ThirdParty;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    protected $diagramController;

    public function __construct(DiagramController $diagramController)
    {
        $this->diagramController = $diagramController;
    }

    public function index(Request $request)
    {
        $query = App::with('stream')->orderBy('app_name');

        // Apply search filter if provided
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where('app_name', 'like', '%' . $searchTerm . '%');
        }

        $apps = $query->paginate(10);

        return Inertia::render('Admin/Index', [
            'apps' => $apps,
            'streams' => Stream::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/AppForm', [
            'streams' => Stream::all(),
            'appTypes' => ['cots', 'inhouse', 'outsource'],
            'stratifications' => ['strategis', 'kritikal', 'umum'],
            'vendors' => Vendor::pluck('name')->unique(),
            'operatingSystems' => OperatingSystem::pluck('name')->unique(),
            'databases' => Database::pluck('name')->unique(),
            'languages' => ProgrammingLanguage::pluck('name')->unique(),
            'frameworks' => Framework::pluck('name')->unique(),
            'middlewares' => Middleware::pluck('name')->unique(),
            'thirdParties' => ThirdParty::pluck('name')->unique(),
            'platforms' => Platform::pluck('name')->unique(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stream_id' => 'required|exists:streams,stream_id',
            'app_type' => 'required|in:cots,inhouse,outsource',
            'stratification' => 'required|in:strategis,kritikal,umum',
            'vendors' => 'array',
            'vendors.*.name' => 'required|string',
            'vendors.*.version' => 'nullable|string',
            'operating_systems' => 'array',
            'operating_systems.*.name' => 'required|string',
            'operating_systems.*.version' => 'nullable|string',
            'databases' => 'array',
            'databases.*.name' => 'required|string',
            'databases.*.version' => 'nullable|string',
            'languages' => 'array',
            'languages.*.name' => 'required|string',
            'languages.*.version' => 'nullable|string',
            'frameworks' => 'array',
            'frameworks.*.name' => 'required|string',
            'frameworks.*.version' => 'nullable|string',
            'middlewares' => 'array',
            'middlewares.*.name' => 'required|string',
            'middlewares.*.version' => 'nullable|string',
            'third_parties' => 'array',
            'third_parties.*.name' => 'required|string',
            'third_parties.*.version' => 'nullable|string',
            'platforms' => 'array',
            'platforms.*.name' => 'required|string',
            'platforms.*.version' => 'nullable|string',
        ]);

        $app = App::create([
            'app_name' => $validated['app_name'],
            'description' => $validated['description'],
            'stream_id' => $validated['stream_id'],
            'app_type' => $validated['app_type'],
            'stratification' => $validated['stratification'],
        ]);

        // Save technology components
        $this->saveTechnologyComponents($app, $validated);

        return redirect()->route('admin.index')->with('success', 'Application created successfully');
    }

    public function edit($appId)
    {
        $app = App::with([
            'vendors',
            'operatingSystems',
            'databases',
            'programmingLanguages',
            'frameworks',
            'middlewares',
            'thirdParties',
            'platforms',
        ])->findOrFail($appId);

        return Inertia::render('Admin/AppForm', [
            'app' => $app,
            'streams' => Stream::all(),
            'appTypes' => ['cots', 'inhouse', 'outsource'],
            'stratifications' => ['strategis', 'kritikal', 'umum'],
            'vendors' => Vendor::pluck('name')->unique(),
            'operatingSystems' => OperatingSystem::pluck('name')->unique(),
            'databases' => Database::pluck('name')->unique(),
            'languages' => ProgrammingLanguage::pluck('name')->unique(),
            'frameworks' => Framework::pluck('name')->unique(),
            'middlewares' => Middleware::pluck('name')->unique(),
            'thirdParties' => ThirdParty::pluck('name')->unique(),
            'platforms' => Platform::pluck('name')->unique(),
        ]);
    }

    public function update(Request $request, $appId)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stream_id' => 'required|exists:streams,stream_id',
            'app_type' => 'required|in:cots,inhouse,outsource',
            'stratification' => 'required|in:strategis,kritikal,umum',
            'vendors' => 'array',
            'vendors.*.name' => 'required|string',
            'vendors.*.version' => 'nullable|string',
            'operating_systems' => 'array',
            'operating_systems.*.name' => 'required|string',
            'operating_systems.*.version' => 'nullable|string',
            'databases' => 'array',
            'databases.*.name' => 'required|string',
            'databases.*.version' => 'nullable|string',
            'languages' => 'array',
            'languages.*.name' => 'required|string',
            'languages.*.version' => 'nullable|string',
            'frameworks' => 'array',
            'frameworks.*.name' => 'required|string',
            'frameworks.*.version' => 'nullable|string',
            'middlewares' => 'array',
            'middlewares.*.name' => 'required|string',
            'middlewares.*.version' => 'nullable|string',
            'third_parties' => 'array',
            'third_parties.*.name' => 'required|string',
            'third_parties.*.version' => 'nullable|string',
            'platforms' => 'array',
            'platforms.*.name' => 'required|string',
            'platforms.*.version' => 'nullable|string',
        ]);

        $app = App::findOrFail($appId);
        $app->update([
            'app_name' => $validated['app_name'],
            'description' => $validated['description'],
            'stream_id' => $validated['stream_id'],
            'app_type' => $validated['app_type'],
            'stratification' => $validated['stratification'],
        ]);

        // Update technology components
        $this->saveTechnologyComponents($app, $validated);

        return redirect()->route('admin.index')->with('success', 'Application updated successfully');
    }

    public function destroy($appId)
    {
        $app = App::findOrFail($appId);
        $app->delete();

        return redirect()->route('admin.index')->with('success', 'Application deleted successfully');
    }

    protected function saveTechnologyComponents(App $app, array $data)
    {
        // Helper function to save components
        $saveComponents = function($items, $relation) use ($app) {
            $app->$relation()->delete(); // Clear existing
            foreach ($items as $item) {
                $app->$relation()->create([
                    'name' => $item['name'],
                    'version' => $item['version'] ?? null,
                ]);
            }
        };

        // Save each type of component
        if (!empty($data['vendors'])) {
            $saveComponents($data['vendors'], 'vendors');
        }
        if (!empty($data['operating_systems'])) {
            $saveComponents($data['operating_systems'], 'operatingSystems');
        }
        if (!empty($data['databases'])) {
            $saveComponents($data['databases'], 'databases');
        }
        if (!empty($data['languages'])) {
            $saveComponents($data['languages'], 'programmingLanguages');
        }
        if (!empty($data['frameworks'])) {
            $saveComponents($data['frameworks'], 'frameworks');
        }
        if (!empty($data['middlewares'])) {
            $saveComponents($data['middlewares'], 'middlewares');
        }
        if (!empty($data['third_parties'])) {
            $saveComponents($data['third_parties'], 'thirdParties');
        }
        if (!empty($data['platforms'])) {
            $saveComponents($data['platforms'], 'platforms');
        }
    }

    /**
     * Show admin page for specific stream
     */
    public function showStream(Request $request, string $streamName)
    {
        if (!$this->diagramController->validateStreamName($streamName)) {
            abort(404, 'Stream not found');
        }

        $data = $this->diagramController->getVueFlowAdminData($streamName);

        return Inertia::render('AdminVueFlowStream', [
            'streamName' => $streamName,
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
            'savedLayout' => $data['savedLayout'],
            'allowedStreams' => $this->diagramController->getAllowedStreams(),
        ]);
    }

    /**
     * Save layout configuration
     */
    public function saveLayout(Request $request, string $streamName)
    {
        return $this->diagramController->saveLayout($request, $streamName);
    }
}
