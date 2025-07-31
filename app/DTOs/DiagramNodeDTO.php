<?php

namespace App\DTOs;

readonly class DiagramNodeDTO
{
    public function __construct(
        public string $id,
        public string $label,
        public int $appId,
        public string $appName,
        public string $streamName,
        public string $lingkup,
        public bool $isHomeStream,
        public bool $isParentNode = false,
        public ?array $position = null,
        public ?array $style = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            label: $data['data']['label'],
            appId: $data['data']['app_id'],
            appName: $data['data']['app_name'] ?? '',
            streamName: $data['data']['stream_name'],
            lingkup: $data['data']['lingkup'],
            isHomeStream: $data['data']['is_home_stream'],
            isParentNode: $data['data']['is_parent_node'] ?? false,
            position: $data['position'] ?? null,
            style: $data['style'] ?? null
        );
    }

    public static function createFromApp(AppDTO $app, bool $isHomeStream, bool $isParentNode = false): self
    {
        $label = $isParentNode 
            ? strtoupper($app->streamName ?? '') . ' Stream'
            : $app->appName . "\nID: " . $app->appId . "\nStream: " . strtoupper($app->streamName ?? '');

        return new self(
            id: (string) $app->appId,
            label: $label,
            appId: $app->appId ?? -1,
            appName: $app->appName,
            streamName: $app->streamName ?? '',
            lingkup: $app->streamName ?? '',
            isHomeStream: $isHomeStream,
            isParentNode: $isParentNode
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'data' => [
                'label' => $this->label,
                'app_id' => $this->appId,
                'app_name' => $this->appName,
                'stream_name' => $this->streamName,
                'lingkup' => $this->lingkup,
                'is_home_stream' => $this->isHomeStream,
                'is_parent_node' => $this->isParentNode,
            ]
        ];

        if ($this->position !== null) {
            $data['position'] = $this->position;
        }

        if ($this->style !== null) {
            $data['style'] = $this->style;
        }

        return $data;
    }
}