<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;

// Routes publiques
Route::post('auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/refresh', [AuthController::class, 'refresh']); // optionnel

// Routes protégées SPA web (cookies)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
});

// Routes protégées API mobile Flutter (token Bearer)
Route::middleware([
    'auth:sanctum',
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
])->group(function () {
    Route::post('reports', [ReportController::class, 'store']);  // création ou upsert
    Route::put('reports/{report}', [ReportController::class, 'update']); // mise à jour
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('dashboard/stats', [ReportController::class, 'stats']);
    Route::delete('reports/{id}', [ReportController::class, 'destroy']);
});
