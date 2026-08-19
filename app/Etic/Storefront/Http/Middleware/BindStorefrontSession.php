<?php

namespace App\Etic\Storefront\Http\Middleware;

use App\Etic\Catalog\Models\CustomerGroup;
use App\Etic\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Facades\StorefrontSession;
use Symfony\Component\HttpFoundation\Response;

class BindStorefrontSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = app(StoreContext::class);

        CartSession::setChannel($store->channel());
        CartSession::setCurrency($store->currency());

        $group = CustomerGroup::getDefault();

        if ($group) {
            StorefrontSession::setCustomerGroups(collect([$group]));
        }

        return $next($request);
    }
}
