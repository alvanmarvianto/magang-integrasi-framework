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
Route::get('/technology/{app_id}', [TechnologyController::class, 'show'])->name('technology');
Route::get('/integration/stream/{stream}', [AppController::class, 'streamIntegrations'])
    ->name('stream.integrations');
Route::get('/diagram/stream/{stream}', [AppController::class, 'vueFlowStreamIntegrations'])
    ->name('diagram.stream.integrations');

// Admin routes for stream layout management
Route::prefix('admin')->group(function () {
    Route::get('/stream/{stream}', [AdminVueFlowController::class, 'show'])->name('admin.stream.show');
    Route::post('/stream/{stream}/layout', [AdminVueFlowController::class, 'saveLayout'])->name('admin.stream.layout');
});