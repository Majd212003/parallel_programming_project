<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavouritesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Jobs\ProcessOrdersBatch;
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


Route::post('/test-batch', function () {

    ProcessOrdersBatch::dispatch();

    return response()->json([
        'message' => 'Batch job dispatched successfully'
    ]);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });



    Route::middleware(['auth:sanctum', 'role:user'])->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit'])->middleware('throttle:wallet-deposit');
});

Route::middleware('role:user')->group(function () {
    Route::post('/orders/direct', [OrderController::class, 'directOrder'])->middleware('throttle:direct-order') ;
});



    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    Route::get('/Store', [StoreController::class, 'index']);

    Route::get('/cart', [OrderController::class, 'currentCart']);
    Route::post('/cart/add', [OrderController::class, 'addToCart'])->middleware('throttle:cart-write');
    Route::put('/cart/items/{item}', [OrderController::class, 'updateCartItem']);
    Route::delete('/cart/items/{item}', [OrderController::class, 'removeCartItem']);
    Route::post('/cart/confirm', [OrderController::class, 'confirmCart'])->middleware('throttle:cart-write');

    Route::get('/orders', [OrderController::class, 'userOrders']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/favourites', [FavouritesController::class, 'index']);
    Route::post('/favourites', [FavouritesController::class, 'store']);
    Route::delete('/favourites/{product}', [FavouritesController::class, 'destroy']);

    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    Route::middleware('role:admin,employee')->group(function () {
        Route::post('/products', [ProductController::class, 'store'])->middleware('throttle:admin-write');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('throttle:admin-write');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('throttle:admin-write');

        Route::post('/Store', [StoreController::class, 'store'])->middleware('throttle:admin-write');
        Route::put('/Store/{Store}', [StoreController::class, 'update'])->middleware('throttle:admin-write');
        Route::delete('/Store/{Store}', [StoreController::class, 'destroy'])->middleware('throttle:admin-write');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::get('/admin/users/{user}', [UserController::class, 'show']);
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->middleware('throttle:admin-write');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->middleware('throttle:admin-write');

        Route::get('/admin/orders', [OrderController::class, 'adminOrders']);
        Route::get('/admin/payments', [PaymentController::class, 'index']);
        Route::get('/admin/payments/{payment}', [PaymentController::class, 'show']);
    });
});
