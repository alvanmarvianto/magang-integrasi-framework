<?php

namespace App\Services;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AppService
{
    protected AppRepositoryInterface $appRepository;
    protected TechnologyService $technologyService;
    protected StreamService $streamService;
    protected TechnologyRepositoryInterface $technologyRepository;
    protected StreamLayoutRepositoryInterface $streamLayoutRepository;
    protected IntegrationRepositoryInterface $integrationRepository;

    public function __construct(
        AppRepositoryInterface $appRepository,
        TechnologyService $technologyService,
        StreamService $streamService,
        TechnologyRepositoryInterface $technologyRepository,
    StreamLayoutRepositoryInterface $streamLayoutRepository,
    IntegrationRepositoryInterface $integrationRepository
    ) {
        $this->appRepository = $appRepository;
        $this->technologyService = $technologyService;
        $this->streamService = $streamService;
        $this->technologyRepository = $technologyRepository;
        $this->streamLayoutRepository = $streamLayoutRepository;
    $this->integrationRepository = $integrationRepository;
    }

    /**
     * Get paginated list of apps with optional search and sorting
     */
    public function getPaginatedApps(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): array {
        $paginatedApps = $this->appRepository->getPaginatedApps($search, $perPage, $sortBy, $sortDesc);
        
        // Convert the paginated app models to DTOs
        $appDTOs = $paginatedApps->map(function ($app) {
            return AppDTO::fromModel($app);
        });
        
        // Create pagination data structure that matches Laravel's default pagination
        $paginationData = [
            'data' => $appDTOs->map(fn($dto) => $dto->toArray())->all(),
            'meta' => [
                'current_page' => $paginatedApps->currentPage(),
                'last_page' => $paginatedApps->lastPage(),
                'per_page' => $paginatedApps->perPage(),
                'total' => $paginatedApps->total(),
                'from' => $paginatedApps->firstItem(),
                'to' => $paginatedApps->lastItem(),
                'links' => $this->buildPaginationLinks($paginatedApps),
            ],
        ];
        
        return [
            'apps' => $paginationData,
            'streams' => $this->streamService->getAllStreams(),
        ];
    }

