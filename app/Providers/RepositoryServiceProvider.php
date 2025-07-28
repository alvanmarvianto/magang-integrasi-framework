<?php

namespace App\Providers;

use App\Repositories\AppRepository;
use App\Repositories\StreamRepository;
use App\Repositories\Interfaces\AppRepositoryInterface;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use App\Services\StreamLayoutService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AppRepositoryInterface::class, AppRepository::class);
        $this->app->bind(StreamRepositoryInterface::class, StreamRepository::class);
        $this->app->singleton(StreamLayoutService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 