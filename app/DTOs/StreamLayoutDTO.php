<?php

namespace App\DTOs;

class StreamLayoutDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $streamName,
        public readonly array $nodesLayout,
        public readonly array $edgesLayout,
        public readonly array $streamConfig,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {}

    /**
     * Create DTO from model instance
     */
    public static function fromModel($streamLayout): self
    {
        return new self(
            id: $streamLayout->id,
            streamName: $streamLayout->stream_name,
            nodesLayout: $streamLayout->nodes_layout ?? [],
            edgesLayout: $streamLayout->edges_layout ?? [],
            streamConfig: $streamLayout->stream_config ?? [],
            createdAt: $streamLayout->created_at?->toDateTimeString(),
            updatedAt: $streamLayout->updated_at?->toDateTimeString()
        );
    }

    /**
     * Create DTO with only essential data for creating/updating
     */
    public static function forSave(
        string $streamName,
        array $nodesLayout,
        array $edgesLayout,
        array $streamConfig
    ): self {
        return new self(
            id: null,
            streamName: $streamName,
            nodesLayout: $nodesLayout,
            edgesLayout: $edgesLayout,
            streamConfig: $streamConfig
        );
    }

    /**
     * Convert to array for model creation/update
     */
    public function toArray(): array
    {
        return [
            'stream_name' => $this->streamName,
            'nodes_layout' => $this->nodesLayout,
            'edges_layout' => $this->edgesLayout,
            'stream_config' => $this->streamConfig,
        ];
    }
}
