<?php

namespace App\Services;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use App\DTOs\TechnologyAppListingDTO;
use App\DTOs\AppTechnologyDataDTO;
use App\DTOs\TechnologyListingPageDTO;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Models\App;
use App\Models\Technology;
use App\Models\AppTechnology;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TechnologyService
{
    /**
     * Get technology type mapping
     */
    public function getTypeMapping(): array
    {
        return [
            'vendors' => 'vendors',
            'operatingSystems' => 'operating_systems',
            'databases' => 'databases',
            'languages' => 'programming_languages',
            'frameworks' => 'frameworks',
            'middlewares' => 'middlewares',
            'thirdParties' => 'third_parties',
            'platforms' => 'platforms',
        ];
    }

    /**
     * Get table name for technology type (legacy compatibility)
     */
    public function getTableName(string $type): string
    {
        // This method is kept for backward compatibility but will use new structure
        return 'technologies'; // All technologies are now in one table
    }

    /**
     * Get enum values from technologies table by type
     */
    public function getEnumValues(string $type): TechnologyEnumDTO
    {
        $typeMapping = $this->getTypeMapping();
        $techType = $typeMapping[$type] ?? $type;
        
        $technologies = Technology::where('type', $techType)->pluck('name')->toArray();

        return new TechnologyEnumDTO(
            type: $type,
            values: $technologies
        );
    }

    /**
     * Get technology data for an app by type
     */
    public function getTechnologyData(string $type, int $appId): Collection
    {
        // For backward compatibility, handle old table names
        if (str_starts_with($type, 'technology_')) {
            $techType = str_replace('technology_', '', $type);
            $techType = str_replace('_', '_', $techType); // Keep underscores for exact mapping
        } else {
            $typeMapping = $this->getTypeMapping();
            $techType = $typeMapping[$type] ?? $type;
        }

        $appTechnologies = AppTechnology::with('technology')
            ->where('app_id', $appId)
            ->whereHas('technology', function ($query) use ($techType) {
                $query->where('type', $techType);
            })
            ->get();

        return $appTechnologies->map(function ($appTech) {
            return TechnologyComponentDTO::fromModel(
                $appTech->technology,
                $appTech->version
            );
        });
    }

    /**
     * Get apps by technology condition as DTOs
     */
    public function getAppByCondition(string $techType, string $name): Collection
    {
        // Handle legacy table name format
        if (str_starts_with($techType, 'technology_')) {
            $techType = str_replace('technology_', '', $techType);
        }

        $technology = Technology::where('type', $techType)
            ->where('name', $name)
            ->first();

        if (!$technology) {
            return collect([]);
        }

        $appTechnologies = AppTechnology::with(['app.stream'])
            ->where('tech_id', $technology->id)
            ->get();

        return $appTechnologies->map(function ($appTech) use ($name) {
            $app = $appTech->app;
            return TechnologyAppListingDTO::fromArray([
                'id' => $app->app_id,
                'name' => $app->app_name,
                'description' => $app->description,
                'version' => $appTech->version,
                'stream' => [
                    'id' => $app->stream?->stream_id,
                    'name' => $app->stream?->stream_name
                ],
                'technology_detail' => $name . ($appTech->version ? ' ' . $appTech->version : '')
            ]);
        });
    }

    /**
     * Get apps by app type or stratification as DTOs
     */
    public function getAppsByAttribute(string $attribute, string $value): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection $apps */
        $apps = App::with('stream')->where($attribute, $value)->get();

        return $apps->map(function ($app) use ($value) {
            return TechnologyAppListingDTO::fromArray([
                'id' => $app->getAttribute('app_id'),
                'name' => $app->getAttribute('app_name'),
                'description' => $app->getAttribute('description'),
                'version' => null,
                'stream' => [
                    'id' => $app->stream?->stream_id,
                    'name' => $app->stream?->stream_name
                ],
                'technology_detail' => strtoupper(str_replace('_', ' ', $value))
            ]);
        });
    }

    /**
     * Store new technology
     */
    public function storeEnumValue(string $type, string $name): bool
    {
        try {
            $typeMapping = $this->getTypeMapping();
            $techType = $typeMapping[$type] ?? $type;
            
            $existingTech = Technology::where('type', $techType)
                ->where('name', $name)
                ->first();
                
            if ($existingTech) {
                throw new \Exception('Technology already exists');
            }
            
            Technology::create([
                'type' => $techType,
                'name' => $name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error adding technology: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing technology
     */
    public function updateEnumValue(string $type, string $oldValue, string $newValue): bool
    {
        try {
            $typeMapping = $this->getTypeMapping();
            $techType = $typeMapping[$type] ?? $type;
            
            $technology = Technology::where('type', $techType)
                ->where('name', $oldValue)
                ->first();
                
            if (!$technology) {
                throw new \Exception('Technology not found');
            }
            
            if ($newValue !== $oldValue) {
                $existingTech = Technology::where('type', $techType)
                    ->where('name', $newValue)
                    ->first();
                    
                if ($existingTech) {
                    throw new \Exception('Technology name already exists');
                }
            }

            if ($this->isTechnologyInUse($technology->id)) {
                throw new \Exception('Technology is currently in use');
            }

            $technology->update(['name' => $newValue]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating technology: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete technology
     */
    public function deleteEnumValue(string $type, string $value): bool
    {
        try {
            $typeMapping = $this->getTypeMapping();
            $techType = $typeMapping[$type] ?? $type;
            
            $technology = Technology::where('type', $techType)
                ->where('name', $value)
                ->first();
                
            if (!$technology) {
                throw new \Exception('Technology not found');
            }
            
            if ($this->isTechnologyInUse($technology->id)) {
                throw new \Exception('Technology is currently in use and cannot be deleted');
            }

            $technology->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Error in deleteEnumValue: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if technology is in use
     */
    public function isTechnologyInUse(int $techId): bool
    {
        return AppTechnology::where('tech_id', $techId)->exists();
    }

    /**
     * Legacy method for backward compatibility
     */
    public function isEnumValueInUse(string $tableName, string $value): bool
    {
        // Extract type from old table name
        $techType = str_replace('technology_', '', $tableName);
        
        $technology = Technology::where('type', $techType)
            ->where('name', $value)
            ->first();
            
        return $technology ? $this->isTechnologyInUse($technology->id) : false;
    }

    /**
     * Get apps using specific technology
     */
    public function getAppsUsingEnum(string $tableName, string $value): array
    {
        $techType = str_replace('technology_', '', $tableName);
        
        $technology = Technology::where('type', $techType)
            ->where('name', $value)
            ->first();
            
        if (!$technology) {
            return [];
        }

        $appTechnologies = AppTechnology::with('app')
            ->where('tech_id', $technology->id)
            ->get();

        return $appTechnologies->map(function($appTech) {
            return [
                'id' => $appTech->app->app_id,
                'name' => $appTech->app->app_name,
                'edit_url' => route('admin.apps.edit', $appTech->app->app_id)
            ];
        })->all();
    }

    /**
     * Get all technology types with their values as DTOs
     */
    public function getAllTechnologyTypes(): array
    {
        $types = [
            'vendors', 'operatingSystems', 'databases', 'languages',
            'frameworks', 'middlewares', 'thirdParties', 'platforms'
        ];

        $result = [];
        foreach ($types as $type) {
            try {
                $enumDTO = $this->getEnumValues($type);
                $result[$type] = $enumDTO;
            } catch (\Exception $e) {
                Log::warning("Failed to get enum values for type {$type}: " . $e->getMessage());
                $result[$type] = new TechnologyEnumDTO(type: $type, values: []);
            }
        }

        return $result;
    }

    /**
     * Get technology components for multiple apps as DTOs
     */
    public function getTechnologyComponentsForApps(array $appIds, string $type): Collection
    {
        $typeMapping = $this->getTypeMapping();
        $techType = $typeMapping[$type] ?? $type;
        
        $appTechnologies = AppTechnology::with('technology')
            ->whereIn('app_id', $appIds)
            ->whereHas('technology', function ($query) use ($techType) {
                $query->where('type', $techType);
            })
            ->get();

        return $appTechnologies->map(function ($appTech) {
            return TechnologyComponentDTO::fromModel(
                $appTech->technology,
                $appTech->version
            );
        });
    }

    /**
     * Get app technology data by app ID
     */
    public function getAppTechnologyData(int $appId): AppTechnologyDataDTO
    {
        // Get app data using AppRepository
        $appRepository = app(AppRepositoryInterface::class);
        $app = $appRepository->findAsDTOFresh($appId);
        
        if (!$app) {
            throw new \Exception("App with ID {$appId} not found");
        }

        // Get all technology data for this app using new structure
        $technologies = [
            'app_type' => $app->appType,
            'stratification' => $app->stratification,
            'vendor' => $this->getTechnologyData('vendors', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'os' => $this->getTechnologyData('operating_systems', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'database' => $this->getTechnologyData('databases', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'language' => $this->getTechnologyData('programming_languages', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'third_party' => $this->getTechnologyData('third_parties', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'middleware' => $this->getTechnologyData('middlewares', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'framework' => $this->getTechnologyData('frameworks', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'platform' => $this->getTechnologyData('platforms', $appId)
                ->map(fn($dto) => $dto->toArray())->toArray(),
        ];

        return AppTechnologyDataDTO::fromAppWithTechnologies($app, $technologies);
    }

    /**
     * Get technology listing page data by technology type and name
     */
    public function getTechnologyListingData(string $type, string $name): TechnologyListingPageDTO
    {
        $typeMap = [
            'app_type' => ['type' => 'App Type', 'icon' => 'fas fa-cube'],
            'stratification' => ['type' => 'Stratification', 'icon' => 'fas fa-layer-group'],
            'vendor' => ['type' => 'Vendor', 'icon' => 'fas fa-building'],
            'os' => ['type' => 'Operating System', 'icon' => 'fas fa-desktop'],
            'database' => ['type' => 'Database', 'icon' => 'fas fa-database'],
            'language' => ['type' => 'Programming Language', 'icon' => 'fas fa-code'],
            'third_party' => ['type' => 'Third Party', 'icon' => 'fas fa-plug'],
            'middleware' => ['type' => 'Middleware', 'icon' => 'fas fa-exchange-alt'],
            'framework' => ['type' => 'Framework', 'icon' => 'fas fa-tools'],
            'platform' => ['type' => 'Platform', 'icon' => 'fas fa-cloud'],
        ];

        if (!isset($typeMap[$type])) {
            throw new \InvalidArgumentException("Invalid technology type: {$type}");
        }

        // Get apps based on type
        if (in_array($type, ['app_type', 'stratification'])) {
            $apps = $this->getAppsByAttribute($type, $name);
        } else {
            $techType = $this->getServiceTypeKey($type);
            $apps = $this->getAppByCondition($techType, $name);
        }

        return TechnologyListingPageDTO::create(
            apps: $apps->map(fn($dto) => $dto->toArray())->toArray(),
            technologyType: $typeMap[$type]['type'],
            technologyName: $name,
            icon: $typeMap[$type]['icon']
        );
    }

    /**
     * Map controller parameter to service type key
     */
    private function getServiceTypeKey(string $controllerType): string
    {
        return match ($controllerType) {
            'vendor' => 'vendors',
            'os' => 'operating_systems',
            'database' => 'databases',
            'language' => 'programming_languages',
            'third_party' => 'third_parties',
            'middleware' => 'middlewares',
            'framework' => 'frameworks',
            'platform' => 'platforms',
            default => throw new \InvalidArgumentException("Invalid controller type: {$controllerType}"),
        };
    }
} 