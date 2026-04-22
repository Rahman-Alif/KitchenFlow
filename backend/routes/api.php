<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\KitchenStaff\OrderQueueController;
use App\Http\Controllers\KitchenStaff\MenuAvailabilityController;
use App\Http\Controllers\KitchenStaff\TransactionController;

use App\Http\Controllers\User\MenuController;
use App\Http\Controllers\User\OrderController;

// ─── Public ───────────────────────────────────────────────
Route::post('/auth/login', [LoginController::class, 'store']);
Route::post('/auth/password-reset/request', [PasswordResetController::class, 'request']);
Route::post('/auth/password-reset/confirm', [PasswordResetController::class, 'confirm']);

// ─── Authenticated ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    Route::post('/auth/logout', [LogoutController::class, 'store']);


    // ─── Admin ────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Users
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users/bulk', [UserController::class, 'bulk']);

        // Categories
        Route::apiResource('categories', CategoryController::class)->except(['update']);

        // Menu Items
        Route::apiResource('menu-items', MenuItemController::class);
        Route::patch('/menu-items/{menuItem}/restock', [MenuItemController::class, 'restock']);
    });

    // ─── Kitchen Staff ────────────────────────────────────
    Route::middleware('role:kitchen_staff')->prefix('kitchen')->group(function () {

        // Order Queue
        Route::get('/orders', [OrderQueueController::class, 'index']);
        Route::get('/orders/{id}', [OrderQueueController::class, 'show']);
        Route::patch('/orders/{id}/status', [OrderQueueController::class, 'updateStatus']);
        Route::post('/orders/{id}/transaction', [TransactionController::class, 'store']);

        // Menu Availability
        Route::get('/menu', [MenuAvailabilityController::class, 'index']);
        Route::patch('/menu/{id}/availability', [MenuAvailabilityController::class, 'updateAvailability']);
    });

    // ─── User ─────────────────────────────────────────────
    Route::middleware('role:user')->prefix('user')->group(function () {

        // Menu
        Route::get('/menu', [MenuController::class, 'index']);

        // Orders
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']); //DUPLICATE
    });
});