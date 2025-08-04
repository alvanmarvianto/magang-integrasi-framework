<?php

namespace App\Services;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\AppRepositoryInterface;
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

    public function __construct(
        AppRepositoryInterface $appRepository,
        TechnologyService $technologyService,
        StreamService $streamService,
        TechnologyRepositoryInterface $technologyRepository,
        StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {
        $this->appRepository = $appRepository;
        $this->technologyService = $technologyService;
        $this->streamService = $streamService;
        $this->technologyRepository = $technologyRepository;
        $this->streamLayoutRepository = $streamLayoutRepository;
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
        
        // Debug logging to verify fresh data is being used
        if ($appDTO) {
            Log::info("Admin form data loaded for app {$appId}: " . $appDTO->appName);
        }
        
        $technologyOptions = $this->getTechnologyOptions();

        return [
            'app' => $appDTO ? $appDTO->toArray() : null,
            'streams' => $this->streamService->getAllStreams()->map(fn($streamDto) => [
                'data' => $streamDto->toArray()
            ]),
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
        ];
    }

    /**
     * Create new app with technology components
     */
    public function createApp(array $validatedData): AppDTO
    {
        $appDTO = $this->buildAppDTOFromValidatedData($validatedData);
        $app = $this->appRepository->createWithTechnology($appDTO);
        
        return AppDTO::fromModel($app);
    }

    /**
     * Update app and its technology components
     */
    public function updateApp(App $app, array $validatedData): AppDTO
    {
        $appDTO = $this->buildAppDTOFromValidatedData($validatedData, $app->app_id);
        $updateResult = $this->appRepository->updateWithTechnology($app, $appDTO);
        
        // Reload the app with fresh data (bypassing cache)
        $updatedApp = $this->appRepository->findWithRelationsFresh($app->app_id);
        
        return AppDTO::fromModel($updatedApp);
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
            if (isset($validatedData[$dataKey]) && !empty($validatedData[$dataKey])) {
                $technologyComponents[$dtoKey] = array_map(
                    fn($component) => is_array($component) ? $component : ['name' => $component],
                    $validatedData[$dataKey]
                );
            }
        }

        return new AppDTO(
            appId: $appId,
            appName: $validatedData['app_name'],
            description: $validatedData['description'] ?? null,
            streamId: $validatedData['stream_id'],
            appType: $validatedData['app_type'],
            stratification: $validatedData['stratification'],
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