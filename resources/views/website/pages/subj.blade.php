<main id="subj" hidden>
  <div class="phero"><div class="wrap">
    <div class="crumb"><a href="/">Home</a><span>/</span><a href="#subjects">Subjects</a><span>/</span><b id="sbc"></b></div>
    <div class="ph-row"><div class="ic" id="sic"></div><div>@if(request()->routeIs('subject'))<h1 id="sn"></h1>@else<h2 id="sn"></h2>@endif<p id="sd"></p></div></div>
    <div class="ph-stats" id="ss"></div>
  </div></div>
  <div class="wrap lay">
    <div>
      <div class="lh"><h2 id="subject-list-title">{{ in_array(request()->route('subject'), ['current-affairs', 'general-knowledge'], true) ? 'Quizzes' : 'Chapters' }}</h2><input id="cf" type="search" placeholder="Search chapters" aria-label="Search chapters"></div>
      <div id="chapter-categories" hidden><label for="chapter-category">History category</label><select id="chapter-category"><option value="">All categories</option><option>Ancient History</option><option>Medieval History</option><option>Modern History</option></select></div>
      <div id="cl"></div>
    </div>
    <aside class="box side"><h3>All subjects</h3><div id="sl"></div></aside>
  </div>
</main>
