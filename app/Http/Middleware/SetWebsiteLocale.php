<?php

namespace App\Http\Middleware;

use App\HindiContent;
use App\WebsiteLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;
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
        $isHindi = $request->is('/', 'hi', 'hi/*');
        app()->setLocale($isHindi ? 'hi' : 'en');
        if ($name = $request->route()?->getAction('website_name')) {
            $action = $request->route()->getAction();
            $action['as'] = $name;
            $request->route()->setAction($action);
        }
        URL::formatPathUsing(function (string $path, ?Route $route): string {
            return app()->getLocale() === 'hi' && $route && in_array($route->getName(), WebsiteLocale::ROUTES, true)
                ? WebsiteLocale::hindiPath($path) : ($route?->getName() === 'home' ? '/en/' : $path);
        });

        $response = $next($request);
        if ($isHindi && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $html = preg_replace_callback('~\b(href|action)="(/(?!/)[^"]*)"~', function (array $match): string {
                $path = parse_url(html_entity_decode($match[2]), PHP_URL_PATH);
                if (! is_string($path) || ! WebsiteLocale::supportsPath($path)) {
                    return $match[0];
                }

                return $match[1].'="'.WebsiteLocale::hindiPath($match[2]).'"';
            }, $response->getContent());
            $original = $response instanceof \Illuminate\Http\Response ? $response->original : null;
            $response->setContent(HindiContent::translateHtml($html));
            if ($response instanceof \Illuminate\Http\Response) {
                $response->original = $original;
            }
        }

        return $response;
    }
}
