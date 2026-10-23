<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalOrigin = rtrim((string) config('app.url'), '/');
        $canonicalHost = parse_url($canonicalOrigin, PHP_URL_HOST);

        if (! $canonicalHost || in_array($canonicalHost, ['localhost', '127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        $canonicalPath = $path !== '/' ? rtrim($path, '/') : '/';
        $query = $request->getQueryString();
        $target = $canonicalOrigin.$canonicalPath.($query ? '?'.$query : '');
        $current = $request->getSchemeAndHttpHost().$request->getRequestUri();

        if (strcasecmp($current, $target) !== 0) {
            return redirect()->to($target, 301);
        }

        return $next($request);
    }
}
