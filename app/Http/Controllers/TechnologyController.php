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

        return $results->map(function ($item) {
            $display = $item->name;
            if ($item->version) {
                $display .= ' ' . $item->version;
            }
            return $display;
        })->toArray();
    }
}
