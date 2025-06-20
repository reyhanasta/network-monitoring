<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevicesController;
use App\Http\Controllers\NetworkController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::prefix('network')->group(function () {
    Route::get('status', [NetworkController::class, 'getNetworkStatus']);
    Route::get('health', [NetworkController::class, 'isNetworkHealthy']);
    Route::get('servers', [NetworkController::class, 'getServers']);
    Route::get('server/{serverKey}', [NetworkController::class, 'checkSpecificServer']);
    Route::post('check-url', [NetworkController::class, 'checkCustomUrl']);
    Route::post('servers', [NetworkController::class, 'addServer']);
    Route::delete('servers/{serverKey}', [NetworkController::class, 'removeServer']);
    Route::post('ping', [NetworkController::class, 'systemPing']);
    Route::get('monitor', [NetworkController::class, 'monitor']);
});

Route::get('/devices', [DevicesController::class, 'index'])->name('devices.index');

Route::prefix('devices')->group(function () {
    Route::get('status', [DevicesController::class, 'getDevicesStatus']);
    Route::post('check-all', [DevicesController::class, 'checkAllDevices']);
    Route::post('{id}/check', [DevicesController::class, 'checkDevice']);
    Route::get('{id}/history', [DevicesController::class, 'getDeviceHistory']);
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
