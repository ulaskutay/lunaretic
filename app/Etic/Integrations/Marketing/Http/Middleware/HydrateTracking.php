<?php

namespace App\Etic\Integrations\Marketing\Http\Middleware;

use App\Etic\Integrations\Marketing\TrackingDispatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HydrateTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        app(TrackingDispatcher::class)->resetAndHydrate();

        return $next($request);
    }
}
