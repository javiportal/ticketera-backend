<?php

use App\Http\Controllers\Api\SwaggerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/swagger', [SwaggerController::class, 'ui'])->name('swagger.ui');
Route::get('/api/docs/json', [SwaggerController::class, 'json'])->name('swagger.json');

Route::get('/docs/analysis', function () {
    return response()->file(public_path('docs/analysis.html'));
})->name('docs.analysis');
