<?php

namespace App\Providers;

use App\Repositories\AppRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\StreamRepository;
use App\Repositories\TechnologyRepository;
use App\Repositories\ConnectionTypeRepository;
use App\Repositories\StreamLayoutRepository;
use App\Repositories\AppLayoutRepository;
use App\Repositories\ContractRepository;
use App\Repositories\ContractPeriodRepository;
use App\Repositories\StreamConfigurationRepository;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use App\Repositories\Interfaces\TechnologyRepositoryInterface;
use App\Repositories\Interfaces\ConnectionTypeRepositoryInterface;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use App\Repositories\Interfaces\AppLayoutRepositoryInterface;
use App\Repositories\Interfaces\ContractRepositoryInterface;
use App\Repositories\Interfaces\ContractPeriodRepositoryInterface;
use App\Repositories\Interfaces\StreamConfigurationRepositoryInterface;
use App\Services\StreamLayoutService;
use App\Services\StreamConfigurationService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings
     */
    private array $repositoryBindings = [
        AppRepositoryInterface::class => AppRepository::class,
        IntegrationRepositoryInterface::class => IntegrationRepository::class,
        StreamRepositoryInterface::class => StreamRepository::class,
        TechnologyRepositoryInterface::class => TechnologyRepository::class,
        ConnectionTypeRepositoryInterface::class => ConnectionTypeRepository::class,
        StreamLayoutRepositoryInterface::class => StreamLayoutRepository::class,
    AppLayoutRepositoryInterface::class => AppLayoutRepository::class,
        ContractRepositoryInterface::class => ContractRepository::class,
        ContractPeriodRepositoryInterface::class => ContractPeriodRepository::class,
        StreamConfigurationRepositoryInterface::class => StreamConfigurationRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository interfaces with their implementations
        foreach ($this->repositoryBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Register singleton services
        $this->app->singleton(StreamLayoutService::class);
        $this->app->singleton(StreamConfigurationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 