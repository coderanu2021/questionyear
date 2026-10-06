<?php

namespace App\Providers;

use App\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());

        View::composer(['website.*', 'errors.*', 'errors::*', 'admin.index', 'admin.pages.settings'], function (\Illuminate\View\View $view): void {
            $settings = SiteSettings::values();
            $view->with('siteSettings', $settings)->with('siteLogoUrl', SiteSettings::logoUrl($settings))->with('siteFaviconUrl', SiteSettings::faviconUrl($settings));
        });
    }
}
