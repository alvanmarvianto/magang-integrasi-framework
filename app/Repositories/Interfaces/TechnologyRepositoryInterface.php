<?php

namespace App\Repositories\Interfaces;

use App\DTOs\TechnologyEnumDTO;

interface TechnologyRepositoryInterface
{
    /**
     * Get enum values for a technology type
     */
    public function getEnumValues(string $tableName): TechnologyEnumDTO;

    /**
     * Save technology components for an app
     */
    public function saveTechnologyComponentsForApp(int $appId, string $technologyType, array $components): void;

    /**
     * Delete all technology components for an app of a specific type
     */
    public function deleteTechnologyComponentsForApp(int $appId, string $technologyType): bool;

    /**
     * Get all technology types and their table mappings
     */
    public function getTechnologyTypeMappings(): array;

    /**
     * Bulk update technology components for an app
     */
    public function bulkUpdateTechnologyComponents(int $appId, array $technologyData): void;
}