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
<div class="f"><label for="post-content">Content</label><textarea id="post-content" name="content" rows="16" required maxlength="100000" placeholder="Write your post here. Separate paragraphs with a blank line.">{{ old('content', $post->content) }}</textarea></div>
<p style="margin-bottom:20px">Only published blogs appear on the website. News stays in the admin panel for now.</p>
<button class="btn pri" type="submit">Save post</button>
</form>
@else
<div class="head"><div><h2>Blogs &amp; News</h2><p>Manage your articles. Only published blogs are visible on the website.</p></div><a class="btn pri" href="{{ route('admin.posts.create') }}">+ Add post</a></div>
<form method="GET" action="{{ route('admin.posts.index') }}" style="margin-bottom:20px;display:flex;gap:12px;align-items:center"><label for="post-filter">Filter</label><select id="post-filter" name="type"><option value="">All posts</option><option value="blog" @selected(request('type') === 'blog')>Blogs</option><option value="news" @selected(request('type') === 'news')>News</option></select><button class="btn">Apply</button></form>
@forelse($posts as $entry)
<div class="card" style="padding:22px;margin-bottom:14px"><div class="head"><div><h3>{{ $entry->title }}</h3><p>{{ ucfirst($entry->type) }} · {{ ucfirst($entry->status) }} · {{ $entry->created_at->format('d M Y') }}</p></div><div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn" href="{{ route('admin.posts.edit', $entry) }}">Edit</a>@if($entry->type === 'blog' && $entry->status === 'published')<a class="btn" href="{{ route('blogs.show', $entry->slug) }}">View blog</a>@endif<form method="POST" action="{{ route('admin.posts.destroy', $entry) }}" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="btn dng" type="submit">Delete</button></form></div></div><p>{{ $entry->excerpt }}</p></div>
@empty<div class="card empty">No posts yet. Add your first blog or news post.</div>@endforelse
<div style="margin-top:24px">@if($posts->hasPages())<nav aria-label="Pagination" style="display:flex;gap:16px;align-items:center">@if($posts->previousPageUrl())<a class="btn" href="{{ $posts->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>@if($posts->nextPageUrl())<a class="btn" href="{{ $posts->nextPageUrl() }}">Next →</a>@endif</nav>@endif</div>
@endif

