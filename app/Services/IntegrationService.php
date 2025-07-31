<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppIntegration;
use App\Models\ConnectionType;
use App\DTOs\IntegrationDTO;
use App\DTOs\ConnectionTypeDTO;
use App\DTOs\AppDTO;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\AppRepositoryInterface;
use Illuminate\Support\Collection;

class IntegrationService
{
    protected IntegrationRepositoryInterface $integrationRepository;
    protected AppRepositoryInterface $appRepository;
    protected StreamLayoutService $streamLayoutService;

    public function __construct(
        IntegrationRepositoryInterface $integrationRepository,
        AppRepositoryInterface $appRepository,
        StreamLayoutService $streamLayoutService
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->appRepository = $appRepository;
        $this->streamLayoutService = $streamLayoutService;
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
                'data' => $transformedData->toArray(),
                'meta' => $this->extractPaginationMeta($paginator),
            ]
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
            'directionOptions' => $this->getDirectionOptions(),
        ];
    }

    /**
     * Create new integration
     */
    public function createIntegration(array $validatedData): IntegrationDTO
    {
        // Check for existing integrations to prevent duplicates
        if ($this->integrationRepository->integrationExistsBetweenApps(
            $validatedData['source_app_id'],
            $validatedData['target_app_id']
        )) {
            throw new \InvalidArgumentException('Integration already exists between these apps');
        }

        $integrationDTO = IntegrationDTO::fromArray($validatedData);
        $integration = $this->integrationRepository->create($integrationDTO);
        
        // Update stream layouts after creating integration
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
        
        // Update stream layouts after updating integration
        $updatedIntegration = $this->integrationRepository->findWithRelations($integration->integration_id);
        $this->streamLayoutService->updateStreamLayoutsForIntegration($updatedIntegration);
        
        return IntegrationDTO::fromModel($updatedIntegration);
    }

    /**
     * Delete integration
     */
    public function deleteIntegration(AppIntegration $integration): bool
    {
        // Remove integration from all stream layouts before deleting
        $this->streamLayoutService->removeIntegrationFromLayouts($integration);
        
        return $this->integrationRepository->delete($integration);
    }

    /**
     * Get integrations for a specific app
     */
    public function getIntegrationsForApp(int $appId): array
    {
        $integrations = $this->integrationRepository->getIntegrationsForApp($appId);
        
        return $integrations->map(fn($integration) => IntegrationDTO::fromModel($integration)->toArray())->toArray();
    }

    /**
     * Get connected apps for a specific app
     */
    public function getConnectedAppsForApp(int $appId): array
    {
        $connectedApps = $this->integrationRepository->getConnectedAppsForApp($appId);
        
        return $connectedApps->map(fn($app) => AppDTO::fromModel($app)->toArray())->toArray();
    }

    /**
     * Get integrations between two specific apps
     */
    public function getIntegrationsBetweenApps(int $sourceAppId, int $targetAppId): array
    {
        $integrations = $this->integrationRepository->getIntegrationsBetweenApps($sourceAppId, $targetAppId);
        
        return $integrations->map(fn($integration) => IntegrationDTO::fromModel($integration)->toArray())->toArray();
    }

    /**
     * Get external apps connected to a stream
     */
    public function getExternalAppsConnectedToStream(array $streamAppIds): array
    {
        $externalApps = $this->integrationRepository->getExternalAppsConnectedToStream($streamAppIds);
        
        return $externalApps->map(fn($app) => AppDTO::fromModel($app)->toArray())->toArray();
    }

    /**
     * Check if integration exists between apps
     */
    public function integrationExistsBetweenApps(int $sourceAppId, int $targetAppId): bool
    {
        return $this->integrationRepository->integrationExistsBetweenApps($sourceAppId, $targetAppId);
    }

    /**
     * Remove duplicate integrations
     */
    public function removeDuplicateIntegrations(): int
    {
        return $this->integrationRepository->removeDuplicateIntegrations();
    }

    /**
     * Get connection types with usage statistics
     */
    public function getConnectionTypes(): array
    {
        return [
            'connectionTypes' => ConnectionType::withCount('appIntegrations')->get()
                ->map(fn($type) => array_merge(
                    ConnectionTypeDTO::fromModel($type)->toArray(),
                    ['usage_count' => $type->app_integrations_count]
                ))
        ];
    }

    /**
     * Get integration statistics
     */
    public function getIntegrationStatistics(): array
    {
        // Get all integrations for statistics
        $allIntegrations = AppIntegration::with('connectionType')->get();
        
        return [
            'total_integrations' => $allIntegrations->count(),
            'bidirectional_integrations' => $allIntegrations->where('direction', 'both_ways')->count(),
            'unidirectional_integrations' => $allIntegrations->where('direction', 'one_way')->count(),
            'integrations_by_connection_type' => $allIntegrations
                ->groupBy('connectionType.type_name')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'most_connected_apps' => $this->getMostConnectedApps(),
        ];
    }

    /**
     * Bulk create integrations
     */
    public function bulkCreateIntegrations(array $integrationsData): array
    {
        $created = [];
        $errors = [];

        foreach ($integrationsData as $index => $data) {
            try {
                $integrationDTO = $this->createIntegration($data);
                $created[] = $integrationDTO->toArray();
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'total_processed' => count($integrationsData),
            'success_count' => count($created),
            'error_count' => count($errors),
        ];
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
     * Get direction options
     */
    private function getDirectionOptions(): array
    {
        return [
            ['value' => 'one_way', 'label' => 'One Way'],
            ['value' => 'both_ways', 'label' => 'Both Ways'],
        ];
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
     * Get most connected apps
     */
    private function getMostConnectedApps(): array
    {
        $appsWithCounts = $this->appRepository->getAppsWithIntegrationCounts();
        
        return $appsWithCounts
            ->sortByDesc('total_integrations')
            ->take(10)
            ->map(function ($app) {
                return [
                    'app_id' => $app->app_id,
                    'app_name' => $app->app_name,
                    'total_integrations' => $app->total_integrations,
                    'outgoing_integrations' => $app->integrations_count,
                    'incoming_integrations' => $app->integrated_by_count,
                ];
            })
            ->values()
            ->toArray();
    }
}
