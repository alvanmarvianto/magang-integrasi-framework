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
     * Get apps with integration counts
     */
    public function getAppsWithIntegrationCounts(): Collection;

    /**
     * Get integration functions grouped by function_name for an app
     * Returns an array of [ { function_name: string, integration_ids: int[] }, ... ]
     */
    public function getIntegrationFunctionsGrouped(int $appId): array;

    /**
     * Replace all integration functions for an app with the provided set.
     * Each item: [ 'function_name' => string, 'integration_ids' => int[] ]
     */
    public function replaceIntegrationFunctions(int $appId, array $functions): void;
} 