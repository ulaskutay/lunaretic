<?php

namespace App\Etic\Storefront\Http\Middleware;

use App\Etic\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Symfony\Component\HttpFoundation\Response;

class BindStorefrontSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = app(StoreContext::class);

        CartSession::setChannel($store->channel());
        CartSession::setCurrency($store->currency());

        return $next($request);
    }
}
