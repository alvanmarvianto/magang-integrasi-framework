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

Route::get('/index', [AppController::class, 'index']);
Route::get('/integration/app/{app_id}', [AppController::class, 'integration'])->name('integration');
Route::get('/integration/stream/{stream}', [AppController::class, 'streamIntegrations'])
    ->name('stream.integrations');
    
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';