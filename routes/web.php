<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AppController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminIntegrationController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', [AppController::class, 'index']);
Route::get('/integration/app/{app_id}', [AppController::class, 'appIntegration'])->name('appIntegration');
Route::get('/diagram/stream/{stream}', [AppController::class, 'vueFlowStreamIntegrations'])
    ->name('diagram.stream.integrations');

Route::prefix('technology')->group(function () {
    Route::get('/app_type/{app_type}', [TechnologyController::class, 'getAppType'])->name('technology.app_type');
    Route::get('/stratification/{stratification}', [TechnologyController::class, 'getStratification'])->name('technology.stratification');
    Route::get('/vendor/{vendor_name}', [TechnologyController::class, 'getAppByVendor'])->name('technology.vendor');
    Route::get('/os/{os_name}', [TechnologyController::class, 'getAppByOS'])->name('technology.os');
    Route::get('/database/{database_name}', [TechnologyController::class, 'getAppByDatabase'])->name('technology.database');
    Route::get('/language/{language_name}', [TechnologyController::class, 'getAppByLanguage'])->name('technology.language');
    Route::get('/third-party/{third_party_name}', [TechnologyController::class, 'getAppByThirdParty'])->name('technology.third_party');
    Route::get('/middleware/{middleware_name}', [TechnologyController::class, 'getAppByMiddleware'])->name('technology.middleware');
    Route::get('/framework/{framework_name}', [TechnologyController::class, 'getAppByFramework'])->name('technology.framework');
    Route::get('/platform/{platform_name}', [TechnologyController::class, 'getAppByPlatform'])->name('technology.platform');
    Route::get('/{app_id}', [TechnologyController::class, 'show'])->name('technology.app');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Main admin pages
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    // Integration management
    Route::get('/integrations', [AdminIntegrationController::class, 'index'])->name('integrations');
    Route::get('/integrations/create', [AdminIntegrationController::class, 'create'])->name('integrations.create');
    Route::post('/integrations', [AdminIntegrationController::class, 'store'])->name('integrations.store');
    Route::get('/integrations/{id}/edit', [AdminIntegrationController::class, 'edit'])->name('integrations.edit');
    Route::put('/integrations/{id}', [AdminIntegrationController::class, 'update'])->name('integrations.update');
    Route::delete('/integrations/{id}', [AdminIntegrationController::class, 'destroy'])->name('integrations.destroy');
    
    // Connection types management
    Route::get('/connection-types', [AdminIntegrationController::class, 'connectionTypes'])->name('connection-types');
    Route::post('/connection-types', [AdminIntegrationController::class, 'storeConnectionType'])->name('connection-types.store');
    Route::put('/connection-types/{id}', [AdminIntegrationController::class, 'updateConnectionType'])->name('connection-types.update');
    Route::delete('/connection-types/{id}', [AdminIntegrationController::class, 'destroyConnectionType'])->name('connection-types.destroy');

    // App management
    Route::get('/apps', [AdminController::class, 'apps'])->name('apps');
    Route::get('/apps/create', [AdminController::class, 'create'])->name('apps.create');
    Route::post('/apps', [AdminController::class, 'store'])->name('apps.store');
    Route::get('/apps/{app}/edit', [AdminController::class, 'edit'])->name('apps.edit');
    Route::put('/apps/{app}', [AdminController::class, 'update'])->name('apps.update');
    Route::delete('/apps/{app}', [AdminController::class, 'destroy'])->name('apps.destroy');

    // Technology management
    Route::get('/technology', [AdminController::class, 'technology'])->name('technology');
    Route::get('/technology/{type}/enum/{value}/check', [AdminController::class, 'checkEnumUsage'])->name('technology.enum.check');
    Route::post('/technology/{type}/enum', [AdminController::class, 'storeEnumValue'])->name('technology.enum.store');
    Route::put('/technology/{type}/enum/{value}', [AdminController::class, 'updateEnumValue'])->name('technology.enum.update');
    Route::delete('/technology/{type}/enum/{value}', [AdminController::class, 'deleteEnumValue'])->name('technology.enum.delete');

    // Stream management
    Route::get('/stream/{streamName}', [AdminController::class, 'showStream'])->name('diagrams.show');
    Route::post('/stream/{streamName}/layout', [AdminController::class, 'saveLayout'])->name('diagrams.save');
});
