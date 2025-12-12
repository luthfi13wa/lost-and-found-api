<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostItemController;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group
| assigned the "api" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| DEBUG ROUTES (optional but useful)
|--------------------------------------------------------------------------
*/

Route::get('/debug-db', function () {
    $default = config('database.default');
    $config = config("database.connections.$default");

    try {
        DB::connection()->getPdo();

        return response()->json([
            'ok'      => true,
            'message' => 'DB connection works!',
            'driver'  => $default,
            'config'  => $config,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'        => false,
            'error'     => $e->getMessage(),
            'exception' => class_basename($e),
            'driver'    => $default,
            'config'    => $config,
        ]);
    }
});

Route::get('/debug-storage', function () {
    $disk = Storage::disk('public');

    return response()->json([
        'files_root'          => $disk->files(),
        'files_lost'          => $disk->files('lost_items'),
        'exists_example1'     => $disk->exists('lost_items/BAgZMv2DphrISfWxpZDgNknVr4v0J8Cbzzq90lC5.jpg'),
        'exists_example2'     => $disk->exists('lost_items/lIUZtySnOBXet9ulTq76NSc7C3wVcHkd9QT6PMKO.jpg'),
        'public_path_example' => file_exists(public_path('storage')),
    ]);
});

// Create a quick demo user
Route::get('/debug-make-user', function () {
    $email = 'demo@example.com';
    $password = 'password123';

    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name'     => 'Demo User',
            'password' => Hash::make($password),
        ]
    );

    return response()->json([
        'message'  => 'Test user ready.',
        'id'       => $user->id,
        'email'    => $email,
        'password' => $password,
    ]);
});

/*
|--------------------------------------------------------------------------
| PUBLIC FILE SERVER (for images)
|--------------------------------------------------------------------------
*/
Route::get('/storage/{path}', function ($path) {
    $filePath = public_path("storage/$path");

    if (! file_exists($filePath)) {
        abort(404);
    }

    return response()->file($filePath, [
        'Content-Type' => mime_content_type($filePath),
    ]);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Lost items browsing
Route::get('/items', [LostItemController::class, 'index']);
Route::get('/items/{lostItem}', [LostItemController::class, 'show']);

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Authenticated user info
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Lost item CRUD
    Route::post('/items',                [LostItemController::class, 'store']);
    Route::put('/items/{lostItem}',      [LostItemController::class, 'update']);
    Route::patch('/items/{lostItem}',    [LostItemController::class, 'update']);
    Route::delete('/items/{lostItem}',   [LostItemController::class, 'destroy']);

    // Mark item as found + upload proof
    Route::post('/items/{lostItem}/found', [LostItemController::class, 'markAsFound']);
});
