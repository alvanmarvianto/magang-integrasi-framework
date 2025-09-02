<?php

namespace App\Http\Controllers\Traits;

use App\Models\Technology;

trait HandlesTechnologyEnums
{
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