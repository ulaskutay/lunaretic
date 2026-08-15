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
        $path = Redirect::normalizePath($request->getPathInfo());

        $redirect = Redirect::query()
            ->forStore()
            ->where('is_active', true)
            ->where('from_path', $path)
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        $target = $redirect->to_url;
        $targetPath = Redirect::normalizePath(parse_url($target, PHP_URL_PATH) ?: $target);

        if ($targetPath === $path && ! str_starts_with($target, 'http')) {
            return $next($request);
        }

        return redirect($target, $redirect->status_code);
    }
}
