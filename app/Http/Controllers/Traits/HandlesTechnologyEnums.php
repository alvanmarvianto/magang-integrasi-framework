<?php

namespace App\Http\Controllers\Traits;

use App\Models\Technology;
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
            'vendors' => Technology::where('type', 'vendors')->pluck('name')->toArray(),
            'operatingSystems' => Technology::where('type', 'operating_systems')->pluck('name')->toArray(),
            'databases' => Technology::where('type', 'databases')->pluck('name')->toArray(),
            'languages' => Technology::where('type', 'programming_languages')->pluck('name')->toArray(),
            'frameworks' => Technology::where('type', 'frameworks')->pluck('name')->toArray(),
            'middlewares' => Technology::where('type', 'middlewares')->pluck('name')->toArray(),
            'thirdParties' => Technology::where('type', 'third_parties')->pluck('name')->toArray(),
            'platforms' => Technology::where('type', 'platforms')->pluck('name')->toArray(),
        ];
    }
} 