<?php

namespace App\Etic\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');

        if (! $staff) {
            return $next($request);
        }

        abort_unless((bool) $staff->admin, 403, __('etic.tenancy.platform_only'));

        return $next($request);
    }
}
