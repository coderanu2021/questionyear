<?php

namespace App\Providers;

use App\SiteSettings;
use App\WebsiteNavigation;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(WebsiteNavigation::class);
        $this->app->singleton(ClientInterface::class, fn (): Client => new Client);
    }

    public function boot(): void
    {
        $this->app->useLangPath(resource_path('lang'));
        DB::prohibitDestructiveCommands($this->app->isProduction());

        View::composer(['website.partials.header', 'website.partials.footer', 'website.pages.home'], function (\Illuminate\View\View $view): void {
            $view->with('navigationMenus', app(WebsiteNavigation::class)->menus());
        });

        View::composer(['website.*', 'errors.*', 'errors::*', 'admin.index', 'admin.pages.settings', 'admin.pages.chapter-content'], function (\Illuminate\View\View $view): void {
            $settings = SiteSettings::values();
            $view->with('siteSettings', $settings)->with('siteLogoUrl', SiteSettings::logoUrl($settings))->with('siteFaviconUrl', SiteSettings::faviconUrl($settings));
        });
    }
}
