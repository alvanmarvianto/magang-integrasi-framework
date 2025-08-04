<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class AppIntegrationDataDTO
{
    public function __construct(
        public int $appId,
        public string $appName,
        public string $streamName,
        public array $children = []
    ) {}

    public static function fromAppWithIntegrations(AppDTO $app, Collection $integrations): self
    {
        $children = $integrations->map(function ($integration) {
            return [
                'name' => $integration['app_name'],
                'lingkup' => $integration['stream_name'] ?? null,
                'link' => $integration['connection_type'] ?? null,
                'app_id' => $integration['app_id'],
            ];
        })->toArray();

        return new self(
            appId: $app->appId,
            appName: $app->appName,
            streamName: $app->streamName ?? '',
            children: $children
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->appName,
            'app_id' => $this->appId,
            'lingkup' => $this->streamName,
            'children' => $this->children,
        ];
    }
}
