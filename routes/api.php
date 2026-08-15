<?php

use App\Etic\Storefront\Http\Api\StoreApiController;
use App\Etic\Storefront\Http\Middleware\BindStorefrontSession;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1', BindStorefrontSession::class])->prefix('v1')->group(function () {
    Route::get('/products', [StoreApiController::class, 'products']);
    Route::get('/categories', [StoreApiController::class, 'categories']);
    Route::get('/brands', [StoreApiController::class, 'brands']);
    Route::get('/pages', [StoreApiController::class, 'pages']);
    Route::get('/cart', [StoreApiController::class, 'cart']);
});
