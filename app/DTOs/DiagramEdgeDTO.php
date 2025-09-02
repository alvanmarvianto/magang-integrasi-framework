<?php

namespace App\DTOs;

readonly class DiagramEdgeDTO
{
    public function __construct(
        public string $id,
        public string $source,
        public string $target,
        public string $label,
        public string $connectionType,
        public string $color = '#000000',
        public bool $animated = false,
        public ?array $style = null,
        public ?array $labelStyle = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            source: $data['source'],
            target: $data['target'],
            label: $data['label'] ?? '',
            connectionType: $data['connection_type'] ?? '',
            color: $data['color'] ?? '#000000',
            animated: $data['animated'] ?? false,
            style: $data['style'] ?? null,
            labelStyle: $data['label_style'] ?? null
        );
    }

    public static function createFromIntegration(IntegrationDTO $integration): self
    {
        $id = $integration->sourceAppId . '-' . $integration->targetAppId;
        $label = $integration->connectionType?->typeName ?? 'Unknown';
        $color = $integration->connectionType?->color ?? '#000000';

        return new self(
            id: $id,
            source: (string) $integration->sourceAppId,
            target: (string) $integration->targetAppId,
            label: $label,
            connectionType: $integration->connectionType?->typeName ?? '',
            color: $color,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'source' => $this->source,
            'target' => $this->target,
            'label' => $this->label,
            'connection_type' => $this->connectionType,
            'color' => $this->color,
            'animated' => $this->animated,
        ];

        if ($this->style !== null) {
            $data['style'] = $this->style;
        }

        if ($this->labelStyle !== null) {
            $data['label_style'] = $this->labelStyle;
        }

        return $data;
    }
}