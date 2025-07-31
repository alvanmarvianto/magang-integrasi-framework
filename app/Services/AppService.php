<?php

namespace App\Services;

use App\Models\App;
use App\DTOs\AppDTO;
use App\Http\Resources\AppResource;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;

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
        $apps = $this->appRepository->getPaginatedApps($search, $perPage, $sortBy, $sortDesc);
        
        return [
            'apps' => AppResource::collection($apps),
            'streams' => $this->streamService->getAllStreams(),
        ];
    }

    /**
     * Get app form data for create/edit operations
     */
    public function getFormData(?int $appId = null): array
    {
        $appDTO = $appId ? $this->appRepository->findAsDTO($appId) : null;

        return [
            'app' => $appDTO ? $appDTO->toArray() : null,
            'streams' => $this->streamService->getAllStreams(),
            'appTypes' => $this->getAppTypes(),
            'stratifications' => $this->getStratifications(),
            'technologyOptions' => $this->getTechnologyOptions(),
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
        $this->appRepository->updateWithTechnology($app, $appDTO);
        
        // Reload the app to get updated data
        $updatedApp = $this->appRepository->findWithRelations($app->app_id);
        
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
            'languages' => 'languages',
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
} 