@if(session('status'))<div class="card" role="status" style="padding:20px;margin-bottom:20px">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card" role="alert" style="padding:20px;margin-bottom:20px">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($isPostForm ?? false)
<div class="head"><h2>{{ $post->exists ? 'Edit post' : 'Add blog or news' }}</h2><a class="btn" href="{{ route('admin.posts.index') }}">Back to posts</a></div>
<form class="card" style="padding:24px" method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
@csrf @if($post->exists) @method('PUT') @endif
<div class="two"><div class="f"><label for="post-type">Type</label><select id="post-type" name="type">@foreach(['blog' => 'Blog', 'news' => 'News'] as $value => $label)<option value="{{ $value }}" @selected(old('type', $post->type ?? 'blog') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="f"><label for="post-status">Status</label><select id="post-status" name="status">@foreach(['draft' => 'Draft', 'published' => 'Published'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $post->status ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select></div></div>
<div class="f"><label for="post-title">Title</label><input id="post-title" name="title" value="{{ old('title', $post->title) }}" required maxlength="255"></div>
<div class="f"><label for="post-slug">URL slug (generated from title when empty)</label><input id="post-slug" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="255"></div>
<div class="f"><label for="post-excerpt">Short description</label><textarea id="post-excerpt" name="excerpt" rows="3" maxlength="1000">{{ old('excerpt', $post->excerpt) }}</textarea></div>
<div class="f"><label for="post-featured-image">Featured image URL</label><input id="post-featured-image" type="url" name="featured_image_url" value="{{ old('featured_image_url', $post->featured_image_url) }}" maxlength="2048"></div>
<div class="f"><label for="post-featured-image-alt">Featured image alt text</label><input id="post-featured-image-alt" name="featured_image_alt" value="{{ old('featured_image_alt', $post->featured_image_alt) }}" maxlength="255"></div>
<div class="f"><label for="post-author">Author</label><input id="post-author" name="author_name" value="{{ old('author_name', $post->author_name) }}" maxlength="255"></div>
<div class="f"><label for="post-source">Official source URL</label><input id="post-source" type="url" name="official_source_url" value="{{ old('official_source_url', $post->official_source_url) }}" maxlength="2048"></div>
<div class="f"><label for="post-meta-title">Meta Title</label><input id="post-meta-title" name="meta_title" value="{{ old('meta_title', $metadata->meta_title ?? '') }}" maxlength="255" placeholder="SEO title for this blog"><small>Aim for about 50–60 characters.</small></div>
<div class="f"><label for="post-meta-description">Meta Description</label><textarea id="post-meta-description" name="meta_description" rows="3" maxlength="1000" placeholder="Describe this blog for search engines">{{ old('meta_description', $metadata->meta_description ?? '') }}</textarea><small>Aim for about 150–160 characters. Leave SEO fields empty to use the existing page defaults.</small></div>
<div class="f"><label for="post-meta-keywords">Meta Keywords</label><input id="post-meta-keywords" name="meta_keywords" value="{{ old('meta_keywords', $metadata->meta_keywords ?? '') }}" maxlength="1000" placeholder="study tips, exam preparation, practice"><small>Separate keywords with commas.</small></div>
<div class="f"><label for="post-content">Content</label><textarea id="post-content" name="content" rows="16" required maxlength="100000" data-upload-url="{{ route('admin.posts.images') }}" placeholder="Write your post here.">{{ old('content', $post->content) }}</textarea><p>Use the image button or drag an image into the editor. JPG, PNG, WebP or GIF, up to 5 MB.</p><p id="post-editor-status" role="status"></p></div>
<style>.ck-editor__editable_inline{min-height:350px}.ck-content img{max-width:100%}</style>
<script src="{{ asset('js/post-editor.js') }}?v={{ filemtime(public_path('js/post-editor.js')) }}" defer></script>
<p style="margin-bottom:20px">Published blogs and news appear on the website.</p>
<button class="btn pri" type="submit">Save post</button>
</form>
@else
<div class="head"><div><h2>Blogs &amp; News</h2><p>Manage your articles. Published blogs and news are visible on the website.</p></div><a class="btn pri" href="{{ route('admin.posts.create') }}">+ Add post</a></div>
<div class="card tc posts-table-card">
<form class="posts-table-toolbar" method="GET" action="{{ route('admin.posts.index') }}">
    <div class="f"><label for="post-search">Search posts</label><input id="post-search" type="search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="Search title or slug"></div>
    <div class="f"><label for="post-filter">Type</label><select id="post-filter" name="type"><option value="">All types</option><option value="blog" @selected(request('type') === 'blog')>Blogs</option><option value="news" @selected(request('type') === 'news')>News</option></select></div>
    <div class="f"><label for="post-status-filter">Status</label><select id="post-status-filter" name="status"><option value="">All statuses</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Published</option></select></div>
    <div class="f"><label for="post-per-page">Rows</label><select id="post-per-page" name="per_page">@foreach([10, 15, 25, 50] as $count)<option value="{{ $count }}" @selected((int) request('per_page', 15) === $count)>{{ $count }}</option>@endforeach</select></div>
    <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
    <button class="btn pri" type="submit">Apply</button><a class="btn" href="{{ route('admin.posts.index') }}">Reset</a>
</form>
<div class="tw" tabindex="0" role="region" aria-label="Posts data table">
<table class="posts-data-table">
    <caption class="posts-table-caption">Blogs and news posts</caption>
    <thead><tr>
        @foreach(['title' => 'Title', 'type' => 'Type', 'status' => 'Status', 'created_at' => 'Created'] as $column => $label)
        @php
            $isSorted = request('sort', 'created_at') === $column;
            $direction = $isSorted && request('direction', 'desc') === 'asc' ? 'desc' : 'asc';
        @endphp
        <th scope="col" aria-sort="{{ $isSorted ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('admin.posts.index', array_merge(request()->except('page'), ['sort' => $column, 'direction' => $direction])) }}">{{ $label }} <span aria-hidden="true">{{ $isSorted ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
        @endforeach
        <th scope="col">Actions</th>
    </tr></thead>
    <tbody>
        @forelse($posts as $entry)
        <tr>
            <td class="posts-title"><a href="{{ route('admin.posts.edit', $entry) }}"><strong>{{ $entry->title }}</strong></a><small>{{ $entry->slug }}</small></td>
            <td><span class="badge b-info">{{ ucfirst($entry->type) }}</span></td>
            <td><span @class(['badge', 'b-ok' => $entry->status === 'published', 'b-warn' => $entry->status === 'draft'])>{{ ucfirst($entry->status) }}</span></td>
            <td><time datetime="{{ $entry->created_at->toAtomString() }}">{{ $entry->created_at->format('d M Y') }}</time></td>
            <td><div class="posts-actions"><a class="btn" href="{{ route('admin.posts.edit', $entry) }}">Edit</a>@if($entry->status === 'published' && $entry->published_at?->lte(now()))<a class="btn" href="{{ route($entry->type === 'news' ? 'news.show' : 'blogs.show', $entry->slug) }}">View {{ $entry->type }}</a>@endif<form method="POST" action="{{ route('admin.posts.destroy', $entry) }}" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="btn dng" type="submit">Delete</button></form></div></td>
        </tr>
        @empty
        <tr><td colspan="5" class="posts-table-empty">No posts found. Try different filters or add a new post.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<div class="posts-table-footer"><span>Showing {{ $posts->firstItem() ?? 0 }}–{{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} posts</span>
@if($posts->hasPages())<nav aria-label="Posts pagination">@if($posts->previousPageUrl())<a class="btn" href="{{ $posts->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>@if($posts->nextPageUrl())<a class="btn" href="{{ $posts->nextPageUrl() }}">Next →</a>@endif</nav>@endif
</div>
</div>
@endif

