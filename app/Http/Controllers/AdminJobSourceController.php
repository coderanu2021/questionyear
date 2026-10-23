<?php

namespace App\Http\Controllers;

use App\Models\JobSource;
use App\Services\JobSources\OfficialSourceUrlPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminJobSourceController extends Controller
{
    public function update(Request $request, JobSource $jobSource, OfficialSourceUrlPolicy $urlPolicy): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
        $data = $request->validate([
            'active' => ['required', 'boolean'],
            'terms_url' => ['nullable', 'url', 'max:512'],
            'terms_allows_automated_access' => ['required', 'boolean'],
        ]);

        if ($data['terms_allows_automated_access'] && blank($data['terms_url'] ?? null)) {
            throw ValidationException::withMessages(['terms_url' => 'Add the official terms or policy URL before allowing automated access.']);
        }

        if (($data['terms_url'] ?? null) && (! $urlPolicy->allows($data['terms_url'], $jobSource->url)
            || strtolower((string) parse_url($data['terms_url'], PHP_URL_HOST)) !== strtolower((string) parse_url($jobSource->url, PHP_URL_HOST)))) {
            throw ValidationException::withMessages(['terms_url' => 'Use a policy URL on the same official government website as this source.']);
        }

        $jobSource->forceFill([
            'active' => $data['active'],
            'terms_url' => $data['terms_url'] ?? null,
            'terms_reviewed_at' => filled($data['terms_url'] ?? null) ? now() : null,
            'terms_allows_automated_access' => $data['terms_allows_automated_access'],
        ])->save();

        return redirect()->route('admin.job-sources.index')->with('status', 'Official source terms review saved.');
    }
}
