<?php

namespace App\Http\Controllers;

use App\Services\TechnologyService;
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
        $allTechnologies = $this->technologyService->getAllTechnologyTypes();
        
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
        try {
            $appTechnologyData = $this->technologyService->getAppTechnologyData($appId);
            
            return Inertia::render('Technology/App', [
                'app' => [
                    'app_id' => $appTechnologyData->appId,
                    'app_name' => $appTechnologyData->appName,
                    'stream_name' => $appTechnologyData->streamName,
                    'is_module' => $appTechnologyData->isModule,
                ],
                'appDescription' => $appTechnologyData->description,
                'technology' => $appTechnologyData->technologies,
                'appName' => $appTechnologyData->appName,
                'streamName' => $appTechnologyData->streamName,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Technology/App', [
                'app' => null,
                'appDescription' => '',
                'technology' => [],
                'appName' => 'Aplikasi tidak ditemukan',
                'streamName' => '',
                'error' => 'Application not found',
            ]);
        }
    }

    public function getAppType(string $appType): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('app_type', $appType);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getStratification(string $stratification): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('stratification', $stratification);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByVendor(string $vendorName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('vendor', $vendorName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByOS(string $osName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('os', $osName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByDatabase(string $databaseName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('database', $databaseName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByLanguage(string $languageName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('language', $languageName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByThirdParty(string $thirdPartyName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('third_party', $thirdPartyName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByMiddleware(string $middlewareName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('middleware', $middlewareName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByFramework(string $frameworkName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('framework', $frameworkName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }

    public function getAppByPlatform(string $platformName): Response
    {
        $listingData = $this->technologyService->getTechnologyListingData('platform', $platformName);
        
        return Inertia::render('Technology/Listing', $listingData->toArray());
    }
}
