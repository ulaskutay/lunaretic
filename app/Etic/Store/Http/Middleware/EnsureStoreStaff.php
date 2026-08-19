<?php

namespace App\Etic\Store\Http\Middleware;

use App\Etic\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');

        if (! $staff) {
            return $next($request);
        }

        $store = app(StoreContext::class)->store();

        if (! $store) {
            abort(404);
        }

        if ($staff->admin) {
            return $next($request);
        }

        if ($store->isSuspended()) {
            abort(403, __('etic.tenancy.store_suspended'));
        }

        abort_unless($store->hasMember($staff), 403, __('etic.tenancy.not_a_member'));

        return $next($request);
    }
}
