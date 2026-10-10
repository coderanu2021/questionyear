<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $request->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=6000,max_height=6000']]);
        $path = $request->file('upload')->store('post-images', 'local');
        abort_unless($path, 500, 'Image could not be uploaded.');

        return response()->json(['url' => route('posts.image', basename($path))]);
    }

    public function image(string $filename): StreamedResponse
    {
        $path = 'post-images/'.$filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    public function blogs(): View
    {
        $posts = Post::where('type', 'blog')->where('status', 'published')->where('published_at', '<=', now())
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9);

        return view('website.blogs', ['posts' => $posts]);
    }

    public function show(string $slug): View
    {
        $post = Post::where('slug', $slug)->where('type', 'blog')->where('status', 'published')
            ->where('published_at', '<=', now())->firstOrFail();

        return view('website.blogs', ['post' => $post]);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['type' => ['nullable', Rule::in(['blog', 'news'])]]);
        $posts = Post::query()->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.index', ['page' => 'posts', 'state' => $this->adminState(), 'posts' => $posts]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.index', ['page' => 'posts', 'state' => $this->adminState(), 'post' => new Post, 'isPostForm' => true]);
    }

    public function edit(Request $request, Post $post): View
    {
        $this->authorizeAdmin($request);

        return view('admin.index', ['page' => 'posts', 'state' => $this->adminState(), 'post' => $post, 'isPostForm' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        Post::create($this->validatedPost($request));

        return redirect()->route('admin.posts.index')->with('status', 'Post created.');
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $post->update($this->validatedPost($request, $post));

        return redirect()->route('admin.posts.index')->with('status', 'Post updated.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Post deleted.');
    }

    /** @return array{type: string, title: string, slug: string, excerpt: ?string, content: string, status: string, published_at: mixed} */
    private function validatedPost(Request $request, ?Post $post = null): array
    {
        $request->validate(['title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255']]);
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title', ''))]);
        $data = $request->validate([
            'type' => ['required', Rule::in(['blog', 'news'])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
        $data['published_at'] = $data['status'] === 'published' ? ($post?->published_at ?? now()) : null;

        return $data;
    }

    /** @return array{tests: array, weeks: array{labels: array}} */
    private function adminState(): array
    {
        return ['tests' => [], 'weeks' => ['labels' => []]];
    }
}
