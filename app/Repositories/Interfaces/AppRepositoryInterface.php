<?php

namespace App\Repositories\Interfaces;

use App\Models\App;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppRepositoryInterface
{
    /**
     * Get paginated list of apps with optional search
     */
    public function getPaginatedApps(string $search = null, int $perPage = 10, string $sortBy = 'app_name', bool $sortDesc = false): LengthAwarePaginator;

    /**
     * Find app by ID with relationships
     */
    public function findWithRelations(int $id): ?App;

    /**
     * Create new app with technology components
     */
    public function createWithTechnology(array $data): App;

    /**
     * Update app and its technology components
     */
    public function updateWithTechnology(App $app, array $data): bool;

    /**
     * Delete app and its related data
     */
    public function delete(App $app): bool;

    /**
     * Save technology components for an app
     */
    public function saveTechnologyComponents(App $app, array $data): void;
} 