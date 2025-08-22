<?php

namespace App\DTOs;

readonly class TechnologyComponentDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $type,
        public ?string $version = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            type: $data['type'],
            version: $data['version'] ?? null
        );
    }

    public static function fromModel($technology, $version = null): self
    {
        return new self(
            id: $technology->id,
            name: $technology->name,
            type: $technology->type,
            version: $version
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'version' => $this->version,
        ];
    }
}
