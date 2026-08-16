<?php

use App\Etic\Integrations\Marketing\Http\Controllers\GoogleMerchantFeedController;
use App\Etic\Integrations\Payments\Http\Controllers\PaytrController;
use App\Etic\SEO\Http\Controllers\RobotsController;
use App\Etic\SEO\Http\Controllers\SitemapController;
use App\Etic\Storefront\Http\Controllers\StorefrontController;
use App\Etic\Storefront\Http\Middleware\BindStorefrontSession;
use Illuminate\Support\Facades\Route;

Route::middleware([BindStorefrontSession::class])->group(function () {
    Route::get('/', [StorefrontController::class, 'home'])->name('home');
    Route::get('/koleksiyon', [StorefrontController::class, 'catalog'])->name('catalog');
    Route::get('/koleksiyon/{slug}', [StorefrontController::class, 'collection'])->name('collection')->where('slug', '^(?!favicon\.ico$).+');
    Route::get('/urun/{slug}', [StorefrontController::class, 'product'])->name('product')->where('slug', '^(?!favicon\.ico$).+');
    Route::get('/p/{slug}', fn (string $slug) => redirect()->route('product', $slug, 301));
    Route::get('/ara', [StorefrontController::class, 'search'])->name('search');
    Route::get('/ara/oneriler', \App\Etic\Storefront\Http\Controllers\SearchSuggestionsController::class)->name('search.suggestions');
    Route::get('/sayfa/{slug}', [StorefrontController::class, 'page'])->name('page');
    Route::get('/blog', [StorefrontController::class, 'blogIndex'])->name('blog.index');
    Route::get('/blog/{slug}', [StorefrontController::class, 'blogShow'])->name('blog.show');

    Route::get('/sepet', [StorefrontController::class, 'cart'])->name('cart.show');
    Route::post('/sepet', [StorefrontController::class, 'addToCart'])->name('cart.add');
    Route::patch('/sepet', [StorefrontController::class, 'updateCart'])->name('cart.update');
    Route::delete('/sepet', [StorefrontController::class, 'removeCart'])->name('cart.remove');
    Route::post('/sepet/kupon', [StorefrontController::class, 'coupon'])->name('cart.coupon');
    Route::delete('/sepet/kupon', [StorefrontController::class, 'removeCoupon'])->name('cart.coupon.remove');

    Route::get('/odeme', [StorefrontController::class, 'checkout'])->name('checkout.show');
    Route::post('/odeme', [StorefrontController::class, 'placeOrder'])->name('checkout.place');
    Route::post('/odeme/paytr/token', [PaytrController::class, 'token'])->name('paytr.token');
    Route::post('/odeme/paytr/callback', [PaytrController::class, 'callback'])->name('paytr.callback');
    Route::get('/odeme/paytr/basarili/{order}', [PaytrController::class, 'success'])->name('paytr.success');
    Route::get('/odeme/paytr/basarisiz/{order}', [PaytrController::class, 'fail'])->name('paytr.fail');
    Route::get('/siparis/{order}', [StorefrontController::class, 'success'])->name('checkout.success');

    Route::get('/giris', [StorefrontController::class, 'loginForm'])->name('login');
    Route::post('/giris', [StorefrontController::class, 'login']);
    Route::get('/kayit', [StorefrontController::class, 'registerForm'])->name('register');
    Route::post('/kayit', [StorefrontController::class, 'register']);
    Route::post('/cikis', [StorefrontController::class, 'logout'])->name('logout');
    Route::get('/hesabim', [StorefrontController::class, 'account'])->middleware('auth')->name('account');
    Route::get('/hesabim/siparis/{order}', [StorefrontController::class, 'accountOrder'])->middleware('auth')->name('account.order');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/feed/google-merchant.xml', GoogleMerchantFeedController::class)->name('feed.google-merchant');
