<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Services\TechnologyService;
use App\Http\Resources\AppResource;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    protected TechnologyService $technologyService;

    public function __construct(TechnologyService $technologyService)
    {
        $this->technologyService = $technologyService;
    }

    public function show(int $appId): Response
    {
        /** @var App $app */
        $app = App::with(['stream'])
            ->findOrFail($appId);
        
        // Fetch normalized technology data
        $technologyData = [
            'app_type' => $app->getAttribute('app_type'),
            'stratification' => $app->getAttribute('stratification'),
            'vendor' => $this->technologyService->getTechnologyData('technology_vendors', $app->getAttribute('app_id')),
            'os' => $this->technologyService->getTechnologyData('technology_operating_systems', $app->getAttribute('app_id')),
            'database' => $this->technologyService->getTechnologyData('technology_databases', $app->getAttribute('app_id')),
            'language' => $this->technologyService->getTechnologyData('technology_programming_languages', $app->getAttribute('app_id')),
            'third_party' => $this->technologyService->getTechnologyData('technology_third_parties', $app->getAttribute('app_id')),
            'middleware' => $this->technologyService->getTechnologyData('technology_middlewares', $app->getAttribute('app_id')),
            'framework' => $this->technologyService->getTechnologyData('technology_frameworks', $app->getAttribute('app_id')),
            'platform' => $this->technologyService->getTechnologyData('technology_platforms', $app->getAttribute('app_id')),
        ];

        return Inertia::render('Technology', [
            'app' => new AppResource($app),
            'appDescription' => $app->getAttribute('description'),
            'technology' => $technologyData,
            'appName' => $app->getAttribute('app_name'),
            'streamName' => $app->stream?->stream_name,
        ]);
    }

    public function getAppType(string $appType): JsonResponse
    {
        $apps = App::where('app_type', $appType)->get();
        return response()->json($apps);
    }

    public function getStratification(string $stratification): JsonResponse
    {
        $apps = App::where('stratification', $stratification)->get();
        return response()->json($apps);
    }

    public function getAppByVendor(string $vendorName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_vendors', $vendorName));
    }

    public function getAppByOS(string $osName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_operating_systems', $osName));
    }

    public function getAppByDatabase(string $databaseName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_databases', $databaseName));
    }

    public function getAppByLanguage(string $languageName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_programming_languages', $languageName));
    }

    public function getAppByThirdParty(string $thirdPartyName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_third_parties', $thirdPartyName));
    }

    public function getAppByMiddleware(string $middlewareName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_middlewares', $middlewareName));
    }

    public function getAppByFramework(string $frameworkName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_frameworks', $frameworkName));
    }

    public function getAppByPlatform(string $platformName): JsonResponse
    {
        return response()->json($this->technologyService->getAppByCondition('technology_platforms', $platformName));
    }
}
