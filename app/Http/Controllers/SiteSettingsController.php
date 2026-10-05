<?php

namespace App\Http\Controllers;

use App\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SiteSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
        $data = $request->validate([
            'site_title' => 'required|string|max:100',
            'site_description' => 'required|string|max:1000',
            'contact_email' => 'required|email|max:255',
            'home_title' => 'required|string|max:255',
            'home_description' => 'required|string|max:3000',
            'footer_description' => 'required|string|max:3000',
            'about_content' => 'required|string|max:10000',
            'contact_description' => 'required|string|max:3000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048|dimensions:max_width=3000,max_height=3000',
            'remove_logo' => 'nullable|boolean',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,webp,gif|max:1024',
            'remove_favicon' => 'nullable|boolean',
        ]);
        $settings = SiteSettings::values();
        $oldLogo = $settings['logo_path'];
        $newLogo = null;
        $oldFavicon = $settings['favicon_path'];
        $newFavicon = null;
        unset($data['logo'], $data['remove_logo'], $data['favicon'], $data['remove_favicon']);
        $settings = array_replace($settings, $data);
        if ($request->boolean('remove_logo')) {
            $settings['logo_path'] = null;
        }
        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->store('site-logos', 'local');
            abort_unless($newLogo, 500, 'The logo could not be stored. Please try again.');
            $settings['logo_path'] = $newLogo;
        }
        try {
            if ($request->boolean('remove_favicon')) {
                $settings['favicon_path'] = null;
            }
            if ($request->hasFile('favicon')) {
                $newFavicon = $request->file('favicon')->store('site-favicons', 'local');
                abort_unless($newFavicon, 500, 'The favicon could not be stored. Please try again.');
                $settings['favicon_path'] = $newFavicon;
            }
            DB::table('site_settings')->updateOrInsert(['id' => 1], ['values' => json_encode($settings, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        } catch (Throwable $exception) {
            if ($newLogo) {
                Storage::disk('local')->delete($newLogo);
            }
            if ($newFavicon) {
                Storage::disk('local')->delete($newFavicon);
            }
            throw $exception;
        }
        if ($oldLogo && $oldLogo !== $settings['logo_path']) {
            Storage::disk('local')->delete($oldLogo);
        }
        if ($oldFavicon && $oldFavicon !== $settings['favicon_path']) {
            Storage::disk('local')->delete($oldFavicon);
        }

        return redirect()->route('admin', ['page' => 'settings'])->with('status', 'Website settings saved. Your changes are live.');
    }

    public function logo(string $filename): StreamedResponse
    {
        $settings = SiteSettings::values();
        abort_unless($settings['logo_path'] && basename($settings['logo_path']) === $filename && Storage::disk('local')->exists($settings['logo_path']), 404);

        return Storage::disk('local')->response($settings['logo_path'], null, ['Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function favicon(string $filename): StreamedResponse
    {
        $settings = SiteSettings::values();
        abort_unless($settings['favicon_path'] && basename($settings['favicon_path']) === $filename && Storage::disk('local')->exists($settings['favicon_path']), 404);

        return Storage::disk('local')->response($settings['favicon_path'], null, ['Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }
}
