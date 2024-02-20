<?php

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/use App\Http\Controllers\UserController;
Route::prefix('user')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);
        Route::get('/user', [UserController::class, 'me']);
        Route::put('/update', [UserController::class, 'update']);
        Route::post('/demande', [UserController::class, 'create_demande']);
        Route::get('/showdemande', [UserController::class, 'show_demandes']);
        // Add more user-specific routes here...
    });
});

// Routes for Admin Authentication
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminController::class, 'register']);
    Route::post('/login', [AdminController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AdminController::class, 'me']);
        Route::post('/adduser', [AdminController::class, 'addUser']);
        Route::put('/updatedemande/{id}', [AdminController::class, 'update_demande']);
        // Add more admin-specific routes here...
    });
});
