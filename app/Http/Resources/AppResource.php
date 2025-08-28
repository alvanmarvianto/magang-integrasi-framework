<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
{
    /**
     * Map technologies to the expected format with version from pivot
     */
    private function mapTechnologies($technologies)
    {
        return $technologies->map(function ($technology) {
            return [
                'name' => $technology->name,
                'version' => $technology->pivot->version,
            ];
        })->all();
    }
} 