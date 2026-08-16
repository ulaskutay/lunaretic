<?php

namespace App\Etic\Storefront\Http\Middleware;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use App\Etic\Theme\ActiveTheme;
use App\Etic\Theme\ThemeRegistry;
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
            $previewTheme = $request->query('theme_preview');

            if (is_string($previewTheme) && app(ThemeRegistry::class)->get($previewTheme)) {
                $store = clone $store;
                $store->setAttribute('theme', $previewTheme);
                $request->attributes->set('etic.theme_preview', true);
            }

            $context->bind($store);
        }

        $context->applyRuntime();
        view()->share('eticStore', $context);
        view()->share('eticTheme', app(ActiveTheme::class));

        $response = $next($request);

        if ($request->attributes->get('etic.theme_preview')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
