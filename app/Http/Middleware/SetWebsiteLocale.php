<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetWebsiteLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('website_language', 'en');
        app()->setLocale(! $request->is('admin', 'admin/*') && in_array($locale, ['en', 'hi'], true) ? $locale : 'en');

        return $next($request);
    }
}
