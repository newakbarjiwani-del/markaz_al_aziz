<?php

use App\Http\Controllers\Api\FinancePaymentController;
use App\Http\Controllers\Api\OrangTua\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Api\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Auth\ApiAuthController;
use Illuminate\Support\Facades\Route;

Route::get('finance/payment', FinancePaymentController::class)->middleware('throttle:60,1');

Route::prefix('auth')->group(function () {
    Route::post('ortu/login', [ApiAuthController::class, 'loginOrangTua'])->middleware('throttle:5,1');
    Route::post('siswa/login', [ApiAuthController::class, 'loginSiswa'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [ApiAuthController::class, 'logout']);
        Route::get('me', [ApiAuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum', 'role:orang_tua'])->prefix('ortu')->group(function () {
    Route::get('dashboard', [OrtuDashboardController::class, 'index']);
    Route::get('children', [OrtuDashboardController::class, 'children']);
});

Route::middleware(['auth:sanctum', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('dashboard', [SiswaDashboardController::class, 'index']);
    Route::get('profil', [SiswaDashboardController::class, 'profil']);
});
