<?php

namespace App\DTOs;

readonly class StreamDTO
{
    public function __construct(
        public ?int $streamId,
        public string $streamName,
        public ?string $description = null,
        public bool $isAllowedForDiagram = false,
        public ?int $sortOrder = null,
        public ?string $color = null,
        public array $apps = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            streamId: $data['stream_id'] ?? null,
            streamName: $data['stream_name'],
            description: $data['description'] ?? null,
            isAllowedForDiagram: $data['is_allowed_for_diagram'] ?? false,
            sortOrder: $data['sort_order'] ?? null,
            color: $data['color'] ?? null,
            apps: array_map(
                fn($app) => is_array($app) ? AppDTO::fromArray($app) : AppDTO::fromModel($app),
                $data['apps'] ?? []
            )
        );
    }

    public static function fromModel($stream): self
    {
        $apps = [];
        if ($stream->relationLoaded('apps') && $stream->apps) {
            $apps = $stream->apps->map(fn($app) => AppDTO::fromModel($app))->toArray();
        }

        return new self(
            streamId: $stream->stream_id,
            streamName: $stream->stream_name,
            description: $stream->description ?? null,
            isAllowedForDiagram: $stream->is_allowed_for_diagram ?? false,
            sortOrder: $stream->sort_order ?? null,
            color: $stream->color ?? null,
            apps: $apps
        );
    }

    public function toArray(): array
    {
        return [
            'stream_id' => $this->streamId,
            'stream_name' => $this->streamName,
            'description' => $this->description,
            'is_allowed_for_diagram' => $this->isAllowedForDiagram,
            'sort_order' => $this->sortOrder,
            'color' => $this->color,
            'apps' => array_map(
                fn($app) => $app instanceof AppDTO ? $app->toArray() : $app,
                $this->apps
            ),
        ];
    }
}