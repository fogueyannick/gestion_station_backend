<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportImportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import-reports', [ReportImportController::class, 'showForm']);
Route::post('/import-reports', [ReportImportController::class, 'import']);
