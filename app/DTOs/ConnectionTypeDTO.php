<?php

namespace App\DTOs;

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
