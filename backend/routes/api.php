<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\MessageController;

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
        Route::get('/dashboard/revenue-week', [DashboardController::class, 'revenueWeek']);

        // Tenant
        Route::get('/tenant', [TenantController::class, 'show']);

        // Users
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users/bulk', [UserController::class, 'bulk']);

        // Categories
        Route::apiResource('categories', CategoryController::class)->except(['update']);

        // Menu Items
        Route::get('/menu-items/low-stock', [MenuItemController::class, 'lowStock']);
        Route::apiResource('menu-items', MenuItemController::class);
        Route::patch('/menu-items/{menuItem}/restock', [MenuItemController::class, 'restock']);
        Route::patch('/menu-items/{menuItem}/availability', [MenuItemController::class, 'updateAvailability']);

        // Orders
        Route::get('/orders', [AdminOrderController::class, 'index']);  //AdminController is OrderController renamed
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);  //AdminController is OrderController renamed

        // Messages
        Route::get('/messages', [MessageController::class, 'index']);
        Route::post('/messages', [MessageController::class, 'store']);
        Route::patch('/messages/{message}/read', [MessageController::class, 'markAsRead']);
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
        Route::post('/menu/{id}/request-restock', [MenuAvailabilityController::class, 'requestRestock']);
    });

    // ─── User ─────────────────────────────────────────────
    Route::middleware('role:user')->prefix('user')->group(function () {

        // Menu
        Route::get('/menu', [MenuController::class, 'index']);

        // Orders
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']); //DUPLICATE
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/orders/{order}', [OrderController::class, 'update']);
        Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        
    });
});