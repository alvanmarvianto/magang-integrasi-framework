<?php

namespace App\Services;

use App\DTOs\TechnologyComponentDTO;
use App\DTOs\TechnologyEnumDTO;
use App\DTOs\TechnologyAppListingDTO;
use App\Models\App;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnologyService
{
    /**
     * Get table name for technology type
     */
    public function getTableName(string $type): string
    {
        return match ($type) {
            'vendors' => 'technology_vendors',
            'operatingSystems' => 'technology_operating_systems',
            'databases' => 'technology_databases',
            'languages' => 'technology_programming_languages',
            'frameworks' => 'technology_frameworks',
            'middlewares' => 'technology_middlewares',
            'thirdParties' => 'technology_third_parties',
            'platforms' => 'technology_platforms',
            default => throw new \InvalidArgumentException('Invalid technology type'),
        };
    }

    /**
     * Get enum values from a technology table
     */
    public function getEnumValues(string $tableName): TechnologyEnumDTO
    {
        $type = DB::table('information_schema.COLUMNS')
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', 'name')
            ->value('COLUMN_TYPE');

        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $values = str_getcsv($matches[1], ',', "'");

        $cleanValues = array_map(function ($value) {
            return trim($value);
        }, $values);

        return new TechnologyEnumDTO(
            type: $tableName,
            values: $cleanValues
        );
    }

    /**
     * Get technology data for an app
     */
    public function getTechnologyData(string $tableName, int $appId): Collection
    {
        $results = DB::table($tableName)
            ->where('app_id', $appId)
            ->get(['name', 'version']);

        return $results->map(function (object $item) {
            return TechnologyComponentDTO::fromArray([
                'id' => null, // Technology components don't have separate IDs
                'name' => $item->name,
                'version' => $item->version,
            ]);
        });
    }

    /**
     * Get apps by technology condition as DTOs
     */
    public function getAppByCondition(string $tableName, string $name): Collection
    {
        $appIds = DB::table($tableName)
            ->where('name', $name)
            ->pluck('app_id')
            ->unique()
            ->toArray();

        if (empty($appIds)) {
            return collect([]);
        }

        /** @var \Illuminate\Database\Eloquent\Collection $apps */
        $apps = App::with('stream')->whereIn('app_id', $appIds)->get();

        return $apps->map(function ($app) use ($tableName, $name) {
            $version = DB::table($tableName)
                ->where('app_id', $app->getAttribute('app_id'))
                ->where('name', $name)
                ->value('version');

            return TechnologyAppListingDTO::fromArray([
                'id' => $app->getAttribute('app_id'),
                'name' => $app->getAttribute('app_name'),
                'description' => $app->getAttribute('description'),
                'version' => $version,
                'stream' => [
                    'id' => $app->stream?->stream_id,
                    'name' => $app->stream?->stream_name
                ],
                'technology_detail' => $name . ($version ? ' ' . $version : '')
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
     * Store new enum value
     */
    public function storeEnumValue(string $type, string $name): bool
    {
        try {
            $tableName = $this->getTableName($type);
            $currentEnumDTO = $this->getEnumValues($tableName);
            
            if (in_array($name, $currentEnumDTO->values)) {
                throw new \Exception('Nilai enum sudah ada');
            }
            
            $newValues = array_merge($currentEnumDTO->values, [$name]);

            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return true;
        } catch (\Exception $e) {
            Log::error('Error adding enum: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing enum value
     */
    public function updateEnumValue(string $type, string $oldValue, string $newValue): bool
    {
        try {
            $tableName = $this->getTableName($type);
            $currentEnumDTO = $this->getEnumValues($tableName);
            
            if ($newValue !== $oldValue && in_array($newValue, $currentEnumDTO->values)) {
                throw new \Exception('Nilai enum sudah ada');
            }

            if ($this->isEnumValueInUse($tableName, $oldValue)) {
                throw new \Exception('Nilai enum sedang digunakan');
            }

            $newValues = array_map(
                fn($val) => $val === $oldValue ? $newValue : $val,
                $currentEnumDTO->values
            );

            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating enum: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete enum value
     */
    public function deleteEnumValue(string $type, string $value): bool
    {
        try {
            $tableName = $this->getTableName($type);
            
            if ($this->isEnumValueInUse($tableName, $value)) {
                throw new \Exception('Nilai enum sedang digunakan dan tidak dapat dihapus');
            }

            $currentEnumDTO = $this->getEnumValues($tableName);
            $newValues = array_values(array_filter($currentEnumDTO->values, fn($val) => $val !== $value));
            
            if (count($newValues) === 0) {
                throw new \Exception('Tidak dapat menghapus nilai enum terakhir');
            }

            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN name ENUM(" .
                implode(',', array_map(fn($val) => "'" . addslashes($val) . "'", $newValues)) .
            ")");

            return true;
        } catch (\Exception $e) {
            Log::error('Error in deleteEnumValue: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if enum value is in use
     */
    public function isEnumValueInUse(string $tableName, string $value): bool
    {
        return DB::table($tableName)->where('name', $value)->exists();
    }

    /**
     * Get apps using specific enum value
     */
    public function getAppsUsingEnum(string $tableName, string $value): array
    {
        $apps = DB::table($tableName)
            ->join('apps', $tableName . '.app_id', '=', 'apps.app_id')
            ->where($tableName . '.name', $value)
            ->select('apps.app_id', 'apps.app_name')
            ->get();

        return $apps->map(function($app) {
            return [
                'id' => $app->app_id,
                'name' => $app->app_name,
                'edit_url' => route('admin.apps.edit', $app->app_id)
            ];
        })->all();
    }

    /**
     * Get all technology types with their enum values as DTOs
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
                $tableName = $this->getTableName($type);
                $enumDTO = $this->getEnumValues($tableName);
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
        $tableName = $this->getTableName($type);
        
        $results = DB::table($tableName)
            ->whereIn('app_id', $appIds)
            ->get(['app_id', 'name', 'version']);

        return $results->map(function ($item) {
            return TechnologyComponentDTO::fromArray([
                'id' => $item->app_id,
                'name' => $item->name,
                'version' => $item->version,
            ]);
        });
    }
} 