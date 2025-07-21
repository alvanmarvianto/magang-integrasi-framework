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
use App\Http\Controllers\Traits\HandlesTechnologyEnums;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use HandlesTechnologyEnums;

    protected $diagramController;

    public function __construct(DiagramController $diagramController)
    {
        $this->diagramController = $diagramController;
    }

    public function index()
    {
        return Inertia::render('Admin/Index');
    }

    public function apps(Request $request)
    {
        $query = App::with('stream')->orderBy('app_name');

        // Apply search filter if provided
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where('app_name', 'like', '%' . $searchTerm . '%');
        }

        $apps = $query->paginate(10);

        return Inertia::render('Admin/Apps', [
            'apps' => $apps,
            'streams' => Stream::all(),
        ]);
    }

    public function technology()
    {
        $enums = $this->getTechnologyEnums();
        
        return Inertia::render('Admin/Technology', [
            'enums' => $enums,
        ]);
    }

    public function storeEnumValue(Request $request, string $type)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Get current enum values
            $tableName = $this->getTableName($type);
            $currentValues = $this->getEnumValues($tableName);
            
            // Check if value already exists
            if (in_array($request->name, $currentValues)) {
                return back()->with('error', 'Nilai enum sudah ada');
            }
            
            // Add new value to enum
            $newValues = array_merge($currentValues, [$request->name]);

            // Update the enum column
            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return back()->with('success', 'Nilai enum berhasil ditambahkan');
        } catch (\Exception $e) {
            \Log::error('Error adding enum: ' . $e->getMessage());
            return back()->with('error', 'Gagal menambahkan nilai enum: ' . $e->getMessage());
        }
    }

    protected function getAppsUsingEnum(string $tableName, string $value): array
    {
        $apps = DB::table($tableName)
            ->join('apps', $tableName . '.app_id', '=', 'apps.app_id')
            ->where($tableName . '.name', $value)
            ->select('apps.app_id', 'apps.app_name')
            ->get();

        return $apps->map(function($app) {
            return [
                'id' => $app->app_id,
                'name' => $app->app_name,
                'edit_url' => route('admin.apps.edit', $app->app_id)
            ];
        })->all();
    }

    public function checkEnumUsage(string $type, string $value)
    {
        $tableName = $this->getTableName($type);
        $apps = $this->getAppsUsingEnum($tableName, $value);

        return response()->json([
            'is_used' => count($apps) > 0,
            'apps' => $apps
        ]);
    }

    public function updateEnumValue(Request $request, string $type, string $oldValue)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tableName = $this->getTableName($type);
        $currentValues = $this->getEnumValues($tableName);
        
        // Check if new value already exists
        if ($request->name !== $oldValue && in_array($request->name, $currentValues)) {
            return back()->with('error', 'Nilai enum sudah ada');
        }

        // Check if value is in use
        $apps = $this->getAppsUsingEnum($tableName, $oldValue);
        if (count($apps) > 0) {
            return back()->with('error', 'Nilai enum sedang digunakan');
        }

        try {
            // Replace the value in enum
            $newValues = array_map(
                fn($val) => $val === $oldValue ? $request->name : $val,
                $currentValues
            );

            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return back()->with('success', 'Nilai enum berhasil diperbarui');
        } catch (\Exception $e) {
            \Log::error('Error updating enum: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui nilai enum: ' . $e->getMessage());
        }
    }

    public function deleteEnumValue(string $type, string $value)
    {
        try {
            $tableName = $this->getTableName($type);
            
            // Check if value is in use
            $isInUse = DB::table($tableName)->where('name', $value)->exists();
            if ($isInUse) {
                return back()->with('error', 'Nilai enum sedang digunakan dan tidak dapat dihapus');
            }

            $currentValues = $this->getEnumValues($tableName);
            
            // Remove value from enum
            $newValues = array_values(array_filter($currentValues, fn($val) => $val !== $value));
            
            if (count($newValues) === 0) {
                return back()->with('error', 'Tidak dapat menghapus nilai enum terakhir');
            }

            // Update the enum column
            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return back()->with('success', 'Nilai enum berhasil dihapus');

        } catch (\Exception $e) {
            \Log::error('Error in deleteEnumValue: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menghapus nilai enum');
        }
    }

    protected function getTechnologyModel(string $type): string
    {
        return match ($type) {
            'vendors' => Vendor::class,
            'operatingSystems' => OperatingSystem::class,
            'databases' => Database::class,
            'languages' => ProgrammingLanguage::class,
            'frameworks' => Framework::class,
            'middlewares' => Middleware::class,
            'thirdParties' => ThirdParty::class,
            'platforms' => Platform::class,
            default => throw new \InvalidArgumentException('Invalid technology type'),
        };
    }

    protected function getTableName(string $type): string
    {
        return match ($type) {
            'vendors' => 'technology_vendors',
            'operatingSystems' => 'technology_operating_systems',
            'databases' => 'technology_databases',
            'languages' => 'technology_programming_languages',
            'frameworks' => 'technology_frameworks',
            'middlewares' => 'technology_middlewares',
            'thirdParties' => 'technology_third_parties',
            'platforms' => 'technology_platforms',
            default => throw new \InvalidArgumentException('Invalid technology type'),
        };
    }

    public function create()
    {
        return Inertia::render('Admin/AppForm', [
            'streams' => Stream::all(),
            'appTypes' => ['cots', 'inhouse', 'outsource'],
            'stratifications' => ['strategis', 'kritikal', 'umum'],
            'vendors' => $this->getEnumValues('technology_vendors'),
            'operatingSystems' => $this->getEnumValues('technology_operating_systems'),
            'databases' => $this->getEnumValues('technology_databases'),
            'languages' => $this->getEnumValues('technology_programming_languages'),
            'frameworks' => $this->getEnumValues('technology_frameworks'),
            'middlewares' => $this->getEnumValues('technology_middlewares'),
            'thirdParties' => $this->getEnumValues('technology_third_parties'),
            'platforms' => $this->getEnumValues('technology_platforms'),
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

        return redirect()->route('admin.apps')->with('success', 'Application created successfully');
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

        // Get all available enum values
        $enumValues = [
            'vendors' => $this->getEnumValues('technology_vendors'),
            'operatingSystems' => $this->getEnumValues('technology_operating_systems'),
            'databases' => $this->getEnumValues('technology_databases'),
            'languages' => $this->getEnumValues('technology_programming_languages'),
            'frameworks' => $this->getEnumValues('technology_frameworks'),
            'middlewares' => $this->getEnumValues('technology_middlewares'),
            'thirdParties' => $this->getEnumValues('technology_third_parties'),
            'platforms' => $this->getEnumValues('technology_platforms'),
        ];

        return Inertia::render('Admin/AppForm', [
            'app' => $app,
            'streams' => Stream::all(),
            'appTypes' => ['cots', 'inhouse', 'outsource'],
            'stratifications' => ['strategis', 'kritikal', 'umum'],
            'vendors' => $enumValues['vendors'],
            'operatingSystems' => $enumValues['operatingSystems'],
            'databases' => $enumValues['databases'],
            'languages' => $enumValues['languages'],
            'frameworks' => $enumValues['frameworks'],
            'middlewares' => $enumValues['middlewares'],
            'thirdParties' => $enumValues['thirdParties'],
            'platforms' => $enumValues['platforms'],
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

        return redirect()->route('admin.apps')->with('success', 'Application updated successfully');
    }

    public function destroy($appId)
    {
        $app = App::findOrFail($appId);
        $app->delete();

        return redirect()->route('admin.apps')->with('success', 'Application deleted successfully');
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

        return Inertia::render('Admin/Diagram', [
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
