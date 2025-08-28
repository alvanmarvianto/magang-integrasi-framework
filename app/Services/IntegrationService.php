<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\ConnectionType;
use App\DTOs\IntegrationDTO;
use App\DTOs\ConnectionTypeDTO;
use App\DTOs\AppDTO;
use App\DTOs\AppIntegrationDataDTO;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Services\StreamConfigurationService;
use Illuminate\Support\Collection;

class IntegrationService
{
    protected IntegrationRepositoryInterface $integrationRepository;
    protected AppRepositoryInterface $appRepository;
    protected StreamLayoutService $streamLayoutService;
    protected StreamConfigurationService $streamConfigService;

    public function __construct(
        IntegrationRepositoryInterface $integrationRepository,
        AppRepositoryInterface $appRepository,
        StreamLayoutService $streamLayoutService,
        StreamConfigurationService $streamConfigService
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->appRepository = $appRepository;
        $this->streamLayoutService = $streamLayoutService;
        $this->streamConfigService = $streamConfigService;
    }

    /**
     * Get paginated integrations with search and sorting
     */
    public function getPaginatedIntegrations(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'source_app_name',
        bool $sortDesc = false
    ): array {
        $paginator = $this->integrationRepository->getPaginatedIntegrations(
            $search,
            $perPage,
            $sortBy,
            $sortDesc
        );

        $transformedData = $paginator->getCollection()->map(function ($integration) {
            return IntegrationDTO::fromModel($integration)->toArray();
        });

        return [
            'integrations' => [
                'data' => $transformedData->values()->toArray(),
                'meta' => $this->extractPaginationMeta($paginator),
            ],
        ];
    }

    /**
     * Get form data for creating/editing integrations
     */
    public function getFormData(?int $integrationId = null): array
    {
        $integrationDTO = null;
        
        if ($integrationId) {
            $integration = $this->integrationRepository->findWithRelations($integrationId);
            $integrationDTO = $integration ? IntegrationDTO::fromModel($integration) : null;
        }

        return [
            'integration' => $integrationDTO?->toArray(),
            'apps' => $this->getAppsForSelection(),
            'connectionTypes' => $this->getConnectionTypesForSelection(),
        ];
    }

    /**
     * Create new integration
     */
    public function createIntegration(array $validatedData): IntegrationDTO
    {
        if ($this->integrationRepository->integrationExistsBetweenApps(
            $validatedData['source_app_id'],
            $validatedData['target_app_id']
        )) {
            throw new \InvalidArgumentException('Integration already exists between these apps');
        }

        $integrationDTO = IntegrationDTO::fromArray($validatedData);
        $integration = $this->integrationRepository->create($integrationDTO);
        
        $this->streamLayoutService->updateStreamLayoutsForIntegration($integration);
        
        return IntegrationDTO::fromModel($integration);
    }

    /**
     * Update existing integration
     */
    public function updateIntegration(AppIntegration $integration, array $validatedData): IntegrationDTO
    {
        $integrationDTO = IntegrationDTO::fromArray(array_merge(
            ['integration_id' => $integration->integration_id],
            $validatedData
        ));
        
        $this->integrationRepository->update($integration, $integrationDTO);
        
        $updatedIntegration = $this->integrationRepository->findWithRelations($integration->integration_id);
        $this->streamLayoutService->updateStreamLayoutsForIntegration($updatedIntegration);
        
        return IntegrationDTO::fromModel($updatedIntegration);
    }

    /**
     * Delete integration
     */
    public function deleteIntegration(AppIntegration $integration): bool
    {
        $this->streamLayoutService->removeIntegrationFromLayouts($integration);
        
        return $this->integrationRepository->delete($integration);
    }
    
    /**
     * Get apps for selection dropdown
     */
    private function getAppsForSelection(): array
    {
        return App::select('app_id', 'app_name')
            ->orderBy('app_name')
            ->get()
            ->map(fn($app) => [
                'app_id' => $app->app_id,
                'app_name' => $app->app_name,
            ])
            ->toArray();
    }

    /**
     * Get connection types for selection dropdown
     */
    private function getConnectionTypesForSelection(): array
    {
        return ConnectionType::all()
            ->map(fn($type) => ConnectionTypeDTO::fromModel($type)->toArray())
            ->toArray();
    }

    /**
     * Extract pagination metadata
     */
    private function extractPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'links' => $paginator->onEachSide(1)->linkCollection()->toArray(),
        ];
    }

    /**
     * Get app integration data for app integration page
     */
    public function getAppIntegrationData(int $appId): AppIntegrationDataDTO
    {
        // Get app with stream information
        $app = $this->appRepository->findWithRelationsFresh($appId);
        
        if (!$app) {
            throw new \Exception("App with ID {$appId} not found");
        }

        $allowedStreams = $this->streamConfigService->getAllowedDiagramStreams();
        if (!$app->stream || !in_array($app->stream->stream_name, $allowedStreams)) {
            throw new \Exception('Access to this app integration is not allowed');
        }

        $appDTO = AppDTO::fromModel($app);

        $integrations = $this->getAppIntegrationsForDisplay($app);

        return AppIntegrationDataDTO::fromAppWithIntegrations($appDTO, $integrations);
    }

    /**
     * Get formatted integrations for display
     */
    private function getAppIntegrationsForDisplay(App $app): Collection
    {
    $app->loadMissing(['integrations.stream', 'integratedBy.stream']);

        $integrations = $app->integrations->map(function ($integration) {
            return [
                'app_id' => $integration->app_id,
                'app_name' => $integration->app_name,
                'stream_name' => $integration->stream?->stream_name,
                'connection_type' => null,
                'connection_color' => '#000000',
            ];
        });

        $integratedBy = $app->integratedBy->map(function ($integration) {
            return [
                'app_id' => $integration->app_id,
                'app_name' => $integration->app_name,
                'stream_name' => $integration->stream?->stream_name,
                'connection_type' => null,
                'connection_color' => '#000000',
            ];
        });

        return $integrations
            ->concat($integratedBy)
            ->unique('app_name')
            ->values();
    }
}
