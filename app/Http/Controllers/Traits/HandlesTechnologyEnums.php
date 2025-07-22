<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

trait HandlesTechnologyEnums
{
    protected function getEnumValues(string $table, string $column = 'name'): array
    {
        try {
            $columnInfo = DB::select("
                SELECT COLUMN_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
            ", [$table, $column]);

            if (empty($columnInfo)) {
                return [];
            }

            $enumStr = $columnInfo[0]->COLUMN_TYPE;
            preg_match('/^enum\((.*)\)$/', $enumStr, $matches);

            if (empty($matches[1])) {
                return [];
            }

            $values = [];
            foreach(explode(',', $matches[1]) as $value) {
                $values[] = trim($value, "'\"");
            }

            return $values;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getTechnologyEnums(): array
    {
        return [
            'vendors' => $this->getEnumValues('technology_vendors'),
            'operatingSystems' => $this->getEnumValues('technology_operating_systems'),
            'databases' => $this->getEnumValues('technology_databases'),
            'languages' => $this->getEnumValues('technology_programming_languages'),
            'frameworks' => $this->getEnumValues('technology_frameworks'),
            'middlewares' => $this->getEnumValues('technology_middlewares'),
            'thirdParties' => $this->getEnumValues('technology_third_parties'),
            'platforms' => $this->getEnumValues('technology_platforms'),
        ];
    }
} 