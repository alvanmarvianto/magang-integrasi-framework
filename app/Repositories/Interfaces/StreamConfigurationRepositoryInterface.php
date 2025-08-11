<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface StreamConfigurationRepositoryInterface
{
    public function getAllowedDiagramStreams(): array;
    public function getAllowedDiagramStreamsWithDetails(): Collection;
    public function isStreamAllowed(string $streamName): bool;
    public function getStreamConfiguration(string $streamName): ?object;
    public function clearCache(): void;
}
