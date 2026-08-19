<?php

namespace App\Etic\Storefront\Http\Middleware;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use App\Etic\Support\Tenancy;
use App\Etic\Theme\ActiveTheme;
use App\Etic\Theme\ThemeRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyStore
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up')) {
            return $next($request);
        }

        $context = app(StoreContext::class);
        $host = $this->requestHost($request);

        if (Tenancy::isPlatformHost($host) && $request->is('platform', 'platform/*')) {
            $context->bind(null);

            return $next($request);
        }

        $store = Store::resolveByHost($host);

        if (! $store && Tenancy::allowsDefaultFallback($host)) {
            $store = $context->store();
        }

        if (! $store) {
            abort(404);
        }

        $previewTheme = $request->query('theme_preview');

        if (is_string($previewTheme) && app(ThemeRegistry::class)->get($previewTheme)) {
            $store = clone $store;
            $store->setAttribute('theme', $previewTheme);
            $request->attributes->set('etic.theme_preview', true);
        }

        $context->bind($store);

        if ($store->isCustomHost($host) && ! Tenancy::isLoopbackHost($host) && $request->is('lunar', 'lunar/*')) {
            return redirect()->away($store->adminUrl($request->getRequestUri()));
        }

        if ($store->isSuspended() && ! Tenancy::isAdminPath($request)) {
            return response()->view('errors.store-suspended', [
                'store' => $store,
            ], 503);
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

    private function requestHost(Request $request): string
    {
        $host = Store::normalizeHost($request->getHost());

        if (! Tenancy::isLoopbackHost($request->ip())) {
            return $host;
        }

        $forwarded = $request->headers->get('X-Etic-Store-Host')
            ?: $request->headers->get('X-Forwarded-Host');

        if (! is_string($forwarded) || trim($forwarded) === '') {
            return $host;
        }

        return Store::normalizeHost(explode(',', $forwarded)[0]) ?: $host;
    }
}
