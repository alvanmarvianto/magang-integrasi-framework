<?php

namespace App\Repositories;

use App\Models\App;
use App\Repositories\Interfaces\AppRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AppRepository implements AppRepositoryInterface
{
    public function getPaginatedApps(string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = App::with('stream')->orderBy('app_name');

        if ($search) {
            $query->where('app_name', 'like', '%' . $search . '%');
        }

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id): ?App
    {
        return App::with([
            'vendors',
            'operatingSystems',
            'databases',
            'programmingLanguages',
            'frameworks',
            'middlewares',
            'thirdParties',
            'platforms',
        ])->find($id);
    }

    public function createWithTechnology(array $data): App
    {
        $app = App::create([
            'app_name' => $data['app_name'],
            'description' => $data['description'],
            'stream_id' => $data['stream_id'],
            'app_type' => $data['app_type'],
            'stratification' => $data['stratification'],
        ]);

        $this->saveTechnologyComponents($app, $data);

        return $app;
    }

    public function updateWithTechnology(App $app, array $data): bool
    {
        $updated = $app->update([
            'app_name' => $data['app_name'],
            'description' => $data['description'],
            'stream_id' => $data['stream_id'],
            'app_type' => $data['app_type'],
            'stratification' => $data['stratification'],
        ]);

        if ($updated) {
            $this->saveTechnologyComponents($app, $data);
        }

        return $updated;
    }

    public function delete(App $app): bool
    {
        return $app->delete();
    }

    public function saveTechnologyComponents(App $app, array $data): void
    {
        $saveComponents = function($items, $relation) use ($app) {
            $app->$relation()->delete(); // Clear existing
            foreach ($items as $item) {
                $app->$relation()->create([
                    'name' => $item['name'],
                    'version' => $item['version'] ?? null,
                ]);
            }
        };

        // Save each type of component
        if (!empty($data['vendors'])) {
            $saveComponents($data['vendors'], 'vendors');
        }
        if (!empty($data['operating_systems'])) {
            $saveComponents($data['operating_systems'], 'operatingSystems');
        }
        if (!empty($data['databases'])) {
            $saveComponents($data['databases'], 'databases');
        }
        if (!empty($data['languages'])) {
            $saveComponents($data['languages'], 'programmingLanguages');
        }
        if (!empty($data['frameworks'])) {
            $saveComponents($data['frameworks'], 'frameworks');
        }
        if (!empty($data['middlewares'])) {
            $saveComponents($data['middlewares'], 'middlewares');
        }
        if (!empty($data['third_parties'])) {
            $saveComponents($data['third_parties'], 'thirdParties');
        }
        if (!empty($data['platforms'])) {
            $saveComponents($data['platforms'], 'platforms');
        }
    }
} 