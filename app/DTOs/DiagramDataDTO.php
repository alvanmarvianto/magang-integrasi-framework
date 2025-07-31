<?php

namespace App\DTOs;

readonly class DiagramDataDTO
{
    public function __construct(
        public array $nodes,
        public array $edges,
        public ?array $layout = null,
        public ?array $config = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nodes: array_map(
                fn($node) => $node instanceof DiagramNodeDTO ? $node : DiagramNodeDTO::fromArray($node),
                $data['nodes'] ?? []
            ),
            edges: array_map(
                fn($edge) => $edge instanceof DiagramEdgeDTO ? $edge : DiagramEdgeDTO::fromArray($edge),
                $data['edges'] ?? []
            ),
            layout: $data['layout'] ?? null,
            config: $data['config'] ?? null
        );
    }

    public static function createEmpty(): self
    {
        return new self(
            nodes: [],
            edges: [],
            layout: null,
            config: null
        );
    }

    public function toArray(): array
    {
        return [
            'nodes' => array_map(
                fn($node) => $node instanceof DiagramNodeDTO ? $node->toArray() : $node,
                $this->nodes
            ),
            'edges' => array_map(
                fn($edge) => $edge instanceof DiagramEdgeDTO ? $edge->toArray() : $edge,
                $this->edges
            ),
            'layout' => $this->layout,
            'config' => $this->config,
        ];
    }

    public function getNodeCount(): int
    {
        return count($this->nodes);
    }

    public function getEdgeCount(): int
    {
        return count($this->edges);
    }

    public function hasNodes(): bool
    {
        return !empty($this->nodes);
    }

    public function hasEdges(): bool
    {
        return !empty($this->edges);
    }
}