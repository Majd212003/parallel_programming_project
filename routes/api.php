<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavouritesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
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
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    Route::get('/Store', [StoreController::class, 'index']);

    Route::get('/cart', [OrderController::class, 'currentCart']);
    Route::post('/cart/add', [OrderController::class, 'addToCart']);
    Route::put('/cart/items/{item}', [OrderController::class, 'updateCartItem']);
    Route::delete('/cart/items/{item}', [OrderController::class, 'removeCartItem']);
    Route::post('/cart/confirm', [OrderController::class, 'confirmCart']);

    Route::get('/orders', [OrderController::class, 'userOrders']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/favourites', [FavouritesController::class, 'index']);
    Route::post('/favourites', [FavouritesController::class, 'store']);
    Route::delete('/favourites/{product}', [FavouritesController::class, 'destroy']);

    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    Route::middleware('role:admin,employee')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::post('/Store', [StoreController::class, 'store']);
        Route::put('/Store/{Store}', [StoreController::class, 'update']);
        Route::delete('/Store/{Store}', [StoreController::class, 'destroy']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::get('/admin/users/{user}', [UserController::class, 'show']);
        Route::put('/admin/users/{user}', [UserController::class, 'update']);
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);

        Route::get('/admin/orders', [OrderController::class, 'adminOrders']);
        Route::get('/admin/payments', [PaymentController::class, 'index']);
        Route::get('/admin/payments/{payment}', [PaymentController::class, 'show']);
    });
});
