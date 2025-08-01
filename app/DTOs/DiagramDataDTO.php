<?php

namespace App\DTOs;

readonly class DiagramDataDTO
{
    public function __construct(
        public array $nodes,
        public array $edges,
        public ?array $layout = null,
        public ?array $config = null,
        public ?string $error = null
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
            config: $data['config'] ?? null,
            error: $data['error'] ?? null
        );
    }

    public static function createEmpty(): self
    {
        return new self(
            nodes: [],
            edges: [],
            layout: null,
            config: null,
            error: null
        );
    }

    public static function withError(string $errorMessage): self
    {
        return new self(
            nodes: [],
            edges: [],
            layout: null,
            config: null,
            error: $errorMessage
        );
    }

    public static function create(array $nodes, array $edges, ?array $layout = null, ?array $config = null): self
    {
        return new self(
            nodes: $nodes,
            edges: $edges,
            layout: $layout,
            config: $config,
            error: null
        );
    }

    public function toArray(): array
    {
        $result = [
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

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
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

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function isValid(): bool
    {
        return $this->error === null;
    }
}