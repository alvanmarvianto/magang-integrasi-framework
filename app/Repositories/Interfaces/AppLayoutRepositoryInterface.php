<?php

namespace App\Repositories\Interfaces;

use App\DTOs\AppLayoutDTO;

interface AppLayoutRepositoryInterface
{
    public function findByAppId(int $appId): ?AppLayoutDTO;
    public function saveLayoutByAppId(int $appId, array $nodesLayout, array $edgesLayout, array $appConfig): AppLayoutDTO;
}
