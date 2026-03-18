<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

//PUBLIC
Route::get('/auth/login', function () {
    return "Login";
});
Route::post('/auth/login', [AuthController::class, 'login']);

//PROTECTED
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
