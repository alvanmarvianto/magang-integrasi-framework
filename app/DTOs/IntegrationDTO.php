<?php

namespace App\DTOs;

readonly class IntegrationDTO
{
    /**
     * connections: array of items
     * - connection_type_id: int
     * - source_inbound?: string|null
     * - source_outbound?: string|null
     * - target_inbound?: string|null
     * - target_outbound?: string|null
     * - connection_type?: ConnectionTypeDTO (optional, when present)
     */
    public function __construct(
        public ?int $integrationId,
        public int $sourceAppId,
        public int $targetAppId,
        public array $connections = [],
        public ?AppDTO $sourceApp = null,
        public ?AppDTO $targetApp = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $connections = array_values(array_map(function ($item) {
            return [
                'connection_type_id' => $item['connection_type_id'] ?? null,
                'source_inbound' => $item['source_inbound'] ?? null,
                'source_outbound' => $item['source_outbound'] ?? null,
                'target_inbound' => $item['target_inbound'] ?? null,
                'target_outbound' => $item['target_outbound'] ?? null,
                // optional embedded connection_type for display
                'connection_type' => isset($item['connection_type'])
                    ? ConnectionTypeDTO::fromArray($item['connection_type'])
                    : null,
            ];
        }, $data['connections'] ?? []));

        return new self(
            integrationId: $data['integration_id'] ?? null,
            sourceAppId: $data['source_app_id'],
            targetAppId: $data['target_app_id'],
            connections: $connections,
            sourceApp: isset($data['source_app']) ? AppDTO::fromArray($data['source_app']) : null,
            targetApp: isset($data['target_app']) ? AppDTO::fromArray($data['target_app']) : null,
        );
    }

    public static function fromModel($integration): self
    {
        $connections = [];
        if ($integration->relationLoaded('connections')) {
            $connections = $integration->connections->map(function ($conn) {
                return [
                    'connection_type_id' => $conn->connection_type_id,
                    'source_inbound' => $conn->source_inbound,
                    'source_outbound' => $conn->source_outbound,
                    'target_inbound' => $conn->target_inbound,
                    'target_outbound' => $conn->target_outbound,
                    'connection_type' => $conn->relationLoaded('connectionType') && $conn->connectionType
                        ? ConnectionTypeDTO::fromModel($conn->connectionType)
                        : null,
                ];
            })->toArray();
        }

        return new self(
            integrationId: $integration->integration_id,
            sourceAppId: $integration->source_app_id,
            targetAppId: $integration->target_app_id,
            connections: $connections,
            sourceApp: $integration->relationLoaded('sourceApp') && $integration->sourceApp
                ? AppDTO::fromModel($integration->sourceApp) : null,
            targetApp: $integration->relationLoaded('targetApp') && $integration->targetApp
                ? AppDTO::fromModel($integration->targetApp) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'integration_id' => $this->integrationId,
            'source_app_id' => $this->sourceAppId,
            'target_app_id' => $this->targetAppId,
            'connections' => array_map(function ($item) {
                return [
                    'connection_type_id' => $item['connection_type_id'] ?? null,
                    'source_inbound' => $item['source_inbound'] ?? null,
                    'source_outbound' => $item['source_outbound'] ?? null,
                    'target_inbound' => $item['target_inbound'] ?? null,
                    'target_outbound' => $item['target_outbound'] ?? null,
                    'connection_type' => isset($item['connection_type']) && $item['connection_type'] instanceof ConnectionTypeDTO
                        ? $item['connection_type']->toArray()
                        : (is_array($item['connection_type'] ?? null) ? $item['connection_type'] : null),
                ];
            }, $this->connections),
            'source_app' => $this->sourceApp?->toArray(),
            'target_app' => $this->targetApp?->toArray(),
        ];
    }
}