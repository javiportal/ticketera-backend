<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [App\Http\Controllers\Api\SwaggerController::class, 'ui'])->name('swagger.ui');
Route::get('/api/docs/json', [App\Http\Controllers\Api\SwaggerController::class, 'json'])->name('swagger.json');
