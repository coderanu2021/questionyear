<?php

namespace App;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class WebsiteLocale
{
    public const ROUTES = ['home', 'news.index', 'news.show', 'blogs.index', 'blogs.show', 'login', 'register', 'subject', 'learn', 'quiz', 'quiz.show', 'chapter', 'daily', 'weekly', 'monthly', 'daily.set', 'weekly.set', 'monthly.set', 'daily.submit', 'weekly.submit', 'monthly.submit', 'daily.set.submit', 'weekly.set.submit', 'monthly.set.submit', 'upcoming', 'exam', 'page', 'feedback', 'learning.leaderboard'];

    public static function englishPath(string $path): string
    {
        return preg_replace('~^/hi(?=/|$)~', '', $path) ?: '/';
    }

    public static function hindiPath(string $path): string
    {
        $path = self::englishPath($path);

        return '/hi'.($path === '/' ? '/' : $path);
    }

    public static function supportsPath(string $path): bool
    {
        $request = Request::create(self::englishPath($path));
        foreach (Route::getRoutes() as $route) {
            if (in_array($route->getName(), self::ROUTES, true) && $route->matches($request)) {
                return true;
            }
        }

        return false;
    }
}
