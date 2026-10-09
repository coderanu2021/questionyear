<?php

namespace App\Http\Controllers;

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

        return redirect()->to($returnTo);
    }
}
