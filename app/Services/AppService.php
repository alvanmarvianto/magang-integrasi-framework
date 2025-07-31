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
        return $this->appRepository->delete($app);
    }
} 