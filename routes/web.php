<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppController;
use App\Http\Controllers\DiagramController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\Admin\Controller as AdminController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\AppController as AdminAppController;
use App\Http\Controllers\Admin\TechnologyController as AdminTechnologyController;
use App\Http\Controllers\Admin\ConnectionTypeController;
use App\Http\Controllers\Admin\DiagramController as AdminDiagramController;
use App\Http\Controllers\Admin\ContractController as AdminContractController;

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

// User contract routes
Route::prefix('contract')->as('contract.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::get('/{app_id}', [ContractController::class, 'redirectToFirstContract'])->name('app');
    Route::get('/{app_id}/{contract_id}', [ContractController::class, 'show'])->name('show');
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
    });

    // Connection types management
    Route::prefix('connection-types')->as('connection-types.')->group(function () {
        Route::get('/', [ConnectionTypeController::class, 'index'])->name('index');
        Route::post('/', [ConnectionTypeController::class, 'store'])->name('store');
        Route::put('/{id}', [ConnectionTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ConnectionTypeController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/check', [ConnectionTypeController::class, 'checkUsage'])->name('check');
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
    Route::get('/{streamName}/app-function', [AdminDiagramController::class, 'getAppFunctionVueFlowData'])->name('app-function');
        Route::post('/{streamName}/layout', [AdminDiagramController::class, 'saveLayout'])->name('save');
        Route::get('/{streamName}/refresh', [AdminDiagramController::class, 'refreshLayout'])->name('refresh');
    });

    // App layout management
    Route::prefix('app')->as('app.')->group(function () {
        Route::get('/{appId}/layout', [AdminDiagramController::class, 'getAppLayoutVueFlowData'])->name('layout');
        Route::get('/{appId}/layout/admin', [AdminDiagramController::class, 'showAppLayout'])->name('layout.admin');
        Route::post('/{appId}/layout', [AdminDiagramController::class, 'saveAppLayout'])->name('save-layout');
    });

    // Stream CRUD management
    Route::prefix('streams')->as('streams.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\StreamController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\StreamController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\StreamController::class, 'store'])->name('store');
        Route::get('/{stream}/edit', [\App\Http\Controllers\Admin\StreamController::class, 'edit'])->name('edit');
        Route::put('/{stream}', [\App\Http\Controllers\Admin\StreamController::class, 'update'])->name('update');
        Route::delete('/{stream}', [\App\Http\Controllers\Admin\StreamController::class, 'destroy'])->name('destroy');
        Route::patch('/{stream}/toggle-allowed', [\App\Http\Controllers\Admin\StreamController::class, 'toggleAllowed'])->name('toggle-allowed');
        Route::patch('/bulk-update-sort', [\App\Http\Controllers\Admin\StreamController::class, 'bulkUpdateSort'])->name('bulk-update-sort');
    });

    // Contract management
    Route::prefix('contracts')->as('contracts.')->group(function () {
        Route::get('/', [AdminContractController::class, 'index'])->name('index');
        Route::get('/create', [AdminContractController::class, 'create'])->name('create');
        Route::post('/', [AdminContractController::class, 'store'])->name('store');
        Route::get('/{contract}/edit', [AdminContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [AdminContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [AdminContractController::class, 'destroy'])->name('destroy');
    });
});