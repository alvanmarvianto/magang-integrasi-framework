<?php

namespace App\Repositories\Interfaces;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use Illuminate\Database\Eloquent\Collection;

interface TechnologyRepositoryInterface
{
    /**
     * Get enum values for a technology type
     */
    public function getEnumValues(string $tableName): TechnologyEnumDTO;

    /**
     * Get technology components for an app
     */
    public function getTechnologyComponentsForApp(int $appId, string $technologyType): Collection;

    /**
     * Save technology components for an app
     */
    public function saveTechnologyComponentsForApp(int $appId, string $technologyType, array $components): void;

    /**
     * Delete all technology components for an app of a specific type
     */
    public function deleteTechnologyComponentsForApp(int $appId, string $technologyType): bool;

    /**
     * Get apps using specific technology
     */
    public function getAppsUsingTechnology(string $technologyType, string $technologyName): Collection;

    /**
     * Get all technology types and their table mappings
     */
    public function getTechnologyTypeMappings(): array;

    /**
     * Bulk update technology components for an app
     */
    public function bulkUpdateTechnologyComponents(int $appId, array $technologyData): void;

    /**
     * Get technology statistics
     */
    public function getTechnologyStatistics(): array;

    /**
     * Search technologies by name across all types
     */
    public function searchTechnologiesByName(string $searchTerm): Collection;
}