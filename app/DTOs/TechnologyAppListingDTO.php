<?php

namespace App\DTOs;

readonly class TechnologyAppListingDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $version,
        public array $stream,
        public string $technologyDetail
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            version: $data['version'] ?? null,
            stream: $data['stream'] ?? [],
            technologyDetail: $data['technology_detail']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'stream' => $this->stream,
            'technology_detail' => $this->technologyDetail,
        ];
    }
}
