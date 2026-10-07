<div class="head"><div><h2>Page SEO</h2><p>Manage the search title, description and keywords for each page.</p></div></div>
@if(session('status'))<div class="card settings-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card settings-message" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="card settings-form" method="GET" action="{{ route('admin.seo.index') }}">
<section class="settings-section"><div class="f"><label for="seo-page">Select a page</label><select id="seo-page" name="page_key">
@foreach(collect($seoPages)->groupBy('group', true) as $group => $entries)<optgroup label="{{ $group }}">@foreach($entries as $key => $entry)<option value="{{ $key }}" @selected($selectedSeoKey === $key)>{{ $entry['label'] }}</option>@endforeach</optgroup>@endforeach
</select></div><button class="btn" type="submit">Edit selected page</button></section>
</form>
<form class="card settings-form" style="margin-top:24px" method="POST" action="{{ route('admin.seo.update') }}">
@csrf @method('PUT')<input type="hidden" name="page_key" value="{{ $selectedSeoKey }}">
<section class="settings-section"><h3>{{ $seoPages[$selectedSeoKey]['label'] }}</h3>
@if($seoPages[$selectedSeoKey]['url'])<p class="settings-hint"><a href="{{ $seoPages[$selectedSeoKey]['url'] }}" target="_blank" rel="noopener">Preview page ↗</a></p>@endif
<p class="settings-hint">Leave a field empty to use its existing or default value. A page's custom values take priority over page type and website defaults. Titles are used exactly as entered.</p>
<div class="f"><label for="seo-title">SEO title</label><input id="seo-title" name="meta_title" maxlength="255" value="{{ old('meta_title', $metadata?->meta_title) }}" placeholder="Title for browser tabs and search results"></div>
<div class="f"><label for="seo-description">Meta description</label><textarea id="seo-description" name="meta_description" maxlength="1000" rows="4" placeholder="Describe this page for search results">{{ old('meta_description', $metadata?->meta_description) }}</textarea></div>
<div class="f"><label for="seo-keywords">Meta keywords</label><textarea id="seo-keywords" name="meta_keywords" maxlength="1000" rows="3" placeholder="study, exam preparation, practice">{{ old('meta_keywords', $metadata?->meta_keywords) }}</textarea></div>
</section><div class="settings-actions"><span class="settings-hint">Save blank fields to restore defaults.</span><button class="btn pri" type="submit">Save page SEO</button></div>
</form>
