<?php

namespace App\Repositories\Interfaces;

use App\Models\AppIntegration;
use App\DTOs\IntegrationDTO;
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
     * Check if integration exists between two apps
     */
    public function integrationExistsBetweenApps(int $sourceAppId, int $targetAppId): bool;

    /**
     * Get all integration options for forms (cached), sorted alphabetically by label.
     * Each item: [integration_id:int, label:string]
     */
    public function getIntegrationOptions(): array;
}