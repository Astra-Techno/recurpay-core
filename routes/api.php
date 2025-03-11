<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'getUser']);
    Route::post('/logout', [UserController::class, 'logout']);
});

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/guest-token', [\App\Http\Controllers\Api\AuthController::class, 'guestToken']);

// Public routes (No authentication required)
Route::post('/register', [UserController::class, 'register']);