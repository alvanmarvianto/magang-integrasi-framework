<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AppController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', [AppController::class, 'index']);
Route::get('/integration/app/{app_id}', [AppController::class, 'appIntegration'])->name('appIntegration');
Route::get('/technology/{app_id}', [AppController::class, 'technology'])->name('technology');
Route::get('/integration/stream/{stream}', [AppController::class, 'streamIntegrations'])
    ->name('stream.integrations');
Route::get('/vue-flow/stream/{stream}', [AppController::class, 'vueFlowStreamIntegrations'])
    ->name('vue-flow.stream.integrations');