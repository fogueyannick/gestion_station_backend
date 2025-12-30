<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportImportController;
use App\Services\GcsUploader;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/import-reports', [ReportImportController::class, 'showForm']);
Route::post('/import-reports', [ReportImportController::class, 'import']);


//Route::post('/test-upload', function (\Illuminate\Http\Request $request) {
//    $url = GcsUploader::upload($request->file('photo'), 'tests');
//    return response()->json(['url' => $url]);
//});
