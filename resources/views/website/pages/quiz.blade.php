<main id="quiz" @if(!$currentAffairsQuiz) hidden @endif>
  <div @class(['wrap', 'qwrap' => !$currentAffairsQuiz, 'ca-reader' => $currentAffairsQuiz])><div class="crumb" id="qc"></div><div @class(['box qbox' => !$currentAffairsQuiz]) id="qb">
    @if($currentAffairsQuiz)
      <div class="ca-layout">
        <aside class="ca-sidebar" aria-label="Question navigation">
          <a class="ca-back" href="{{ route('subject', 'current-affairs') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All Current Affairs</a>
          <div class="ca-sidebar-heading">In this edition <span>{{ count($currentAffairsQuiz->questions) }}</span></div>
          <nav class="ca-question-nav" aria-label="Jump to a question">
            @foreach($currentAffairsQuiz->questionAnswers() as $question)
              <a href="#ca-question-{{ $loop->iteration }}" data-ca-nav="{{ $loop->iteration }}"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $question['q'] }}</span></a>
            @endforeach
          </nav>
        </aside>
        <div class="ca-content">
          <div class="ca-heading">
            <div class="ca-eyebrow"><span>Current Affairs</span>@if($currentAffairsQuiz->quiz_date)<span class="ca-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><time datetime="{{ $currentAffairsQuiz->quiz_date->toDateString() }}">{{ $currentAffairsQuiz->quiz_date->format('d M Y') }}</time></span>@endif</div>
            <h1>{{ $currentAffairsQuiz->title }}</h1>
            <p>Daily questions and answers, in one place.</p>
          </div>
          <div class="ca-tools">
            <label class="ca-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="ca-search" type="search" placeholder="Find a question or answer" aria-label="Search questions and answers" autocomplete="off"></label>
            <span id="ca-result-count" role="status" aria-live="polite">{{ count($currentAffairsQuiz->questions) }} {{ count($currentAffairsQuiz->questions) === 1 ? 'question' : 'questions' }}</span>
          </div>
          <div class="ca-conversation">
            @foreach($currentAffairsQuiz->questionAnswers() as $question)
              <article class="ca-exchange" id="ca-question-{{ $loop->iteration }}" data-ca-question="{{ $loop->iteration }}">
                <div class="ca-question-message"><span class="ca-question-number">Question {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $question['q'] }}</h2></div>
                <div class="ca-answer-message"><div><span class="ca-answer-label">Answer</span><p>{{ $question['answer'] }}</p></div></div>
              </article>
            @endforeach
          </div>
          <div class="ca-no-results" id="ca-no-results" hidden><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h2>No matching questions</h2><p>Try another word, or clear your search.</p></div>
          <div class="ca-end"><span>You're all caught up.</span><a href="{{ route('subject', 'current-affairs') }}">Browse more Current Affairs <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        </div>
      </div>
    @endif
  </div></div>
</main>
