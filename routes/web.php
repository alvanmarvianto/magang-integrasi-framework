<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppController;
use App\Http\Controllers\DiagramController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\AppController as AdminAppController;
use App\Http\Controllers\Admin\TechnologyController as AdminTechnologyController;
use App\Http\Controllers\Admin\ConnectionTypeController;
use App\Http\Controllers\Admin\AdminDiagramController;

Route::get('/', [AppController::class, 'index'])->name('index');
Route::get('/integration/app/{app_id}', [AppController::class, 'appIntegration'])->name('appIntegration');
Route::get('/integration/stream/{stream}', [DiagramController::class, 'show'])
    ->name('integrations.stream');

Route::prefix('technology')->as('technology.')->group(function () {
    Route::get('/', [TechnologyController::class, 'index'])->name('index');
    Route::get('/app_type/{app_type}', [TechnologyController::class, 'getAppType'])->name('app_type');
    Route::get('/stratification/{stratification}', [TechnologyController::class, 'getStratification'])->name('stratification');
    Route::get('/vendor/{vendor_name}', [TechnologyController::class, 'getAppByVendor'])->name('vendor');
    Route::get('/os/{os_name}', [TechnologyController::class, 'getAppByOS'])->name('os');
    Route::get('/database/{database_name}', [TechnologyController::class, 'getAppByDatabase'])->name('database');
    Route::get('/language/{language_name}', [TechnologyController::class, 'getAppByLanguage'])->name('language');
    Route::get('/third-party/{third_party_name}', [TechnologyController::class, 'getAppByThirdParty'])->name('third_party');
    Route::get('/middleware/{middleware_name}', [TechnologyController::class, 'getAppByMiddleware'])->name('middleware');
    Route::get('/framework/{framework_name}', [TechnologyController::class, 'getAppByFramework'])->name('framework');
    Route::get('/platform/{platform_name}', [TechnologyController::class, 'getAppByPlatform'])->name('platform');
    Route::get('/{app_id}', [TechnologyController::class, 'show'])->name('app');
});

// Admin routes
Route::prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');

    // Integration management
    Route::prefix('integrations')->as('integrations.')->group(function () {
        Route::get('/', [AdminIntegrationController::class, 'index'])->name('index');
        Route::get('/create', [AdminIntegrationController::class, 'create'])->name('create');
        Route::post('/', [AdminIntegrationController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminIntegrationController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminIntegrationController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminIntegrationController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/switch', [AdminIntegrationController::class, 'switchSourceTarget'])->name('switch');
    });

    // Connection types management
    Route::prefix('connection-types')->as('connection-types.')->group(function () {
        Route::get('/', [ConnectionTypeController::class, 'index'])->name('index');
        Route::post('/', [ConnectionTypeController::class, 'store'])->name('store');
        Route::put('/{id}', [ConnectionTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ConnectionTypeController::class, 'destroy'])->name('destroy');
    });

    // App management
    Route::prefix('apps')->as('apps.')->group(function () {
        Route::get('/', [AdminAppController::class, 'index'])->name('index');
        Route::get('/create', [AdminAppController::class, 'create'])->name('create');
        Route::post('/', [AdminAppController::class, 'store'])->name('store');
        Route::get('/{app}/edit', [AdminAppController::class, 'edit'])->name('edit');
        Route::put('/{app}', [AdminAppController::class, 'update'])->name('update');
        Route::delete('/{app}', [AdminAppController::class, 'destroy'])->name('destroy');
    });

    // Technology management
    Route::prefix('technology')->as('technology.')->group(function () {
        Route::get('/', [AdminTechnologyController::class, 'index'])->name('index');
        Route::get('/{type}/enum/{value}/check', [AdminTechnologyController::class, 'checkEnumUsage'])->name('enum.check');
        Route::post('/{type}/enum', [AdminTechnologyController::class, 'storeEnumValue'])->name('enum.store');
        Route::put('/{type}/enum/{value}', [AdminTechnologyController::class, 'updateEnumValue'])->name('enum.update');
        Route::delete('/{type}/enum/{value}', [AdminTechnologyController::class, 'deleteEnumValue'])->name('enum.delete');
    });

    // Stream management
    Route::prefix('stream')->as('diagrams.')->group(function () {
        Route::get('/{streamName}', [AdminDiagramController::class, 'show'])->name('show');
        Route::post('/{streamName}/layout', [AdminDiagramController::class, 'saveLayout'])->name('save');
        Route::get('/{streamName}/refresh', [AdminDiagramController::class, 'refreshLayout'])->name('refresh');
    });
});