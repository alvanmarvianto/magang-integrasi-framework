<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Services\TechnologyService;
use App\Http\Resources\AppResource;
use App\Http\Controllers\Traits\HandlesTechnologyEnums;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    use HandlesTechnologyEnums;
    
    protected TechnologyService $technologyService;

    public function __construct(TechnologyService $technologyService)
    {
        $this->technologyService = $technologyService;
    }

    public function index(): Response
    {
        // Get all available technology enums
        $technologies = $this->getTechnologyEnums();
        
        // Get app type and stratification enums from the apps table
        $appTypes = $this->getEnumValues('apps', 'app_type');
        $stratifications = $this->getEnumValues('apps', 'stratification');
        
        return Inertia::render('Technology/Index', [
            'technologies' => [
                'appTypes' => $appTypes,
                'stratifications' => $stratifications,
                'vendors' => $technologies['vendors'],
                'operatingSystems' => $technologies['operatingSystems'],
                'databases' => $technologies['databases'],
                'languages' => $technologies['languages'],
                'frameworks' => $technologies['frameworks'],
                'middlewares' => $technologies['middlewares'],
                'thirdParties' => $technologies['thirdParties'],
                'platforms' => $technologies['platforms'],
            ]
        ]);
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

        return Inertia::render('Technology/App', [
            'app' => new AppResource($app),
            'appDescription' => $app->getAttribute('description'),
            'technology' => $technologyData,
            'appName' => $app->getAttribute('app_name'),
            'streamName' => $app->stream?->stream_name,
        ]);
    }

    public function getAppType(string $appType): Response
    {
        $apps = App::with('stream')->where('app_type', $appType)->get();
        
        $formattedApps = $apps->map(function ($app) use ($appType) {
            return [
                'id' => $app->getAttribute('app_id'),
                'name' => $app->getAttribute('app_name'),
                'description' => $app->getAttribute('description'),
                'version' => null,
                'stream' => [
                    'id' => $app->stream?->stream_id,
                    'name' => $app->stream?->stream_name
                ],
                'technology_detail' => strtoupper(str_replace('_', ' ', $appType))
            ];
        })->toArray();
        
        return Inertia::render('Technology/Listing', [
            'apps' => $formattedApps,
            'technologyType' => 'App Type',
            'technologyName' => strtoupper(str_replace('_', ' ', $appType)),
            'pageTitle' => strtoupper(str_replace('_', ' ', $appType)),
            'icon' => 'fas fa-cube'
        ]);
    }

    public function getStratification(string $stratification): Response
    {
        $apps = App::with('stream')->where('stratification', $stratification)->get();
        
        $formattedApps = $apps->map(function ($app) use ($stratification) {
            return [
                'id' => $app->getAttribute('app_id'),
                'name' => $app->getAttribute('app_name'),
                'description' => $app->getAttribute('description'),
                'version' => null,
                'stream' => [
                    'id' => $app->stream?->stream_id,
                    'name' => $app->stream?->stream_name
                ],
                'technology_detail' => strtoupper(str_replace('_', ' ', $stratification))
            ];
        })->toArray();
        
        return Inertia::render('Technology/Listing', [
            'apps' => $formattedApps,
            'technologyType' => 'Stratification',
            'technologyName' => strtoupper(str_replace('_', ' ', $stratification)),
            'pageTitle' => strtoupper(str_replace('_', ' ', $stratification)),
            'icon' => 'fas fa-layer-group'
        ]);
    }

    public function getAppByVendor(string $vendorName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_vendors', $vendorName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Vendor',
            'technologyName' => $vendorName,
            'pageTitle' => $vendorName,
            'icon' => 'fas fa-building'
        ]);
    }

    public function getAppByOS(string $osName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_operating_systems', $osName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Operating System',
            'technologyName' => $osName,
            'pageTitle' => $osName,
            'icon' => 'fas fa-desktop'
        ]);
    }

    public function getAppByDatabase(string $databaseName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_databases', $databaseName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Database',
            'technologyName' => $databaseName,
            'pageTitle' => $databaseName,
            'icon' => 'fas fa-database'
        ]);
    }

    public function getAppByLanguage(string $languageName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_programming_languages', $languageName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Programming Language',
            'technologyName' => $languageName,
            'pageTitle' => $languageName,
            'icon' => 'fas fa-code'
        ]);
    }

    public function getAppByThirdParty(string $thirdPartyName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_third_parties', $thirdPartyName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Third Party',
            'technologyName' => $thirdPartyName,
            'pageTitle' => $thirdPartyName,
            'icon' => 'fas fa-plug'
        ]);
    }

    public function getAppByMiddleware(string $middlewareName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_middlewares', $middlewareName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Middleware',
            'technologyName' => $middlewareName,
            'pageTitle' => $middlewareName,
            'icon' => 'fas fa-exchange-alt'
        ]);
    }

    public function getAppByFramework(string $frameworkName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_frameworks', $frameworkName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Framework',
            'technologyName' => $frameworkName,
            'pageTitle' => $frameworkName,
            'icon' => 'fas fa-tools'
        ]);
    }

    public function getAppByPlatform(string $platformName): Response
    {
        $apps = $this->technologyService->getAppByCondition('technology_platforms', $platformName);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps,
            'technologyType' => 'Platform',
            'technologyName' => $platformName,
            'pageTitle' => $platformName,
            'icon' => 'fas fa-cloud'
        ]);
    }
}
