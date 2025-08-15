<?php

namespace App\DTOs;

use App\Models\AppIntegrationFunction;

readonly class AppIntegrationFunctionDTO
{
    public function __construct(
        public ?int $id,
        public int $appId,
        public int $integrationId,
        public string $functionName,
    ) {}

    public static function fromModel(AppIntegrationFunction $model): self
    {
        return new self(
            id: method_exists($model, 'getKey') ? $model->getKey() : null,
            appId: (int) $model->app_id,
            integrationId: (int) $model->integration_id,
            functionName: (string) $model->function_name,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            appId: (int) $data['app_id'],
            integrationId: (int) $data['integration_id'],
            functionName: (string) $data['function_name'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'app_id' => $this->appId,
            'integration_id' => $this->integrationId,
            'function_name' => $this->functionName,
        ];
    }
}
