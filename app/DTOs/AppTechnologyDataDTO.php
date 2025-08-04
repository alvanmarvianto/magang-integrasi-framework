<?php

namespace App\DTOs;

readonly class AppTechnologyDataDTO
{
    public function __construct(
        public int $appId,
        public string $appName,
        public ?string $description,
        public string $streamName,
        public ?string $appType,
        public ?string $stratification,
        public array $technologies = []
    ) {}

    public static function fromAppWithTechnologies(AppDTO $app, array $technologies): self
    {
        return new self(
            appId: $app->appId,
            appName: $app->appName,
            description: $app->description,
            streamName: $app->streamName ?? '',
            appType: $app->appType,
            stratification: $app->stratification,
            technologies: $technologies
        );
    }

    public function toArray(): array
    {
        return [
            'app_id' => $this->appId,
            'app_name' => $this->appName,
            'description' => $this->description,
            'stream_name' => $this->streamName,
            'app_type' => $this->appType,
            'stratification' => $this->stratification,
            'technology' => $this->technologies,
        ];
    }
}
