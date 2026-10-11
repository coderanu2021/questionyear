<?php

namespace App\Http\Controllers;

use App\WebsiteLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebsiteLanguageController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['language' => 'required|in:en,hi', 'return_to' => 'nullable|string|max:2048']);
        $request->session()->put('website_language', $data['language']);
        $returnTo = $data['return_to'] ?? '/';
        if (! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || str_contains($returnTo, '\\') || preg_match('/[\x00-\x20]/', $returnTo)) {
            $returnTo = '/';
        }

        $path = parse_url($returnTo, PHP_URL_PATH);
        if (is_string($path) && WebsiteLocale::supportsPath($path)) {
            $returnTo = $data['language'] === 'hi' ? WebsiteLocale::hindiPath($returnTo) : WebsiteLocale::englishPath($returnTo);
        }

        return redirect()->to($returnTo);
    }
}
