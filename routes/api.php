<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostItemController;

// PUBLIC ROUTES
Route::get('/items', [LostItemController::class, 'index']);
Route::get('/items/{lostItem}', [LostItemController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES (need token)
Route::middleware('auth.api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/items', [LostItemController::class, 'store']);
    Route::put('/items/{lostItem}', [LostItemController::class, 'update']);
    Route::patch('/items/{lostItem}', [LostItemController::class, 'update']);
    Route::delete('/items/{lostItem}', [LostItemController::class, 'destroy']);
});
