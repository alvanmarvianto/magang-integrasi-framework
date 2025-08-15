<?php

namespace App\DTOs;

use App\Models\AppLayout;

class AppLayoutDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $appId,
        public readonly array $nodesLayout,
        public readonly array $edgesLayout,
        public readonly array $appConfig,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {}

    public static function fromModel(AppLayout $layout): self
    {
        return new self(
            id: $layout->id,
            appId: (int) $layout->app_id,
            nodesLayout: $layout->nodes_layout ?? [],
            edgesLayout: $layout->edges_layout ?? [],
            appConfig: $layout->app_config ?? [],
            createdAt: $layout->created_at?->toDateTimeString(),
            updatedAt: $layout->updated_at?->toDateTimeString()
        );
    }

    public static function forSave(int $appId, array $nodesLayout, array $edgesLayout, array $appConfig): self
    {
        return new self(
            id: null,
            appId: $appId,
            nodesLayout: $nodesLayout,
            edgesLayout: $edgesLayout,
            appConfig: $appConfig
        );
    }

    public function toArray(): array
    {
        return [
            'app_id' => $this->appId,
            'nodes_layout' => $this->nodesLayout,
            'edges_layout' => $this->edgesLayout,
            'app_config' => $this->appConfig,
        ];
    }
}
