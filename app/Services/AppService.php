<?php

namespace App\Services;

use App\Models\App;
use App\Http\Resources\AppResource;
use App\Repositories\Interfaces\AppRepositoryInterface;

class AppService
{
    protected AppRepositoryInterface $appRepository;
    protected TechnologyService $technologyService;
    protected StreamService $streamService;

    public function __construct(
        AppRepositoryInterface $appRepository,
        TechnologyService $technologyService,
        StreamService $streamService
    ) {
        $this->appRepository = $appRepository;
        $this->technologyService = $technologyService;
        $this->streamService = $streamService;
    }

    /**
     * Get paginated list of apps with optional search
     */
    public function getPaginatedApps(string $search = null, int $perPage = 10, string $sortBy = 'app_name', bool $sortDesc = false): array
    {
        $apps = $this->appRepository->getPaginatedApps($search, $perPage, $sortBy, $sortDesc);
        
        return [
            'apps' => AppResource::collection($apps),
            'streams' => $this->streamService->getAllStreams(),
        ];
    }

    /**
     * Get app form data for create/edit
     */
    public function getFormData(?int $appId = null): array
    {
        $app = $appId ? $this->appRepository->findWithRelations($appId) : null;

        return [
            'app' => $app ? new AppResource($app) : null,
            'streams' => $this->streamService->getAllStreams(),
            'appTypes' => ['cots', 'inhouse', 'outsource'],
            'stratifications' => ['strategis', 'kritikal', 'umum'],
            'vendors' => $this->technologyService->getEnumValues('technology_vendors'),
            'operatingSystems' => $this->technologyService->getEnumValues('technology_operating_systems'),
            'databases' => $this->technologyService->getEnumValues('technology_databases'),
            'languages' => $this->technologyService->getEnumValues('technology_programming_languages'),
            'frameworks' => $this->technologyService->getEnumValues('technology_frameworks'),
            'middlewares' => $this->technologyService->getEnumValues('technology_middlewares'),
            'thirdParties' => $this->technologyService->getEnumValues('technology_third_parties'),
            'platforms' => $this->technologyService->getEnumValues('technology_platforms'),
        ];
    }

    /**
     * Create new app with technology components
     */
    public function createApp(array $data): App
    {
        return $this->appRepository->createWithTechnology($data);
    }

    /**
     * Update app and its technology components
     */
    public function updateApp(App $app, array $data): bool
    {
        return $this->appRepository->updateWithTechnology($app, $data);
    }

    /**
     * Delete app and its related data
     */
    public function deleteApp(App $app): bool
    {
        return $this->appRepository->delete($app);
    }
} 