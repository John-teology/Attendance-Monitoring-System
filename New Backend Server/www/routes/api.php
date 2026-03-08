<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Middleware\CheckApiSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/attendance/sync', [AttendanceController::class, 'sync'])
    ->middleware(CheckApiSecret::class);

Route::post('/verify-user', [AttendanceController::class, 'verify'])
    ->middleware(CheckApiSecret::class);

Route::get('/attendance/stats', [AttendanceController::class, 'getStats'])
    ->middleware(CheckApiSecret::class);

Route::get('/users', [AttendanceController::class, 'getUsers'])
    ->middleware(CheckApiSecret::class);

Route::get('/system-settings', [AttendanceController::class, 'getSettings'])
    ->middleware(CheckApiSecret::class);
