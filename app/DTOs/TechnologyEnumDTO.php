<?php

namespace App\DTOs;

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
