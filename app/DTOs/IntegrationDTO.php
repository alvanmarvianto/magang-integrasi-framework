<?php

namespace App\DTOs;

readonly class IntegrationDTO
{
    public function __construct(
        public ?int $integrationId,
        public int $sourceAppId,
        public int $targetAppId,
        public int $connectionTypeId,
        public ?string $inbound = null,
        public ?string $outbound = null,
        public ?string $connectionEndpoint = null,
        public string $direction = 'one_way',
        public ?AppDTO $sourceApp = null,
        public ?AppDTO $targetApp = null,
        public ?ConnectionTypeDTO $connectionType = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            integrationId: $data['integration_id'] ?? null,
            sourceAppId: $data['source_app_id'],
            targetAppId: $data['target_app_id'],
            connectionTypeId: $data['connection_type_id'],
            inbound: $data['inbound'] ?? null,
            outbound: $data['outbound'] ?? null,
            connectionEndpoint: $data['connection_endpoint'] ?? null,
            direction: $data['direction'] ?? 'one_way',
            sourceApp: isset($data['source_app']) ? AppDTO::fromArray($data['source_app']) : null,
            targetApp: isset($data['target_app']) ? AppDTO::fromArray($data['target_app']) : null,
            connectionType: isset($data['connection_type']) ? ConnectionTypeDTO::fromArray($data['connection_type']) : null
        );
    }

    public static function fromModel($integration): self
    {
        return new self(
            integrationId: $integration->integration_id,
            sourceAppId: $integration->source_app_id,
            targetAppId: $integration->target_app_id,
            connectionTypeId: $integration->connection_type_id,
            inbound: $integration->inbound,
            outbound: $integration->outbound,
            connectionEndpoint: $integration->connection_endpoint,
            direction: $integration->direction,
            sourceApp: $integration->relationLoaded('sourceApp') && $integration->sourceApp 
                ? AppDTO::fromModel($integration->sourceApp) : null,
            targetApp: $integration->relationLoaded('targetApp') && $integration->targetApp 
                ? AppDTO::fromModel($integration->targetApp) : null,
            connectionType: $integration->relationLoaded('connectionType') && $integration->connectionType 
                ? ConnectionTypeDTO::fromModel($integration->connectionType) : null
        );
    }

    public function toArray(): array
    {
        return [
            'integration_id' => $this->integrationId,
            'source_app_id' => $this->sourceAppId,
            'target_app_id' => $this->targetAppId,
            'connection_type_id' => $this->connectionTypeId,
            'inbound' => $this->inbound,
            'outbound' => $this->outbound,
            'connection_endpoint' => $this->connectionEndpoint,
            'direction' => $this->direction,
            'source_app' => $this->sourceApp?->toArray(),
            'target_app' => $this->targetApp?->toArray(),
            'connection_type' => $this->connectionType?->toArray(),
        ];
    }

    public function isBidirectional(): bool
    {
        return $this->direction === 'both_ways';
    }

    public function isUnidirectional(): bool
    {
        return $this->direction === 'one_way';
    }
}

readonly class ConnectionTypeDTO
{
    public function __construct(
        public ?int $connectionTypeId,
        public string $typeName,
        public ?string $description = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            connectionTypeId: $data['connection_type_id'] ?? null,
            typeName: $data['type_name'],
            description: $data['description'] ?? null
        );
    }

    public static function fromModel($connectionType): self
    {
        return new self(
            connectionTypeId: $connectionType->connection_type_id,
            typeName: $connectionType->type_name,
            description: $connectionType->description ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'connection_type_id' => $this->connectionTypeId,
            'type_name' => $this->typeName,
            'description' => $this->description,
        ];
    }
}