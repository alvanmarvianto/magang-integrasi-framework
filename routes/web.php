<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AppController;
use App\Http\Controllers\AdminVueFlowController;
use App\Http\Controllers\TechnologyController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', [AppController::class, 'index']);
Route::get('/integration/app/{app_id}', [AppController::class, 'appIntegration'])->name('appIntegration');
Route::get('/integration/stream/{stream}', [AppController::class, 'streamIntegrations'])
    ->name('stream.integrations');
Route::get('/diagram/stream/{stream}', [AppController::class, 'vueFlowStreamIntegrations'])
    ->name('diagram.stream.integrations');

Route::prefix('technology')->group(function () {
    Route::get('/vendor/{vendor_name}', [TechnologyController::class, 'getAppByVendor'])->name('technology.vendor');
    Route::get('/os/{os_name}', [TechnologyController::class, 'getAppByOS'])->name('technology.os');
    Route::get('/database/{database_name}', [TechnologyController::class, 'getAppByDatabase'])->name('technology.database');
    Route::get('/language/{language_name}', [TechnologyController::class, 'getAppByLanguage'])->name('technology.language');
    Route::get('/third-party/{third_party_name}', [TechnologyController::class, 'getAppByThirdParty'])->name('technology.third_party');
    Route::get('/middleware/{middleware_name}', [TechnologyController::class, 'getAppByMiddleware'])->name('technology.middleware');
    Route::get('/framework/{framework_name}', [TechnologyController::class, 'getAppByFramework'])->name('technology.framework');
    Route::get('/platfrom/{platform_name}', [TechnologyController::class, 'getAppByPlatform'])->name('technology.platform');
    Route::get('/{app_id}', [TechnologyController::class, 'show'])->name('technology.app');
});

Route::prefix('admin')->group(function () {
    Route::get('/stream/{stream}', [AdminVueFlowController::class, 'show'])->name('admin.stream.show');
    Route::post('/stream/{stream}/layout', [AdminVueFlowController::class, 'saveLayout'])->name('admin.stream.layout');
});