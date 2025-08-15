<?php

namespace App\Repositories\Interfaces;

use App\DTOs\AppLayoutDTO;
use Illuminate\Support\Collection;

interface AppLayoutRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?AppLayoutDTO;
    public function findByAppId(int $appId): ?AppLayoutDTO;
    public function create(AppLayoutDTO $dto): AppLayoutDTO;
    public function update(int $id, AppLayoutDTO $dto): ?AppLayoutDTO;
    public function saveLayoutByAppId(int $appId, array $nodesLayout, array $edgesLayout, array $appConfig): AppLayoutDTO;
    public function delete(int $id): bool;
    public function getStatistics(): array;
}
