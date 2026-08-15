<?php

namespace App\Etic\SEO\Http\Middleware;

use App\Etic\SEO\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');

        $redirect = Redirect::query()
            ->where('is_active', true)
            ->where('from_path', $path)
            ->first();

        if ($redirect) {
            return redirect($redirect->to_url, $redirect->status_code);
        }

        return $next($request);
    }
}
