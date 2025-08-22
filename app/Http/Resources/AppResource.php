<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Group technologies by type
        $technologiesByType = $this->technologies->groupBy('type');

        return [
            'app_id' => $this->app_id,
            'app_name' => $this->app_name,
            'description' => $this->description,
            'stream_id' => $this->stream_id,
            'app_type' => $this->app_type,
            'stratification' => $this->stratification,
            'vendors' => $this->mapTechnologies($technologiesByType->get('vendors', collect())),
            'operating_systems' => $this->mapTechnologies($technologiesByType->get('operating_systems', collect())),
            'databases' => $this->mapTechnologies($technologiesByType->get('databases', collect())),
            'programming_languages' => $this->mapTechnologies($technologiesByType->get('programming_languages', collect())),
            'frameworks' => $this->mapTechnologies($technologiesByType->get('frameworks', collect())),
            'middlewares' => $this->mapTechnologies($technologiesByType->get('middlewares', collect())),
            'third_parties' => $this->mapTechnologies($technologiesByType->get('third_parties', collect())),
            'platforms' => $this->mapTechnologies($technologiesByType->get('platforms', collect())),
        ];
    }

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