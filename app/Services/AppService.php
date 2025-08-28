<?php

namespace App\Services;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;

class AppService
{
    protected AppRepositoryInterface $appRepository;
    protected TechnologyService $technologyService;
    protected StreamService $streamService;
    protected TechnologyRepositoryInterface $technologyRepository;
    protected StreamLayoutRepositoryInterface $streamLayoutRepository;
    protected IntegrationRepositoryInterface $integrationRepository;
    protected AppLayoutService $appLayoutService;

    public function __construct(
        AppRepositoryInterface $appRepository,
        TechnologyService $technologyService,
        StreamService $streamService,
        TechnologyRepositoryInterface $technologyRepository,
    StreamLayoutRepositoryInterface $streamLayoutRepository,
    IntegrationRepositoryInterface $integrationRepository,
    AppLayoutService $appLayoutService
    ) {
        $this->appRepository = $appRepository;
        $this->technologyService = $technologyService;
        $this->streamService = $streamService;
        $this->technologyRepository = $technologyRepository;
        $this->streamLayoutRepository = $streamLayoutRepository;
    $this->integrationRepository = $integrationRepository;
    $this->appLayoutService = $appLayoutService;
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
        
        $appDTOs = $paginatedApps->map(function ($app) {
            return AppDTO::fromModel($app);
        });
        
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
        $appDTO = $appId ? $this->appRepository->findAsDTOFresh($appId) : null;
        
        $technologyOptions = $this->getTechnologyOptions();
        $integrationOptions = $this->integrationRepository->getIntegrationOptions();

        $appPayload = $appDTO ? $appDTO->toArray() : null;
        if ($appId && $appPayload !== null) {
            $grouped = $this->appRepository->getIntegrationFunctionsGrouped($appId);
            $appPayload['integration_functions'] = $grouped;
        }

        return [
            'app' => $appPayload,
            'streams' => $this->streamService->getAllStreams()->map(function ($streamDto) {
                if (is_object($streamDto) && method_exists($streamDto, 'toArray')) {
                    $data = $streamDto->toArray();
                } elseif (is_array($streamDto)) {
                    $data = $streamDto;
                } else {
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
            'vendors' => $technologyOptions['vendors'] ?? [],
            'operatingSystems' => $technologyOptions['operating_systems'] ?? [],
            'databases' => $technologyOptions['databases'] ?? [],
            'languages' => $technologyOptions['programming_languages'] ?? [],
            'frameworks' => $technologyOptions['frameworks'] ?? [],
            'middlewares' => $technologyOptions['middlewares'] ?? [],
            'thirdParties' => $technologyOptions['third_parties'] ?? [],
            'platforms' => $technologyOptions['platforms'] ?? [],
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

        if (!empty($validatedData['functions']) && is_array($validatedData['functions'])) {
            $this->appRepository->replaceIntegrationFunctions($app->app_id, $validatedData['functions']);
            
            $this->appLayoutService->autoSyncColorsAfterAppOperation($app->app_id);
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

        if (array_key_exists('functions', $validatedData)) {
            $this->appRepository->replaceIntegrationFunctions($app->app_id, $validatedData['functions'] ?? []);
            
            $this->appLayoutService->autoSyncColorsAfterAppOperation($app->app_id);
        }
        
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
            $this->streamLayoutRepository->removeAppFromLayouts($appId);
        }
        
        return $deleted;
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

        foreach ($technologyTypes as $type => $techType) {
            $enumData = $this->technologyRepository->getEnumValues($type);
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
            isModule: (bool)($validatedData['is_module'] ?? false),
            technologyComponents: $technologyComponents
        );
    }

    /**
     * Build pagination links array that matches Laravel's default pagination format
     */
    private function buildPaginationLinks($paginator): array
    {
        $links = [];
        
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];
        
        foreach (range(1, $paginator->lastPage()) as $page) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $paginator->currentPage(),
            ];
        }
        
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        
        return $links;
    }
} 