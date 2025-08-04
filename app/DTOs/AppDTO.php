<?php

namespace App\DTOs;

use App\DTOs\TechnologyComponentDTO;

readonly class AppDTO
{
    public function __construct(
        public ?int $appId,
        public string $appName,
        public ?string $description,
        public int $streamId,
        public ?string $appType,
        public ?string $stratification,
        public ?string $streamName = null,
        public array $technologyComponents = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            appId: $data['app_id'] ?? null,
            appName: $data['app_name'],
            description: $data['description'] ?? null,
            streamId: $data['stream_id'],
            appType: $data['app_type'] ?? null,
            stratification: $data['stratification'] ?? null,
            streamName: $data['stream_name'] ?? null,
            technologyComponents: $data['technology_components'] ?? []
        );
    }

    public static function fromModel($app): self
    {
        return new self(
            appId: $app->app_id,
            appName: $app->app_name,
            description: $app->description,
            streamId: $app->stream_id,
            appType: $app->app_type,
            stratification: $app->stratification,
            streamName: $app->stream?->stream_name,
            technologyComponents: self::extractTechnologyComponents($app)
        );
    }

    public function toArray(): array
    {
        return [
            'app_id' => $this->appId,
            'app_name' => $this->appName,
            'description' => $this->description,
            'stream_id' => $this->streamId,
            'app_type' => $this->appType,
            'stratification' => $this->stratification,
            'stream_name' => $this->streamName,
            'technology_components' => $this->technologyComponents,
        ];
    }

    private static function extractTechnologyComponents($app): array
    {
        $components = [];
        
        $relationMappings = [
            'vendors' => 'vendors',
            'operating_systems' => 'operatingSystems',
            'databases' => 'databases',
            'programming_languages' => 'programmingLanguages',
            'frameworks' => 'frameworks',
            'middlewares' => 'middlewares',
            'third_parties' => 'thirdParties',
            'platforms' => 'platforms',
        ];

        foreach ($relationMappings as $key => $relation) {
            if ($app->relationLoaded($relation) && $app->$relation) {
                $components[$key] = $app->$relation->map(function ($item) {
                    return new TechnologyComponentDTO(
                        id: $item->getKey(),
                        name: $item->name,
                        version: $item->version ?? null
                    );
                })->toArray();
            }
        }

        return $components;
    }
}