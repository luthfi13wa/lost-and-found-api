<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostItemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 🔍 Debug route to inspect DB connection + config in production
Route::get('/debug-db', function () {
    $config = [
        'host'     => config('database.connections.mysql.host'),
        'port'     => config('database.connections.mysql.port'),
        'database' => config('database.connections.mysql.database'),
        'username' => config('database.connections.mysql.username'),
    ];

    try {
        DB::connection()->getPdo();

        return response()->json([
            'ok'      => true,
            'message' => 'DB connection works!',
            'config'  => $config,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'        => false,
            'error'     => $e->getMessage(),
            'exception' => class_basename($e),
            'config'    => $config,
        ]);
    }
});

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