    /**
     * Get app form data for create/edit operations
     */
    public function getFormData(?int $appId = null): array
    {
        // For edit operations, use fresh data to bypass cache
        $appDTO = $appId ? $this->appRepository->findAsDTOFresh($appId) : null;
        
        $technologyOptions = $this->getTechnologyOptions();
        $integrationOptions = $this->integrationRepository->getIntegrationOptions();

        // Prepare app payload, and enrich with integration_functions for edit mode
        $appPayload = $appDTO ? $appDTO->toArray() : null;
        if ($appId && $appPayload !== null) {
            // Fetch functions and group integration_ids per function_name
            $rows = DB::table('appintegration_functions')
                ->where('app_id', $appId)
                ->select('function_name', 'integration_id')
                ->orderBy('function_name')
                ->get();

            if ($rows->isNotEmpty()) {
                $grouped = $rows
                    ->groupBy('function_name')
                    ->map(function ($items, $fname) {
                        return [
                            'function_name' => $fname,
                            'integration_ids' => $items->pluck('integration_id')->unique()->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                $appPayload['integration_functions'] = $grouped;
            } else {
                $appPayload['integration_functions'] = [];
            }
        }

        return [
            'app' => $appPayload,
            'streams' => $this->streamService->getAllStreams()->map(function ($streamDto) {
                if (is_object($streamDto) && method_exists($streamDto, 'toArray')) {
                    $data = $streamDto->toArray();
                } elseif (is_array($streamDto)) {
                    $data = $streamDto;
                } else {
                    // Fallback for stdClass or other objects
                    $data = [
                        'stream_id' => $streamDto->stream_id ?? null,
                        'stream_name' => $streamDto->stream_name ?? null,
                        'description' => $streamDto->description ?? null,
                    ];
                }
                return ['data' => $data];
            }),
            'appTypes' => $this->getAppTypes(),
            'stratifications' => $this->getStratifications(),
            'technologyOptions' => $technologyOptions,
            // Individual technology arrays for frontend compatibility
            'vendors' => $technologyOptions['vendors'] ?? [],
            'operatingSystems' => $technologyOptions['operating_systems'] ?? [],
            'databases' => $technologyOptions['databases'] ?? [],
            'languages' => $technologyOptions['programming_languages'] ?? [],
            'frameworks' => $technologyOptions['frameworks'] ?? [],
            'middlewares' => $technologyOptions['middlewares'] ?? [],
            'thirdParties' => $technologyOptions['third_parties'] ?? [],
            'platforms' => $technologyOptions['platforms'] ?? [],
            // Integration options for Informasi Fungsi section
            'integrationOptions' => $integrationOptions,
        ];
    }

    /**
     * Create new app with technology components
     */
    public function createApp(array $validatedData): AppDTO
    {
        $appDTO = $this->buildAppDTOFromValidatedData($validatedData);
        $app = $this->appRepository->createWithTechnology($appDTO);

        // Persist function mappings if provided
        if (!empty($validatedData['functions']) && is_array($validatedData['functions'])) {
            $this->saveAppIntegrationFunctions($app->app_id, $validatedData['functions']);
        }
        
        return AppDTO::fromModel($app);
    }

    /**
     * Update app and its technology components
     */
    public function updateApp(App $app, array $validatedData): AppDTO
    {
        $appDTO = $this->buildAppDTOFromValidatedData($validatedData, $app->app_id);
        $updateResult = $this->appRepository->updateWithTechnology($app, $appDTO);

        // Persist function mappings if provided (replace existing)
        if (array_key_exists('functions', $validatedData)) {
            $this->saveAppIntegrationFunctions($app->app_id, $validatedData['functions'] ?? []);
        }
        
        // Reload the app with fresh data (bypassing cache)
        $updatedApp = $this->appRepository->findWithRelationsFresh($app->app_id);
        
        return AppDTO::fromModel($updatedApp);
    }

    private function saveAppIntegrationFunctions(int $appId, array $functions): void
    {
        // Expected function item: { function_name: string, integration_ids: number[] } or legacy { integration_id }
        DB::transaction(function () use ($appId, $functions) {
            DB::table('appintegration_functions')->where('app_id', $appId)->delete();

            $rows = [];
            foreach ($functions as $f) {
                $name = trim((string)($f['function_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                // Normalize integrations to an array
                $ids = [];
                if (isset($f['integration_ids']) && is_array($f['integration_ids'])) {
                    $ids = array_values(array_unique(array_map('intval', $f['integration_ids'])));
                } elseif (!empty($f['integration_id'])) { // legacy single
                    $ids = [intval($f['integration_id'])];
                }

                foreach ($ids as $integrationId) {
                    if (!$integrationId) continue;
                    $rows[] = [
                        'app_id' => $appId,
                        'integration_id' => $integrationId,
                        'function_name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($rows)) {
                DB::table('appintegration_functions')->insert($rows);
            }
        });
    }

    /**
     * Delete app and its related data
     */
    public function deleteApp(App $app): bool
    {
        $appId = $app->app_id;
        $deleted = $this->appRepository->delete($app);
        
        if ($deleted) {
            // Remove app from all stream layouts
            $this->streamLayoutRepository->removeAppFromLayouts($appId);
        }
        
        return $deleted;
    }

    /**
     * Get apps by stream name
     */
    public function getAppsByStreamName(string $streamName): array
    {
        $apps = $this->appRepository->getAppsByStreamName($streamName);
        
        return $apps->map(fn($app) => AppDTO::fromModel($app))->toArray();
    }

    /**
     * Search apps by name
     */
    public function searchAppsByName(string $searchTerm): array
    {
        $apps = $this->appRepository->searchAppsByName($searchTerm);
        
        return $apps->map(fn($app) => AppDTO::fromModel($app))->toArray();
    }

    /**
     * Get app statistics
     */
    public function getAppStatistics(): array
    {
        return $this->appRepository->getAppStatistics();
    }

    /**
     * Get apps with integration counts
     */
    public function getAppsWithIntegrationCounts(): array
    {
        $apps = $this->appRepository->getAppsWithIntegrationCounts();
        
        return $apps->map(function ($app) {
            $appDTO = AppDTO::fromModel($app);
            $data = $appDTO->toArray();
            $data['integration_counts'] = [
                'outgoing' => $app->integrations_count ?? 0,
                'incoming' => $app->integrated_by_count ?? 0,
                'total' => $app->total_integrations ?? 0,
            ];
            
            return $data;
        })->toArray();
    }

    /**
     * Check if app exists by name
     */
    public function appExistsByName(string $appName): bool
    {
        return $this->appRepository->existsByName($appName);
    }

    /**
     * Bulk update apps
     */
    public function bulkUpdateApps(array $appsData): bool
    {
        return $this->appRepository->bulkUpdateApps($appsData);
    }

    /**
     * Get app types enumeration
     */
    private function getAppTypes(): array
    {
        return ['cots', 'inhouse', 'outsource'];
    }

    /**
     * Get stratifications enumeration
     */
    private function getStratifications(): array
    {
        return ['strategis', 'kritikal', 'umum'];
    }

    /**
     * Get all technology options for forms
     */
    private function getTechnologyOptions(): array
    {
        $technologyTypes = $this->technologyRepository->getTechnologyTypeMappings();
        $options = [];

        foreach ($technologyTypes as $type => $mapping) {
            $enumData = $this->technologyRepository->getEnumValues($mapping['table']);
            $options[$type] = $enumData->values;
        }

        return $options;
    }

    /**
     * Build AppDTO from validated form data
     */
    private function buildAppDTOFromValidatedData(array $validatedData, ?int $appId = null): AppDTO
    {
        $technologyComponents = [];

        // Extract technology components from validated data
        $technologyMapping = [
            'vendors' => 'vendors',
            'operating_systems' => 'operating_systems',
            'databases' => 'databases',
            'programming_languages' => 'languages',
            'frameworks' => 'frameworks',
            'middlewares' => 'middlewares',
            'third_parties' => 'third_parties',
            'platforms' => 'platforms',
        ];

        foreach ($technologyMapping as $dtoKey => $dataKey) {
            if (isset($validatedData[$dataKey])) {
                if (!empty($validatedData[$dataKey])) {
                    $technologyComponents[$dtoKey] = array_map(
                        fn($component) => is_array($component) ? $component : ['name' => $component],
                        $validatedData[$dataKey]
                    );
                } else {
                    // Include empty arrays to signal deletion of all components of this type
                    $technologyComponents[$dtoKey] = [];
                }
            }
        }

        return new AppDTO(
            appId: $appId,
            appName: $validatedData['app_name'],
            description: $validatedData['description'] ?? null,
            streamId: $validatedData['stream_id'],
            appType: $validatedData['app_type'],
            stratification: $validatedData['stratification'],
            isFunction: (bool)($validatedData['is_function'] ?? false),
            technologyComponents: $technologyComponents
        );
    }

    /**
     * Build pagination links array that matches Laravel's default pagination format
     */
    private function buildPaginationLinks($paginator): array
    {
        $links = [];
        
        // Previous link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];
        
        // Page number links
        foreach (range(1, $paginator->lastPage()) as $page) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $paginator->currentPage(),
            ];
        }
        
        // Next link
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        
        return $links;
    }
} 