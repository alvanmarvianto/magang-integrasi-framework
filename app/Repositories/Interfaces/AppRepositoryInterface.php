<?php

namespace App\Repositories\Interfaces;

use App\Models\App;
use App\DTOs\AppDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppRepositoryInterface
{
    /**
     * Get paginated list of apps with optional search and sorting
     */
    public function getPaginatedApps(
        ?string $search = null,
        int $perPage = 10,
        string $sortBy = 'app_name',
        bool $sortDesc = false
    ): LengthAwarePaginator;

    /**
     * Find app by ID with relationships
     */
    public function findWithRelations(int $id): ?App;

    /**
     * Find app by ID with relationships bypassing cache
     */
    public function findWithRelationsFresh(int $id): ?App;

    /**
     * Find app by ID and return as DTO
     */
    public function findAsDTO(int $id): ?AppDTO;

    /**
     * Find app by ID and return as DTO bypassing cache
     */
    public function findAsDTOFresh(int $id): ?AppDTO;

    /**
     * Create new app with technology components
     */
    public function createWithTechnology(AppDTO $appData): App;

    /**
     * Update app and its technology components
     */
    public function updateWithTechnology(App $app, AppDTO $appData): bool;

    /**
     * Delete app and its related data
     */
    public function delete(App $app): bool;

    /**
     * Get apps by stream ID
     */
    public function getAppsByStreamId(int $streamId): Collection;

    /**
     * Get apps by stream name
     */
    public function getAppsByStreamName(string $streamName): Collection;

    /**
     * Get apps by multiple IDs
     */
    public function getAppsByIds(array $appIds): Collection;

    /**
     * Search apps by name
     */
    public function searchAppsByName(string $searchTerm): Collection;

    /**
     * Get apps with integration counts
     */
    public function getAppsWithIntegrationCounts(): Collection;

    /**
     * Check if app exists by name
     */
    public function existsByName(string $appName): bool;

    /**
     * Get app statistics
     */
    public function getAppStatistics(): array;

    /**
     * Bulk update apps
     */
    public function bulkUpdateApps(array $appData): bool;
} 