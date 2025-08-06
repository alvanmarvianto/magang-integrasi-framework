<?php

namespace App\Repositories\Interfaces;

use App\Models\AppIntegration;
use App\DTOs\IntegrationDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface IntegrationRepositoryInterface
{
    /**
     * Get paginated list of integrations with optional search and sorting
     */
    public function getPaginatedIntegrations(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'source_app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator;

    /**
     * Find integration by ID with relationships
     */
    public function findWithRelations(int $integrationId): ?AppIntegration;

    /**
     * Create new integration
     */
    public function create(IntegrationDTO $integrationData): AppIntegration;

    /**
     * Update existing integration
     */
    public function update(AppIntegration $integration, IntegrationDTO $integrationData): bool;

    /**
     * Delete integration
     */
    public function delete(AppIntegration $integration): bool;

    /**
     * Get integrations for specific app
     */
    public function getIntegrationsForApp(int $appId): Collection;

    /**
     * Get integrations between two apps
     */
    public function getIntegrationsBetweenApps(int $sourceAppId, int $targetAppId): Collection;

    /**
     * Check if integration exists between two apps
     */
    public function integrationExistsBetweenApps(int $sourceAppId, int $targetAppId): bool;

    /**
     * Get connected apps for a specific app
     */
    public function getConnectedAppsForApp(int $appId): Collection;

    /**
     * Get integrations for multiple apps
     */
    public function getIntegrationsForApps(array $appIds): Collection;

    /**
     * Remove duplicate integrations
     */
    public function removeDuplicateIntegrations(): int;

    /**
     * Get apps integrated with apps in specific stream
     */
    public function getExternalAppsConnectedToStream(array $streamAppIds): Collection;

    /**
     * Get integration statistics
     */
    public function getIntegrationStatistics(): array;
}