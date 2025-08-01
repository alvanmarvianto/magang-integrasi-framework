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
        // Get all available technology enums using DTOs
        $allTechnologies = $this->technologyService->getAllTechnologyTypes();
        
        // Get app type and stratification enums from the apps table
        $appTypes = $this->getEnumValues('apps', 'app_type');
        $stratifications = $this->getEnumValues('apps', 'stratification');
        
        return Inertia::render('Technology/Index', [
            'technologies' => [
                'appTypes' => $appTypes,
                'stratifications' => $stratifications,
                'vendors' => $allTechnologies['vendors']->values ?? [],
                'operatingSystems' => $allTechnologies['operatingSystems']->values ?? [],
                'databases' => $allTechnologies['databases']->values ?? [],
                'languages' => $allTechnologies['languages']->values ?? [],
                'frameworks' => $allTechnologies['frameworks']->values ?? [],
                'middlewares' => $allTechnologies['middlewares']->values ?? [],
                'thirdParties' => $allTechnologies['thirdParties']->values ?? [],
                'platforms' => $allTechnologies['platforms']->values ?? [],
            ]
        ]);
    }

    public function show(int $appId): Response
    {
        /** @var App $app */
        $app = App::with(['stream'])
            ->findOrFail($appId);
        
        // Fetch normalized technology data using DTOs
        $technologyData = [
            'app_type' => $app->getAttribute('app_type'),
            'stratification' => $app->getAttribute('stratification'),
            'vendor' => $this->technologyService->getTechnologyData('technology_vendors', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'os' => $this->technologyService->getTechnologyData('technology_operating_systems', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'database' => $this->technologyService->getTechnologyData('technology_databases', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'language' => $this->technologyService->getTechnologyData('technology_programming_languages', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'third_party' => $this->technologyService->getTechnologyData('technology_third_parties', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'middleware' => $this->technologyService->getTechnologyData('technology_middlewares', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'framework' => $this->technologyService->getTechnologyData('technology_frameworks', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
            'platform' => $this->technologyService->getTechnologyData('technology_platforms', $app->getAttribute('app_id'))
                ->map(fn($dto) => $dto->toArray())->toArray(),
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
        $apps = $this->technologyService->getAppsByAttribute('app_type', $appType);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
            'technologyType' => 'App Type',
            'technologyName' => strtoupper(str_replace('_', ' ', $appType)),
            'pageTitle' => strtoupper(str_replace('_', ' ', $appType)),
            'icon' => 'fas fa-cube'
        ]);
    }

    public function getStratification(string $stratification): Response
    {
        $apps = $this->technologyService->getAppsByAttribute('stratification', $stratification);
        
        return Inertia::render('Technology/Listing', [
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
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
            'apps' => $apps->map(fn($dto) => $dto->toArray())->toArray(),
            'technologyType' => 'Platform',
            'technologyName' => $platformName,
            'pageTitle' => $platformName,
            'icon' => 'fas fa-cloud'
        ]);
    }


}
