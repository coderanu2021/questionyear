<?php

namespace App\Http\Controllers;

use App\PageSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageSeoController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $pages = PageSeo::pages();
        $data = $request->validate(['page_key' => ['nullable', 'string', Rule::in(array_keys($pages))]]);
        $selectedKey = $data['page_key'] ?? 'route:home';
        $metadata = DB::table('page_seo')->where('page_key', $selectedKey)->first();

        return view('admin.index', [
            'page' => 'seo',
            'state' => ['tests' => [], 'weeks' => ['labels' => []]],
            'seoPages' => $pages,
            'selectedSeoKey' => $selectedKey,
            'metadata' => $metadata,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'page_key' => ['required', 'string', Rule::in(array_keys(PageSeo::pages()))],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
        ]);
        $key = $data['page_key'];
        unset($data['page_key']);
        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $data[$field] = isset($data[$field]) && trim($data[$field]) !== '' ? trim($data[$field]) : null;
        }
        DB::table('page_seo')->updateOrInsert(['page_key' => $key], [...$data, 'updated_at' => now()]);

        return redirect()->route('admin.seo.index', ['page_key' => $key])->with('status', 'Page SEO saved. Your changes are live.');
    }
}
