<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostItemController;

/*
|--------------------------------------------------------------------------
| DEBUG ROUTE (TEMPORARY)
|--------------------------------------------------------------------------
|
| This helps us see the REAL error behind your 500 issue.
| Visit: /api/debug-db after deployment.
|
*/

Route::get('/debug-db', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'ok' => true,
            'message' => 'DB connection works!',
            'database' => DB::getDatabaseName(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
            'exception' => class_basename($e),
        ], 500);
    }
});


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/items', [LostItemController::class, 'index']);
Route::get('/items/{lostItem}', [LostItemController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth.api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/items', [LostItemController::class, 'store']);
    Route::put('/items/{lostItem}', [LostItemController::class, 'update']);
    Route::patch('/items/{lostItem}', [LostItemController::class, 'update']);
    Route::delete('/items/{lostItem}', [LostItemController::class, 'destroy']);
});
