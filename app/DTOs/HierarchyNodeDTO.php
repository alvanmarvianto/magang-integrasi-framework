<?php

namespace App\DTOs;

readonly class HierarchyNodeDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $url = null,
        public ?string $stream = null,
        public ?int $appId = null,
        public array $children = []
    ) {}

    public static function createFolder(string $name, array $children = []): self
    {
        return new self(
            name: $name,
            type: 'folder',
            children: $children
        );
    }

    public static function createUrl(string $name, string $url, ?string $stream = null, ?int $appId = null): self
    {
        return new self(
            name: $name,
            type: 'url',
            url: $url,
            stream: $stream,
            appId: $appId
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'type' => $this->type,
        ];

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->stream !== null) {
            $data['stream'] = $this->stream;
        }

        if ($this->appId !== null) {
            $data['app_id'] = $this->appId;
        }

        if (!empty($this->children)) {
            $data['children'] = array_map(fn($child) => $child->toArray(), $this->children);
        }

        return $data;
    }
}
