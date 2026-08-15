<?php

namespace App\Etic\Storefront\Http\Middleware;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(StoreContext::class);
        $store = Store::resolveByHost($request->getHost()) ?? $context->store();

        if ($store) {
            $context->bind($store);
        }

        $context->applyRuntime();
        view()->share('eticStore', $context);

        return $next($request);
    }
}
