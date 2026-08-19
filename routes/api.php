<?php

use App\Etic\Integrations\Payments\Http\Controllers\PaytrController;
use App\Etic\Storefront\Http\Controllers\SearchSuggestionsController;
use App\Etic\Storefront\Http\Api\AuthApiController;
use App\Etic\Storefront\Http\Api\CartApiController;
use App\Etic\Storefront\Http\Api\StoreApiController;
use App\Etic\Storefront\Http\Middleware\BindStorefrontSession;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/search/suggestions', SearchSuggestionsController::class);
});

Route::middleware(['throttle:60,1', BindStorefrontSession::class])->prefix('v1')->group(function () {
    Route::get('/bootstrap', [StoreApiController::class, 'bootstrap']);
    Route::get('/products', [StoreApiController::class, 'products']);
    Route::get('/products/{slug}', [StoreApiController::class, 'product']);
    Route::get('/categories', [StoreApiController::class, 'categories']);
    Route::get('/brands', [StoreApiController::class, 'brands']);
    Route::get('/pages', [StoreApiController::class, 'pages']);
    Route::get('/pages/{slug}', [StoreApiController::class, 'page']);
    Route::get('/blog', [StoreApiController::class, 'blogIndex']);
    Route::get('/blog/{slug}', [StoreApiController::class, 'blogShow']);

    Route::get('/cart', [CartApiController::class, 'show']);
    Route::post('/cart', [CartApiController::class, 'add']);
    Route::patch('/cart', [CartApiController::class, 'update']);
    Route::delete('/cart', [CartApiController::class, 'remove']);
    Route::post('/cart/coupon', [CartApiController::class, 'coupon']);
    Route::delete('/cart/coupon', [CartApiController::class, 'removeCoupon']);

    Route::get('/checkout', [CartApiController::class, 'checkout']);
    Route::post('/checkout', [CartApiController::class, 'place']);
    Route::post('/checkout/paytr/token', [PaytrController::class, 'token']);
    Route::get('/orders/{order}', [CartApiController::class, 'order']);

    Route::post('/auth/register', [AuthApiController::class, 'register']);
    Route::post('/auth/login', [AuthApiController::class, 'login']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::get('/account', [AuthApiController::class, 'account']);
});
