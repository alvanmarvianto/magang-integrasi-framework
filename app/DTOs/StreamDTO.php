<?php

namespace App\DTOs;

readonly class StreamDTO
{
    public function __construct(
        public ?int $streamId,
        public string $streamName,
        public array $apps = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            streamId: $data['stream_id'] ?? null,
            streamName: $data['stream_name'],
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
            apps: $apps
        );
    }

    public function toArray(): array
    {
        return [
            'stream_id' => $this->streamId,
            'stream_name' => $this->streamName,
            'apps' => array_map(
                fn($app) => $app instanceof AppDTO ? $app->toArray() : $app,
                $this->apps
            ),
        ];
    }
}