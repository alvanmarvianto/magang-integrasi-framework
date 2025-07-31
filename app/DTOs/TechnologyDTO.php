<?php

namespace App\DTOs;

readonly class TechnologyComponentDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $version = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            version: $data['version'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
        ];
    }
}

readonly class TechnologyEnumDTO
{
    public function __construct(
        public string $type,
        public array $values
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            values: $data['values']
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'values' => $this->values,
        ];
    }
}