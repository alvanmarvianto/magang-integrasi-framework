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
    public bool $isModule = false,
        public ?string $streamName = null,
    public array $technologyComponents = [],
    public array $integrationFunctions = []
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
            isModule: (bool)($data['is_module'] ?? false),
            streamName: $data['stream_name'] ?? null,
            technologyComponents: $data['technology_components'] ?? [],
            integrationFunctions: $data['integration_functions'] ?? []
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
            isModule: (bool)($app->is_module ?? false),
            streamName: $app->stream?->stream_name,
            technologyComponents: self::extractTechnologyComponents($app),
            integrationFunctions: self::extractIntegrationFunctions($app)
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
            'is_module' => $this->isModule,
            'technology_components' => $this->technologyComponents,
            'integration_functions' => $this->integrationFunctions,
        ];
    }

    private static function extractTechnologyComponents($app): array
    {
        $components = [];
        
        // Use the new technology relationship structure
        if ($app->relationLoaded('appTechnologies') && $app->appTechnologies) {
            $groupedTechnologies = $app->appTechnologies->groupBy(function ($appTech) {
                return $appTech->technology->type;
            });

            foreach ($groupedTechnologies as $type => $appTechs) {
                $techKey = self::mapTypeToKey($type);
                $components[$techKey] = $appTechs->map(function ($appTech) {
                    return new TechnologyComponentDTO(
                        id: $appTech->technology->id,
                        name: $appTech->technology->name,
                        type: $appTech->technology->type,
                        version: $appTech->version
                    );
                })->toArray();
            }
        }

        return $components;
    }

    private static function mapTypeToKey(string $type): string
    {
        return match ($type) {
            'vendors' => 'vendors',
            'operating_systems' => 'operating_systems',
            'databases' => 'databases',
            'programming_languages' => 'programming_languages',
            'frameworks' => 'frameworks',
            'middlewares' => 'middlewares',
            'third_parties' => 'third_parties',
            'platforms' => 'platforms',
            default => $type,
        };
    }

    private static function extractIntegrationFunctions($app): array
    {
        if (!$app->relationLoaded('integrationFunctions')) {
            return [];
        }

        return $app->integrationFunctions->map(function ($item) {
            return [
                'id' => $item->getKey(),
                'app_id' => $item->app_id,
                'integration_id' => $item->integration_id,
                'function_name' => $item->function_name,
            ];
        })->toArray();
    }
}