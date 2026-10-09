<main id="subj" @if(!request()->routeIs('subject')) hidden @endif>
  <div class="phero"><div class="wrap">
    <div class="crumb"><a href="/">Home</a><span>/</span><a href="#subjects">Subjects</a><span>/</span><b id="sbc"></b></div>
    <div class="ph-row"><div class="ic" id="sic"></div><div>@if(request()->routeIs('subject'))<h1 id="sn">{{ $selectedSubject?->name }}</h1>@else<h2 id="sn"></h2>@endif<p id="sd">{{ request()->routeIs('subject') ? $selectedSubject?->description : '' }}</p></div></div>
    <div class="ph-stats" id="ss"></div>
  </div></div>
  <div class="wrap lay">
    <div>
      <div class="lh"><h2 id="subject-list-title">{{ in_array(request()->route('subject'), ['current-affairs', 'general-knowledge'], true) ? 'Quizzes' : 'Chapters' }}</h2><input id="cf" type="search" placeholder="Search chapters" aria-label="Search chapters"></div>
      <div id="chapter-categories" hidden><label for="chapter-category">History category</label><select id="chapter-category"><option value="">All categories</option><option>Ancient History</option><option>Medieval History</option><option>Modern History</option></select></div>
      <div id="cl">@if(request()->routeIs('subject') && $selectedSubject)
        @foreach($selectedSubject->chapters as $index => $entry)
          @if(!in_array($selectedSubject->slug, ['current-affairs', 'general-knowledge']) && ($entry->content || $entry->notes))<article class="box seo-topic"><h2><a href="{{ $entry->readingUrl($index) }}">{{ $entry->title }}</a></h2><p>{{ $entry->description }}</p><a class="btn btn-l" href="{{ $entry->readingUrl($index) }}">Read chapter notes</a></article>@endif
        @endforeach
        @foreach($curriculum['tests'] as $key => $tests)@if(str_starts_with($key, $selectedSubject->slug.':'))@foreach($tests as $quiz)<article class="box seo-topic"><h2><a href="{{ $quiz['url'] }}">{{ $quiz['title'] }}</a></h2><p>{{ $quiz['count'] }} practice questions</p><a class="btn btn-l" href="{{ $quiz['url'] }}">Open quiz</a></article>@endforeach @endif @endforeach
      @endif</div>
    </div>
    <aside class="box side"><h3>All subjects</h3><div id="sl"></div></aside>
  </div>
</main>
