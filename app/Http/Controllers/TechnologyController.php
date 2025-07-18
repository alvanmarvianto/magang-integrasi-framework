<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Technology;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    public function show($appId): Response
    {
        $app = App::with(['stream', 'technology'])
            ->findOrFail($appId);

        $technology = $app->technology;
        
        // Fetch normalized technology data
        $technologyData = null;
        if ($technology) {
            $technologyData = [
                'app_type' => $technology->app_type,
                'stratification' => $technology->stratification,
                'vendor' => $this->getTechnologyData('technology_vendors', $technology->technology_id),
                'os' => $this->getTechnologyData('technology_operating_systems', $technology->technology_id),
                'database' => $this->getTechnologyData('technology_databases', $technology->technology_id),
                'language' => $this->getTechnologyData('technology_programming_languages', $technology->technology_id),
                'third_party' => $this->getTechnologyData('technology_third_parties', $technology->technology_id),
                'middleware' => $this->getTechnologyData('technology_middlewares', $technology->technology_id),
                'framework' => $this->getTechnologyData('technology_frameworks', $technology->technology_id),
                'platform' => $this->getTechnologyData('technology_platforms', $technology->technology_id),
            ];
        }

        return Inertia::render('Technology', [
            'app' => $app,
            'appDescription' => $app->description,
            'technology' => $technologyData,
            'appName' => $app->app_name,
            'streamName' => $app->stream?->stream_name,
        ]);
    }

    private function getTechnologyData($tableName, $technologyId)
    {
        $results = DB::table($tableName)
            ->where('technology_id', $technologyId)
            ->get(['name', 'version']);

        return $results->map(function (object $item) {
            return [
            'name' => $item->name,
            'version' => $item->version,
            ];
        })->toArray();
    }

    public function getAppByVendor($vendorName)
    {
        return $this->getAppByCondition('technology_vendors', $vendorName);
    }

    public function getAppByOS($osName): array
    {
        return $this->getAppByCondition('technology_operating_systems', $osName);
    }

    public function getAppByDatabase($databaseName): array
    {
        return $this->getAppByCondition('technology_databases', $databaseName);
    }

    public function getAppByLanguage($languageName): array
    {
        return $this->getAppByCondition('technology_programming_languages', $languageName);
    }

    public function getAppByThirdParty($thirdPartyName): array
    {
        return $this->getAppByCondition('technology_third_parties', $thirdPartyName);
    }

    public function getAppByMiddleware($middlewareName): array
    {
        return $this->getAppByCondition('technology_middlewares', $middlewareName);
    }

    public function getAppByFramework($frameworkName): array
    {
        return $this->getAppByCondition('technology_frameworks', $frameworkName);
    }

    public function getAppByPlatform($platformName): array
    {
        return $this->getAppByCondition('technology_platforms', $platformName);
    }

    private function getAppByCondition(string $tableName, string $name): array
    {
        $technologies = Technology::whereIn('technology_id', function ($query) use ($tableName, $name) {
            $query->select('technology_id')
                ->from($tableName)
                ->where('name', $name);
            })
            ->get();

        $appIds = $technologies->pluck('app_id')->unique()->toArray();

        $apps = App::whereIn('app_id', $appIds)
            ->get();

        return $apps->map(function ($app) use ($tableName) {
            $technologyId = $app->technology?->technology_id ?? $app->technology_id;
            $version = optional(
                DB::table($tableName)
                    ->where('technology_id', $technologyId)
                    ->first()
            )->version;

            return [
                'id' => $app->app_id,
                'name' => $app->app_name,
                'description' => $app->description,
                'version' => $version
            ];
        })->toArray();
    }
}
